package collector

import (
	"context"
	"encoding/json"
	"fmt"
	"net"
	"net/http"
	"os"
	"strings"
	"sync"
	"time"
)

// dockerSocketPath returns the Docker socket path, overridable via DOCKER_SOCKET.
func dockerSocketPath() string {
	if p := os.Getenv("DOCKER_SOCKET"); p != "" {
		return p
	}
	return "/var/run/docker.sock"
}

// maxDockerConcurrency caps simultaneous stats requests so a host running many
// containers does not open an unbounded number of connections per cycle.
const maxDockerConcurrency = 8

// dockerClient talks to the Docker Engine API over the Unix socket using only
// the standard library. It is created only when the socket is reachable, so the
// whole container-collection feature auto-enables purely by mounting the socket.
type dockerClient struct {
	http *http.Client
	// prevCPU keeps the previous raw CPU counters per container so CPU% is a
	// delta between two agent cycles, mirroring the disk/net counter approach.
	prevCPU map[string]cpuSnapshot
}

type cpuSnapshot struct {
	total  uint64
	system uint64
}

// newDockerClient returns a client only if the Docker socket is present;
// otherwise it returns nil and container collection is skipped.
func newDockerClient() *dockerClient {
	socket := dockerSocketPath()
	if info, err := os.Stat(socket); err != nil || info.IsDir() {
		return nil
	}

	return &dockerClient{
		http: &http.Client{
			Timeout: 5 * time.Second,
			Transport: &http.Transport{
				DialContext: func(ctx context.Context, _, _ string) (net.Conn, error) {
					return (&net.Dialer{}).DialContext(ctx, "unix", socket)
				},
			},
		},
		prevCPU: make(map[string]cpuSnapshot),
	}
}

// dockerContainer is the subset of GET /containers/json we care about.
type dockerContainer struct {
	ID    string   `json:"Id"`
	Names []string `json:"Names"`
	Image string   `json:"Image"`
	State string   `json:"State"`
}

// dockerStats is the subset of GET /containers/{id}/stats?stream=false we need.
type dockerStats struct {
	CPUStats    cpuStats `json:"cpu_stats"`
	PreCPUStats cpuStats `json:"precpu_stats"`
	MemoryStats struct {
		Usage uint64            `json:"usage"`
		Limit uint64            `json:"limit"`
		Stats map[string]uint64 `json:"stats"`
	} `json:"memory_stats"`
}

type cpuStats struct {
	CPUUsage struct {
		TotalUsage  uint64   `json:"total_usage"`
		PercpuUsage []uint64 `json:"percpu_usage"`
	} `json:"cpu_usage"`
	SystemCPUUsage uint64 `json:"system_cpu_usage"`
	OnlineCPUs     uint32 `json:"online_cpus"`
}

// collect returns CPU% and memory for every running container. Errors degrade
// gracefully: a failed listing yields no containers, a failed per-container
// stats call skips just that container.
func (d *dockerClient) collect() []ContainerMetric {
	containers, err := d.listContainers()
	if err != nil {
		return nil
	}

	results := make([]ContainerMetric, len(containers))
	ok := make([]bool, len(containers))

	sem := make(chan struct{}, maxDockerConcurrency)
	var wg sync.WaitGroup
	for i, c := range containers {
		wg.Add(1)
		sem <- struct{}{}
		go func(i int, c dockerContainer) {
			defer wg.Done()
			defer func() { <-sem }()
			if m, err := d.containerMetric(c); err == nil {
				results[i] = m
				ok[i] = true
			}
		}(i, c)
	}
	wg.Wait()

	// Prune CPU state for containers that are no longer running, and keep only
	// the successfully collected metrics in stable order.
	seen := make(map[string]struct{}, len(containers))
	out := make([]ContainerMetric, 0, len(containers))
	for i := range containers {
		if !ok[i] {
			continue
		}
		seen[results[i].ID] = struct{}{}
		out = append(out, results[i])
	}
	for id := range d.prevCPU {
		if _, alive := seen[id]; !alive {
			delete(d.prevCPU, id)
		}
	}
	return out
}

func (d *dockerClient) listContainers() ([]dockerContainer, error) {
	var containers []dockerContainer
	if err := d.get("/containers/json", &containers); err != nil {
		return nil, err
	}
	running := containers[:0]
	for _, c := range containers {
		if c.State == "running" {
			running = append(running, c)
		}
	}
	return running, nil
}

func (d *dockerClient) containerMetric(c dockerContainer) (ContainerMetric, error) {
	var s dockerStats
	if err := d.get("/containers/"+c.ID+"/stats?stream=false", &s); err != nil {
		return ContainerMetric{}, err
	}

	return ContainerMetric{
		ID:          shortID(c.ID),
		Name:        containerName(c.Names),
		Image:       c.Image,
		CPUPercent:  d.cpuPercent(c.ID, s),
		MemoryUsage: memoryUsage(s),
		MemoryLimit: s.MemoryStats.Limit,
	}, nil
}

// cpuPercent applies the standard Docker formula. It prefers the precpu_stats
// embedded in the response; when those are absent (first sighting of a
// container) it falls back to the delta against the previous agent cycle.
func (d *dockerClient) cpuPercent(id string, s dockerStats) float64 {
	cur := cpuSnapshot{total: s.CPUStats.CPUUsage.TotalUsage, system: s.CPUStats.SystemCPUUsage}

	prev := cpuSnapshot{total: s.PreCPUStats.CPUUsage.TotalUsage, system: s.PreCPUStats.SystemCPUUsage}
	if prev.total == 0 && prev.system == 0 {
		// precpu not populated; use our own previous reading if we have one.
		if p, ok := d.prevCPU[id]; ok {
			prev = p
		}
	}
	d.prevCPU[id] = cur

	cpuDelta := float64(cur.total) - float64(prev.total)
	systemDelta := float64(cur.system) - float64(prev.system)
	if systemDelta <= 0 || cpuDelta < 0 {
		return 0
	}

	cpus := float64(s.CPUStats.OnlineCPUs)
	if cpus == 0 {
		cpus = float64(len(s.CPUStats.CPUUsage.PercpuUsage))
	}
	if cpus == 0 {
		cpus = 1
	}

	return (cpuDelta / systemDelta) * cpus * 100.0
}

// memoryUsage mirrors `docker stats`: subtract page cache from total usage.
func memoryUsage(s dockerStats) uint64 {
	usage := s.MemoryStats.Usage
	// cgroup v1 exposes "cache"; cgroup v2 exposes "inactive_file".
	cache := s.MemoryStats.Stats["cache"]
	if cache == 0 {
		cache = s.MemoryStats.Stats["inactive_file"]
	}
	if cache > usage {
		return usage
	}
	return usage - cache
}

func (d *dockerClient) get(path string, v any) error {
	// The Unix-socket dialer ignores host/port, so any URL host works.
	req, err := http.NewRequest(http.MethodGet, "http://docker"+path, nil)
	if err != nil {
		return err
	}
	resp, err := d.http.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		return fmt.Errorf("docker GET %s: status %d", path, resp.StatusCode)
	}
	return json.NewDecoder(resp.Body).Decode(v)
}

func shortID(id string) string {
	if len(id) > 12 {
		return id[:12]
	}
	return id
}

func containerName(names []string) string {
	if len(names) == 0 {
		return ""
	}
	// Docker returns names with a leading slash, e.g. "/argoos-app".
	return strings.TrimPrefix(names[0], "/")
}
