package collector

import (
	"os"
	"sort"
	"time"

	"github.com/shirou/gopsutil/v3/cpu"
	"github.com/shirou/gopsutil/v3/disk"
	"github.com/shirou/gopsutil/v3/host"
	"github.com/shirou/gopsutil/v3/load"
	"github.com/shirou/gopsutil/v3/mem"
	psnet "github.com/shirou/gopsutil/v3/net"
	"github.com/shirou/gopsutil/v3/process"
)

type DiskPartition struct {
	Mount string `json:"mount"`
	Total uint64 `json:"total"`
	Used  uint64 `json:"used"`
	Free  uint64 `json:"free"`
}

type ProcessMemory struct {
	PID        int32   `json:"pid"`
	Name       string  `json:"name"`
	RSS        uint64  `json:"mem_rss"`
	CPUPercent float64 `json:"cpu_percent"`
}

type Metric struct {
	CollectedAt    time.Time       `json:"collected_at"`
	CPUUsage       float64         `json:"cpu_usage"`
	RAMUsed        uint64          `json:"ram_used"`
	RAMTotal       uint64          `json:"ram_total"`
	DiskReadBytes  uint64          `json:"disk_read_bytes"`
	DiskWriteBytes uint64          `json:"disk_write_bytes"`
	NetRxBytes     uint64          `json:"net_rx_bytes"`
	NetTxBytes     uint64          `json:"net_tx_bytes"`
	LoadAvg1       float64         `json:"load_avg_1"`
	LoadAvg5       float64         `json:"load_avg_5"`
	LoadAvg15      float64         `json:"load_avg_15"`
	UptimeSeconds  uint64          `json:"uptime_seconds"`
	DiskPartitions []DiskPartition `json:"disk_partitions"`
	Processes      []ProcessMemory `json:"processes"`
}

// skipFSType lists pseudo/kernel-only filesystem types that carry no useful disk
// usage data. overlay is intentionally absent so the Docker container root is included.
var skipFSType = map[string]bool{
	"autofs": true, "bdev": true, "binfmt_misc": true, "bpf": true,
	"cgroup": true, "cgroup2": true, "configfs": true, "cpuset": true, "debugfs": true,
	"devpts": true, "devtmpfs": true, "efivarfs": true, "fusectl": true,
	"fuse.gvfsd-fuse": true, "hugetlbfs": true, "mqueue": true,
	"nfsd": true, "nsfs": true, "proc": true, "pstore": true,
	"rpc_pipefs": true, "securityfs": true, "sockfs": true,
	"squashfs": true, "sysfs": true, "tmpfs": true, "tracefs": true,
}

// Collector holds counters from the previous interval to compute deltas.
type Collector struct {
	prevDiskRead  uint64
	prevDiskWrite uint64
	prevNetRx     uint64
	prevNetTx     uint64
	primed        bool
	// procCache retains *process.Process objects across cycles so CPUPercent(0)
	// can compute a meaningful delta (first cycle always returns 0, like disk/net deltas).
	procCache map[int32]*process.Process
}

func New() *Collector {
	return &Collector{procCache: make(map[int32]*process.Process)}
}

// Prime reads I/O counters once to establish the baseline, so the first real
// collection reports a delta from "now" rather than from boot.
func (c *Collector) Prime() {
	c.readDiskCounters()
	c.readNetCounters()
	c.primed = true
}

func (c *Collector) Collect() (*Metric, error) {
	m := &Metric{CollectedAt: time.Now().UTC()}

	pcts, err := cpu.Percent(0, false)
	if err != nil {
		return nil, err
	}
	if len(pcts) > 0 {
		m.CPUUsage = pcts[0]
	}

	vm, err := mem.VirtualMemory()
	if err != nil {
		return nil, err
	}
	m.RAMUsed = vm.Used
	m.RAMTotal = vm.Total

	diskRead, diskWrite := c.readDiskCounters()
	if c.primed {
		m.DiskReadBytes = delta(diskRead, c.prevDiskRead)
		m.DiskWriteBytes = delta(diskWrite, c.prevDiskWrite)
	}
	c.prevDiskRead = diskRead
	c.prevDiskWrite = diskWrite

	netRx, netTx := c.readNetCounters()
	if c.primed {
		m.NetRxBytes = delta(netRx, c.prevNetRx)
		m.NetTxBytes = delta(netTx, c.prevNetTx)
	}
	c.prevNetRx = netRx
	c.prevNetTx = netTx

	c.primed = true

	avg, err := load.Avg()
	if err == nil {
		m.LoadAvg1 = avg.Load1
		m.LoadAvg5 = avg.Load5
		m.LoadAvg15 = avg.Load15
	}

	if uptime, err := host.Uptime(); err == nil {
		m.UptimeSeconds = uptime
	}

	parts, err := disk.Partitions(true) // all=true so overlay (Docker root) is included
	if err == nil {
		for _, p := range parts {
			if skipFSType[p.Fstype] {
				continue
			}
			// Docker injects /etc/resolv.conf, /etc/hostname, /etc/hosts as file
			// bind mounts; skip anything that isn't a real directory.
			if info, err := os.Stat(p.Mountpoint); err != nil || !info.IsDir() {
				continue
			}
			usage, err := disk.Usage(p.Mountpoint)
			if err != nil {
				continue
			}
			m.DiskPartitions = append(m.DiskPartitions, DiskPartition{
				Mount: p.Mountpoint,
				Total: usage.Total,
				Used:  usage.Used,
				Free:  usage.Free,
			})
		}
	}

	m.Processes = c.collectTopProcesses(20)

	return m, nil
}

func (c *Collector) collectTopProcesses(limit int) []ProcessMemory {
	procs, err := process.Processes()
	if err != nil {
		return nil
	}

	type entry struct {
		p    *process.Process
		rss  uint64
		name string
	}
	entries := make([]entry, 0, len(procs))
	for _, p := range procs {
		info, err := p.MemoryInfo()
		if err != nil || info == nil {
			continue
		}
		name, _ := p.Name()
		entries = append(entries, entry{p: p, rss: info.RSS, name: name})
	}
	sort.Slice(entries, func(i, j int) bool { return entries[i].rss > entries[j].rss })
	if len(entries) > limit {
		entries = entries[:limit]
	}

	// Reuse cached process objects so CPUPercent(0) computes a delta from the
	// previous cycle rather than from process start (which would return 0).
	newCache := make(map[int32]*process.Process, limit)
	result := make([]ProcessMemory, 0, len(entries))
	for _, e := range entries {
		pid := e.p.Pid
		cached, ok := c.procCache[pid]
		if !ok {
			cached = e.p
		}
		cpuPct, _ := cached.CPUPercent()
		newCache[pid] = cached
		result = append(result, ProcessMemory{
			PID:        pid,
			Name:       e.name,
			RSS:        e.rss,
			CPUPercent: cpuPct,
		})
	}
	c.procCache = newCache
	return result
}

func (c *Collector) readDiskCounters() (read, write uint64) {
	counters, err := disk.IOCounters()
	if err != nil {
		return 0, 0
	}
	for _, v := range counters {
		read += v.ReadBytes
		write += v.WriteBytes
	}
	return read, write
}

func (c *Collector) readNetCounters() (rx, tx uint64) {
	counters, err := psnet.IOCounters(false)
	if err != nil || len(counters) == 0 {
		return 0, 0
	}
	return counters[0].BytesRecv, counters[0].BytesSent
}

func delta(current, previous uint64) uint64 {
	if current >= previous {
		return current - previous
	}
	return current // counter reset (e.g. system reboot while agent was running)
}
