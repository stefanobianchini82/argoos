# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Argoos** is a self-hosted monitoring system comprising two main components:

- **Server** (`/server`): Laravel 13 application that receives metrics from agents, stores them in MySQL, processes them asynchronously via Redis/Horizon, and provides a Livewire-based dashboard with alerting system.
- **Agent** (`/agent`): Minimal Go 1.22 binary that collects system metrics (CPU, RAM, disk, network) via `gopsutil` and sends them to the server every N seconds.

**Architecture**: Agents POST metrics to `/api/v1/metrics` (authenticated via `X-API-Key` header). The server validates, queues, and persists metrics asynchronously. Scheduled jobs run every minute to check alert rules and detect offline hosts. The dashboard displays real-time and historical metrics with Chart.js graphs.

**Key Design Principles**:
- Agent is stateless, no persistent storage — fire and forget
- Server uses async processing via Horizon (Laravel queues on Redis)
- MySQL metrics table uses RANGE partitioning by month for efficient retention cleanup
- API key prefix indexing ensures O(1) host lookup regardless of agent count
- Full Docker Compose stack for local development (PHP-FPM, Nginx, MySQL, Redis, Horizon)

---

## Directory Structure

```
argoos/
├── agent/                         # Go agent (argoos-agent)
│   ├── cmd/agent/main.go         # Entrypoint: config → collector → sender loop
│   ├── internal/
│   │   ├── config/config.go      # Load & validate env vars (SERVER_URL, API_KEY, etc.)
│   │   ├── collector/collector.go # Collect metrics via gopsutil, delta calculations
│   │   └── sender/sender.go      # Sender interface: HTTPSender, FileSender
│   ├── go.mod, go.sum
│   ├── Dockerfile                # Multi-stage build → scratch image (~8 MB)
│   └── .env.example
│
├── server/                        # Laravel server (argoos-server)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/MetricController.php  # POST /api/v1/metrics → store
│   │   │   └── Middleware/AuthenticateAgent.php      # X-API-Key validation (O(1) prefix lookup)
│   │   ├── Models/
│   │   │   ├── Host.php          # Hosts table: api_key (bcrypt), api_key_prefix (indexed)
│   │   │   ├── Metric.php        # Metrics table (RANGE partitioned by month)
│   │   │   └── DiskPartition.php # Per-partition usage (RANGE partitioned)
│   │   ├── Jobs/
│   │   │   ├── CheckAlertRules.php   # Evaluate alert rules (every minute via Schedule)
│   │   │   └── CheckHostsOffline.php # Detect offline hosts (every minute via Schedule)
│   │   ├── Livewire/
│   │   │   ├── DashboardOverview.php # Main dashboard grid: hosts status
│   │   │   ├── HostDetail.php        # Single host metrics & partitions
│   │   │   ├── AlertRuleList.php     # Alert rules CRUD
│   │   │   └── Settings.php          # Telegram/Slack tokens, retention settings
│   │   ├── Channels/
│   │   │   ├── TelegramChannel.php   # Notification channel
│   │   │   └── SlackChannel.php      # Notification channel
│   │   └── Providers/AppServiceProvider.php  # Register custom notification channels
│   ├── routes/
│   │   ├── api.php       # POST /api/v1/metrics (auth.agent middleware)
│   │   └── web.php       # Dashboard routes (Livewire components)
│   ├── database/migrations/  # Create tables, add partitioning, alerts, settings
│   ├── config/           # Laravel config: database, queue, cache, horizon, dashboard
│   ├── resources/        # CSS (Tailwind), JS (Chart.js), Livewire views
│   ├── docker/
│   │   ├── php/Dockerfile       # PHP-FPM 8.3-alpine, Composer install, artisan commands
│   │   └── nginx/default.conf   # Reverse proxy to PHP-FPM, static asset serving
│   ├── docker-compose.yml       # MySQL 8, Redis 7, Nginx, PHP-FPM, Horizon
│   ├── bootstrap/app.php        # Middleware aliases, scheduled jobs, routing config
│   ├── artisan                  # Laravel CLI entrypoint
│   ├── vite.config.js          # Vite + Tailwind CSS + Laravel plugin
│   └── composer.json, package.json
│
├── docs/                         # Documentation
│   ├── agent.md          # Agent: build, run modes (HTTP vs file), env vars
│   ├── server.md         # Server: Docker stack, setup, API endpoints, auth flow
│   ├── database.md       # RANGE partitioning, retention, integrity guarantees
│   ├── monitoring-dashboard-spec.md  # Full system spec, phases, roadmap
│   └── redis.md          # Redis setup for caching/queuing
│
├── .gitignore           # Agent output/, server vendor/node_modules/storage/
└── CLAUDE.md            # This file
```

---

## Server Development

### Build & Run

#### Docker (Recommended)
```bash
cd server
# 1. Configure environment
cp .env.example .env
# Edit .env: set DB_PASSWORD, DB_ROOT_PASSWORD, DASHBOARD_PASSWORD

# 2. Build and start containers
docker compose up --build -d

# 3. Run migrations (creates tables with partitioning)
docker compose exec app php artisan migrate

# 4. Register the first host
docker compose exec app php artisan tinker
# In tinker:
# $key = bin2hex(random_bytes(32));
# App\Models\Host::create(['label' => 'server1', 'ip' => '192.168.1.10', 'api_key' => password_hash($key, PASSWORD_BCRYPT), 'api_key_prefix' => substr($key, 0, 12)]);
# echo $key;
```

**Access**: http://localhost:8080 (Livewire dashboard, basic auth via DASHBOARD_USER/DASHBOARD_PASSWORD)
**Services**:
- `app`: PHP-FPM 8.3-alpine
- `nginx`: Reverse proxy (port 8080 → app)
- `mysql`: MySQL 8.0 (port 3306)
- `redis`: Redis 7-alpine (cache + queue)
- `horizon`: Laravel Horizon queue worker

#### Local Development (without Docker)
```bash
cd server

# Install PHP dependencies
composer install

# Generate app key
php artisan key:generate

# Set up SQLite for local testing
cp .env.example .env
# Edit .env: DB_CONNECTION=sqlite, DB_DATABASE=database/database.sqlite
touch database/database.sqlite

# Run migrations
php artisan migrate

# Start dev servers (concurrent: Artisan, Horizon, logs, Vite)
composer run dev

# Or run individually:
php artisan serve                              # Port 8000
php artisan queue:listen --tries=1 --timeout=0 # Horizon (local dev mode)
php artisan pail --timeout=0                   # Log streaming
npm run dev                                     # Vite (CSS/JS rebuilding)
```

### Testing

```bash
cd server

# Run all tests
composer test

# Run specific test
php artisan test tests/Feature/ExampleTest.php

# Run with coverage
php artisan test --coverage

# In Docker:
docker compose exec app composer test
```

**Test Setup**: `phpunit.xml` configured for SQLite in-memory database, sync queue (no Redis needed for tests).

### Linting

```bash
cd server

# Laravel Pint (PHP code style)
./vendor/bin/pint

# Check only (no fix)
./vendor/bin/pint --test

# In Docker:
docker compose exec app ./vendor/bin/pint
```

### Database

**Schema**:
- `hosts`: id, label, description, ip, api_key (bcrypt), api_key_prefix (indexed), last_seen_at, last_offline_notified_at
- `metrics`: id (PRIMARY KEY with collected_at for RANGE partition), host_id, collected_at, cpu_usage, ram_used/total, disk_read/write_bytes, net_rx/tx_bytes, load_avg_1/5/15
- `disk_partitions`: id, host_id, mount_point, total, used, free, collected_at (RANGE partitioned)
- `alert_rules`: host_id, metric_type (cpu, ram, disk), operator (>, <, ==), threshold, enabled, channels (JSON: ["telegram", "slack", "email"])
- `alert_events`: host_id, rule_id, triggered_at, resolved_at, value, notification_sent_at
- `settings`: key, value (Telegram bot token, Slack webhook, retention days, etc.)

**Partitioning**: `metrics` and `disk_partitions` use `PARTITION BY RANGE (UNIX_TIMESTAMP(collected_at))` with initial partition `p_initial MAXVALUE`. Monthly partition cleanup via future job.

**Migrations**: Run via `php artisan migrate` or `docker compose exec app php artisan migrate`.

### API Endpoints

**POST /api/v1/metrics** (auth.agent middleware)
- Authenticate via `X-API-Key` header (O(1) prefix lookup + bcrypt verify)
- Accept JSON payload: collected_at, cpu_usage, ram_used/total, disk_read/write_bytes, net_rx/tx_bytes, load_avg_*, disk_partitions array
- Validate request body
- Store Metric + DiskPartition records (atomic transaction)
- Return 201 Created (or 401 Unauthorized, 422 Unprocessable Entity)

**Web Routes** (Livewire dashboard):
- `/` - DashboardOverview: list hosts with status, last seen, current metrics
- `/hosts/{id}` - HostDetail: metrics graph, disk usage, alert rules
- `/hosts/create` - HostCreate: register new host (generate API key)
- `/hosts/{id}/edit` - HostEdit: update host info
- `/alerts` - AlertRuleList: list, create, update rules for hosts
- `/settings` - Settings: configure notification channels (Telegram, Slack)

### Queue & Jobs

**Queue Connection**: Redis (configured in `.env` QUEUE_CONNECTION=redis)

**Horizon**: Laravel's queue dashboard at `/horizon` (with basic auth). Shows active jobs, failed jobs, queue throughput.

**Jobs**:
- `CheckAlertRules`: Evaluate all enabled rules every minute (sum recent metrics, compare thresholds, trigger notifications)
- `CheckHostsOffline`: Detect hosts without metrics for >3 minutes, send offline notifications

**Scheduled Jobs**: Configured in `bootstrap/app.php` via `Schedule::class`. Run via `php artisan schedule:work` (local) or Horizon container.

### Configuration Files

- `.env.example` → `.env`: DB credentials, Redis host/port, Telegram/Slack tokens, retention settings
- `config/database.php`: MySQL connection with hostname resolution
- `config/queue.php`: Redis queue driver (Horizon)
- `config/cache.php`: Redis cache store (with prefix)
- `config/horizon.php`: Horizon worker config (memory, timeout, balancing)
- `config/dashboard.php`: Basic auth credentials (DASHBOARD_USER/DASHBOARD_PASSWORD)

### Common Tasks

```bash
# Create a new migration
php artisan make:migration create_table_name

# Create model + migration + controller
php artisan make:model ModelName -mcr

# Generate app key
php artisan key:generate

# Clear caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Rebuild frontend assets
npm run build   # Production
npm run dev     # Development with hot reload

# SSH into PHP container
docker compose exec app bash

# View logs
docker compose logs -f app        # PHP application
docker compose logs -f horizon    # Queue worker
docker compose logs -f mysql      # Database
```

---

## Agent Development

### Build

```bash
cd agent

# Requires Docker (no local Go toolchain needed)
docker build -t argoos-agent:latest .

# Image size: ~8 MB (FROM scratch, static binary + CA certs)
```

**Multi-stage Dockerfile**:
1. Build stage: golang:1.22-alpine → compile CGO_ENABLED=0 (static binary)
2. Runtime stage: FROM scratch → copy binary + CA certificates

### Run

```bash
# HTTP mode (send to server)
docker run --rm \
  -e SERVER_URL=https://your-server/api/v1/metrics \
  -e API_KEY=your-api-key \
  -e HOST_LABEL=my-server \
  -e COLLECT_INTERVAL=30 \
  argoos-agent:latest

# File mode (write to JSONL, no server needed)
docker run --rm \
  -e HOST_LABEL=my-server \
  -e COLLECT_INTERVAL=10 \
  -e OUTPUT_FILE=/data/metrics.jsonl \
  -v /tmp/argoos-data:/data \
  argoos-agent:latest

# Stdout mode (inspect metrics)
docker run --rm \
  -e HOST_LABEL=my-server \
  -e COLLECT_INTERVAL=10 \
  -e OUTPUT_FILE=stdout \
  argoos-agent:latest
```

### Configuration

**Environment Variables**:
- `SERVER_URL`: Full URL of metrics endpoint (e.g., `https://example.com/api/v1/metrics`)
- `API_KEY`: Authentication key (generated in server, 64 hex chars)
- `HOST_LABEL`: Human-readable host name (defaults to system hostname)
- `COLLECT_INTERVAL`: Seconds between collections (default 30)
- `RETRY_ATTEMPTS`: Max HTTP retries with exponential backoff (default 3)
- `OUTPUT_FILE`: File path or `stdout` for file mode (default `/data/metrics.jsonl`)

**Mode Selection**:
- **HTTP mode**: Both `SERVER_URL` and `API_KEY` set → POST to server (authenticated)
- **File mode**: Neither set → write JSONL to `OUTPUT_FILE` (debugging/testing)
- **Mixed mode error**: Only one of `SERVER_URL`/`API_KEY` set → configuration error

### Metrics Collected

| Metric | Type | Description |
|--------|------|-------------|
| `collected_at` | ISO8601 | Timestamp of collection |
| `cpu_usage` | float | Overall CPU % (0–100) |
| `ram_used` | int | Used RAM bytes |
| `ram_total` | int | Total RAM bytes |
| `disk_read_bytes` | int | Bytes read since last interval (delta) |
| `disk_write_bytes` | int | Bytes written since last interval (delta) |
| `net_rx_bytes` | int | Bytes received since last interval (delta) |
| `net_tx_bytes` | int | Bytes transmitted since last interval (delta) |
| `load_avg_1` | float | 1-minute load average |
| `load_avg_5` | float | 5-minute load average |
| `load_avg_15` | float | 15-minute load average |
| `disk_partitions` | array | Mount point, total/used/free bytes per partition |

**Delta Calculations**: Disk/network values are deltas relative to the previous interval. On startup, the collector "primes" counters once, so the first reading reflects usage since agent start.

### Code Architecture

**main.go**:
1. Load config from env vars (`config.Load()`)
2. Create sender (HTTP or File based on config)
3. Create collector, prime it (pre-read counters)
4. Loop: collect → send → log every N seconds

**internal/config/config.go**:
- Struct `Config` with all env var fields
- `Load()` function validates required fields and returns config or error
- Defaults: HOST_LABEL (system hostname), COLLECT_INTERVAL (30), RETRY_ATTEMPTS (3), OUTPUT_FILE (`/data/metrics.jsonl`)

**internal/collector/collector.go**:
- `Collector` struct wraps gopsutil calls + counter state
- `Prime()` method pre-reads disk/network counters (no delta on first call)
- `Collect()` method returns `Metric` struct with current values
- Delta calculations for disk/network (subtract previous from current)

**internal/sender/sender.go**:
- `Sender` interface: `Send(m *collector.Metric) error`
- `HTTPSender`: POST JSON to server with `X-API-Key` header, retry logic (exponential backoff: 1s, 2s, 4s)
- `FileSender`: Write JSONL to file or stdout

**Payload Format** (JSON):
```json
{
  "collected_at": "2026-05-07T14:00:00Z",
  "cpu_usage": 23.4,
  "ram_used": 2147483648,
  "ram_total": 8589934592,
  "disk_read_bytes": 204800,
  "disk_write_bytes": 102400,
  "net_rx_bytes": 512000,
  "net_tx_bytes": 256000,
  "load_avg_1": 0.45,
  "load_avg_5": 0.38,
  "load_avg_15": 0.31,
  "disk_partitions": [
    { "mount": "/", "total": 107374182400, "used": 53687091200, "free": 53687091200 }
  ]
}
```

### Testing & Development

```bash
cd agent

# Update go.sum (without local Go 1.22 install)
docker run --rm -v "$(pwd)":/app -w /app golang:1.22-alpine go mod tidy

# Run in file mode for local testing
docker run --rm \
  -e HOST_LABEL=my-server \
  -e COLLECT_INTERVAL=5 \
  -e OUTPUT_FILE=stdout \
  argoos-agent:latest
```

---

## Key Architectural Decisions

### API Key Authentication (O(1) Lookup)

**Problem**: Validating API keys by bcrypt-comparing against all hosts is O(n).

**Solution**: Store bcrypt hash in `api_key` column, indexed prefix (first 12 chars) in `api_key_prefix`.
- Extract prefix from incoming key
- Query: `Host::where('api_key_prefix', $prefix)->first()` (indexed, returns ≤1 candidate)
- Verify: `password_verify($plaintext, $host->api_key)` (only once)
- Result: O(1) regardless of host count

### MySQL RANGE Partitioning

**Problem**: 30-day retention on 50 hosts = ~4.3M metrics rows. Regular DELETE is slow and fragments the table.

**Solution**: Partition `metrics` and `disk_partitions` by `UNIX_TIMESTAMP(collected_at)` monthly.
- New data auto-routed to correct partition
- Old partition cleanup: `ALTER TABLE metrics DROP PARTITION p_2024_01` (instant, metadata-only)
- Query pruning: SELECT on recent week only touches 1–2 partitions
- Constraint: PRIMARY KEY must include `collected_at` → `PRIMARY KEY (id, collected_at)`

### Async Processing with Horizon

**Problem**: Metric POSTs should return quickly without waiting for DB writes.

**Solution**: Controller validates and enqueues job, returns 201 immediately. Horizon worker processes batch.
- Benefits: High throughput (agent doesn't wait), decouples ingestion from storage
- Failure handling: Failed jobs retry with exponential backoff, logged to DB/dashboard

### Notification Channels (Telegram, Slack)

**Extensibility**: Custom notification channels registered in `AppServiceProvider`.
- `App\Channels\TelegramChannel` → implements `send(Notification, Notifiable)`
- `App\Channels\SlackChannel` → same interface
- Alert rules specify channels: `['telegram', 'slack', 'email']` (JSON array)
- Alert system notifies via selected channels when thresholds breached

---

## Common Development Workflows

### Adding a New Metric (Server)

1. Add field to `Metric` model fillable array
2. Update MetricController validation rules
3. Create migration: `php artisan make:migration add_new_metric_to_metrics_table`
4. Update collector.go to gather the metric
5. Add chart/display to Livewire dashboard component

### Adding an Alert Channel

1. Create class in `app/Channels/YourChannel.php` implementing `Illuminate\Notifications\Channel`
2. Register in `AppServiceProvider::boot()`
3. Add field to `alert_rules` (JSON or enum column)
4. Implement notification sending logic (API calls, credentials in .env)

### Debugging Agent Issues

1. Run in file mode with `OUTPUT_FILE=stdout` to inspect payload
2. Check config with `docker compose logs agent` (if running as container)
3. Verify server metrics endpoint: `curl -X POST http://localhost:8000/api/v1/metrics ... | jq`
4. Check Horizon queue in dashboard (/horizon) for failed jobs

### Local Development Against Docker Server

Agent needs to reach server API. On Docker Desktop Mac/Windows, use `host.docker.internal`:
```bash
SERVER_URL=http://host.docker.internal:8000/api/v1/metrics \
  (adjust port based on local dev setup)
```

---

## Performance & Scaling

- **Agent**: Stateless, minimal (~8 MB image, ~5 MB RAM). Cross-compile trivially: `GOOS=linux GOARCH=arm64 go build` (Raspberry Pi, ARM servers).
- **Server**: Scales with agent count. Partitioning keeps queries fast. Redis queues offload sync writes.
- **Database**: MySQL 8 with RANGE partitioning, indexed prefix lookup. 50 hosts × 2880 metrics/day (30-sec interval) × 30 days = ~4.3M rows (easily manageable).
- **Caching**: Redis for queue + cache. Metric aggregations cached by time range.
- **Retention**: Scheduled job drops expired MySQL partitions automatically (no manual cleanup needed).

---

## Roadmap (Phases)

- **Phase 1** (✓ Done): API, metrics ingestion, auth, Horizon
- **Phase 2** (✓ Done): Livewire dashboard, host CRUD, real-time metric display
- **Phase 3** (✓ Done): Historical charts (Chart.js), time range picker, aggregation + Redis caching
- **Phase 4** (✓ Done): Alert rules UI, evaluator, Telegram/Slack/email notifications
- **Phase 6** (✓ Done): Multi-disk views, top processes, HTTP uptime checks

**Ideas for future development**:
- Anomaly detection: automatic baseline + spike alerts without manual thresholds
- Scheduled reports: weekly PDF/email report per host

