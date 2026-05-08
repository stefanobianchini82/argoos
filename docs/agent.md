# Argoos Agent

Minimal Go binary that collects system metrics and dispatches them to the central server (HTTP mode) or to a local file (file mode, useful for debugging).

## Directory Structure

```
agent/
├── Dockerfile
├── .env.example
├── go.mod
├── go.sum
└── cmd/
│   └── agent/
│       └── main.go             # entrypoint, main loop
└── internal/
    ├── collector/
    │   └── collector.go        # reads metrics via gopsutil
    ├── sender/
    │   └── sender.go           # Sender interface + FileSender + HTTPSender
    └── config/
        └── config.go           # loads and validates env vars
```

## Environment Variables

| Variable | Description | Default |
|---|---|---|
| `SERVER_URL` | Full URL of the central server metrics endpoint | — |
| `API_KEY` | Authentication key for this host | — |
| `HOST_LABEL` | Human-readable name for this host | system hostname |
| `COLLECT_INTERVAL` | Seconds between collections | `30` |
| `RETRY_ATTEMPTS` | Max HTTP retries with exponential backoff | `3` |
| `OUTPUT_FILE` | **File mode only** — file path or `stdout` | `/data/metrics.jsonl` |

The agent selects its mode automatically:

- **HTTP mode** — if both `SERVER_URL` and `API_KEY` are set, metrics are POSTed to the server. Setting only one of the two is a configuration error.
- **File mode** — if neither is set, metrics are written to `OUTPUT_FILE` (JSONL). Useful for local inspection without a running server.

Copy `.env.example` as a starting point.

## Build

Requires Docker (no local Go toolchain needed).

```bash
cd agent
docker build -t argoos-agent:latest .
```

Image size: **~8 MB** (built `FROM scratch`, static binary + CA certificates).

To regenerate `go.sum` or add dependencies without a local Go 1.22 install:

```bash
docker run --rm -v "$(pwd)":/app -w /app golang:1.22-alpine go mod tidy
```

## Run

### Send metrics to the central server (HTTP mode)

```bash
docker run --rm \
  -e SERVER_URL=https://your-server/api/v1/metrics \
  -e API_KEY=your-api-key-here \
  -e HOST_LABEL=my-server \
  -e COLLECT_INTERVAL=30 \
  -e RETRY_ATTEMPTS=3 \
  argoos-agent:latest
```

Metrics are POSTed to `SERVER_URL` every `COLLECT_INTERVAL` seconds with the `X-API-Key` header set to `API_KEY`. On failure the agent retries up to `RETRY_ATTEMPTS` times with exponential backoff (1 s, 2 s, 4 s, …).

### Write metrics to a file (file mode)

Mount a local directory to persist the JSONL output:

```bash
docker run --rm \
  -e HOST_LABEL=my-server \
  -e COLLECT_INTERVAL=10 \
  -e OUTPUT_FILE=/data/metrics.jsonl \
  -v /tmp/argoos-data:/data \
  argoos-agent:latest
```

Each line in the output file is one JSON object (one collection interval).

### Print metrics to stdout (file mode)

Useful for quick inspection without mounting a volume:

```bash
docker run --rm \
  -e HOST_LABEL=my-server \
  -e COLLECT_INTERVAL=10 \
  -e OUTPUT_FILE=stdout \
  argoos-agent:latest
```

## Metrics Collected

| Metric | Description |
|---|---|
| `cpu_usage` | Overall CPU usage percentage |
| `ram_used` | Used RAM in bytes |
| `ram_total` | Total RAM in bytes |
| `disk_read_bytes` | Disk read bytes since last interval |
| `disk_write_bytes` | Disk write bytes since last interval |
| `net_rx_bytes` | Network received bytes since last interval |
| `net_tx_bytes` | Network transmitted bytes since last interval |
| `load_avg_1` | 1-minute load average |
| `load_avg_5` | 5-minute load average |
| `load_avg_15` | 15-minute load average |
| `disk_partitions` | Array: mount point, total, used, free for each partition |

Disk and network values are **deltas** relative to the previous interval. On startup the agent primes the counters once before the first tick, so the initial reading reflects usage since the agent started rather than since system boot.

## Payload Format

One JSON object sent per interval (identical whether written to file or POSTed to the server):

```json
{"collected_at":"2026-05-07T14:00:00Z","cpu_usage":23.4,"ram_used":2147483648,"ram_total":8589934592,"disk_read_bytes":204800,"disk_write_bytes":102400,"net_rx_bytes":512000,"net_tx_bytes":256000,"load_avg_1":0.45,"load_avg_5":0.38,"load_avg_15":0.31,"disk_partitions":[{"mount":"/","total":107374182400,"used":53687091200,"free":53687091200}]}
```

In HTTP mode the payload is sent as `application/json` with `X-API-Key: <key>` header. The server identifies the host from the API key — no `host_label` field is included in the payload.

## Sender Interface

Dispatching is abstracted behind the `Sender` interface in `internal/sender/sender.go`:

```go
type Sender interface {
    Send(m *collector.Metric) error
}
```

`FileSender` writes JSONL to a file or stdout. `HTTPSender` POSTs to the server API. `main.go` selects the implementation based on whether `SERVER_URL` is set:

```go
// HTTP mode (SERVER_URL + API_KEY set)
snd = sender.NewHTTPSender(cfg.ServerURL, cfg.APIKey, cfg.RetryAttempts)

// File mode (fallback)
snd = sender.NewFileSender(cfg.OutputFile)
```
