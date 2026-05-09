# Argoos

Self-hosted infrastructure monitoring system. A lightweight Go agent collects system metrics from each server and ships them to a central Laravel dashboard with alerting, real-time graphs, and Telegram/Slack notifications.

---

## Overview

Argoos is made of two independent components:

| Component | Language | Role |
|-----------|----------|------|
| **Server** | Laravel 13 (PHP 8.3) | Receives, stores, and displays metrics. Runs the dashboard, alert engine, and notification dispatch. |
| **Agent** | Go 1.22 | Collects CPU, RAM, disk, network metrics every N seconds and ships them to the server via HTTP. ~8 MB Docker image, zero persistent state. |

### How it works

```
[Server A]  argoos-agent ──┐
[Server B]  argoos-agent ──┤──► POST /api/v1/metrics  ──► Laravel ──► MySQL (partitioned)
[Server C]  argoos-agent ──┘         (X-API-Key)              └──► Horizon (async jobs)
                                                                       └──► Alerts → Telegram / Slack
```

---

## Features

- **Real-time dashboard** — host status grid, per-host metrics, disk partitions, load averages
- **Historical charts** — Chart.js graphs with configurable time range
- **Alert rules** — threshold-based alerts (CPU, RAM, disk) per host, dispatched via Telegram or Slack
- **Offline detection** — hosts silent for >3 minutes trigger an offline notification
- **Async ingestion** — metrics are queued via Redis/Horizon; the agent never waits on DB writes
- **Efficient storage** — MySQL RANGE partitioning by month; old partitions are dropped instantly (`ALTER TABLE DROP PARTITION`)
- **O(1) auth** — API key prefix index means host lookup is a single indexed read regardless of fleet size
- **Tiny agent** — static Go binary, `FROM scratch` Docker image (~8 MB), cross-compiles to any Linux arch

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Agent | Go 1.22, gopsutil, Docker (scratch image) |
| Server framework | Laravel 13, PHP 8.3, Livewire |
| Database | MySQL 8.0 (RANGE partitioning) |
| Queue | Redis 7 + Laravel Horizon |
| Frontend | Tailwind CSS, Chart.js, Vite |
| Notifications | Telegram Bot API, Slack Incoming Webhooks |
| Containers | Docker Compose |

---

## Quick Start

### Prerequisites

- Docker and Docker Compose
- Git

### 1. Clone the repository

```bash
git clone https://github.com/stefanobianchini82/argoos.git
cd argoos
```

### 2. Configure the server

```bash
cd server
cp .env.example .env
# Set at minimum: DB_PASSWORD, DB_ROOT_PASSWORD, DASHBOARD_PASSWORD
```

### 3. Start the stack

```bash
docker compose up --build -d
```

This starts five services: `nginx` (port 8080), `app` (PHP-FPM), `mysql`, `redis`, and `horizon`.

### 4. Run migrations

```bash
docker compose exec app php artisan migrate
```

### 5. Register the first host

```bash
docker compose exec app php artisan tinker
```

```php
$key = bin2hex(random_bytes(32));

App\Models\Host::create([
    'label'          => 'server1',
    'ip'             => '192.168.1.10',
    'api_key'        => password_hash($key, PASSWORD_BCRYPT),
    'api_key_prefix' => substr($key, 0, 12),
]);

echo $key; // save this — you'll need it for the agent
```

### 6. Run the agent on the host you want to monitor

```bash
docker run --rm \
  -e SERVER_URL=http://your-server:8080/api/v1/metrics \
  -e API_KEY=<key-from-step-5> \
  -e HOST_LABEL=server1 \
  -e COLLECT_INTERVAL=30 \
  ghcr.io/stefanobianchini82/argoos-agent:latest
```

**Dashboard**: [http://localhost:8080](http://localhost:8080) (credentials from `DASHBOARD_USER` / `DASHBOARD_PASSWORD` in `.env`)

---

## Server Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | MySQL host | `mysql` |
| `DB_DATABASE` | Database name | `argoos` |
| `DB_USERNAME` | MySQL user | `argoos` |
| `DB_PASSWORD` | MySQL password | — |
| `DB_ROOT_PASSWORD` | MySQL root password | — |
| `REDIS_HOST` | Redis host | `redis` |
| `QUEUE_CONNECTION` | Queue driver | `redis` |
| `DASHBOARD_USER` | Basic auth username | `admin` |
| `DASHBOARD_PASSWORD` | Basic auth password | — |
| `TELEGRAM_BOT_TOKEN` | Telegram Bot token for alerts | — |
| `SLACK_WEBHOOK_URL` | Slack Incoming Webhook URL | — |

### Docker Services

| Service | Image | Exposed Port |
|---------|-------|-------------|
| `nginx` | nginx:1.27-alpine | 8080 → 80 |
| `app` | PHP-FPM 8.3-alpine | — |
| `mysql` | mysql:8.0 | 3306 |
| `redis` | redis:7-alpine | — |
| `horizon` | PHP-FPM 8.3-alpine | — |

### Local Development (without Docker)

```bash
cd server
composer install
cp .env.example .env
# Set DB_CONNECTION=sqlite, DB_DATABASE=database/database.sqlite
touch database/database.sqlite
php artisan key:generate
php artisan migrate
composer run dev   # starts Artisan, Horizon, Pail (log viewer), and Vite concurrently
```

### Running Tests

The server uses [PEST](https://pestphp.com/) as its test runner (89 tests, SQLite in-memory).

```bash
cd server

# Run all tests
./vendor/bin/pest

# Unit tests only
./vendor/bin/pest tests/Unit

# Feature tests only
./vendor/bin/pest tests/Feature

# Run a specific test file
./vendor/bin/pest tests/Feature/Api/AuthenticateAgentTest.php

# With code coverage (requires Xdebug or PCOV)
./vendor/bin/pest --coverage

# In Docker:
docker compose exec app ./vendor/bin/pest
```

---

## Agent Configuration

The agent is configured entirely via environment variables. No config files, no persistent state.

| Variable | Description | Default |
|----------|-------------|---------|
| `SERVER_URL` | Metrics endpoint URL | — |
| `API_KEY` | Authentication key for this host | — |
| `HOST_LABEL` | Human-readable host name | system hostname |
| `COLLECT_INTERVAL` | Seconds between collections | `30` |
| `RETRY_ATTEMPTS` | Max HTTP retries (exponential backoff) | `3` |
| `OUTPUT_FILE` | **File mode only** — path or `stdout` | `/data/metrics.jsonl` |

**Mode selection** is automatic:
- Both `SERVER_URL` + `API_KEY` set → **HTTP mode** (POST to server)
- Neither set → **File mode** (write JSONL to `OUTPUT_FILE`)
- Only one set → configuration error

### Building the Agent

```bash
cd agent
docker build -t argoos-agent:latest .
# Result: ~8 MB image (FROM scratch, static binary + CA certs)
```

### Debugging with File Mode

Inspect collected metrics without a running server:

```bash
docker run --rm \
  -e HOST_LABEL=my-server \
  -e COLLECT_INTERVAL=10 \
  -e OUTPUT_FILE=stdout \
  argoos-agent:latest
```

---

## Metrics Collected

| Metric | Description |
|--------|-------------|
| `cpu_usage` | Overall CPU usage (%) |
| `ram_used` / `ram_total` | RAM in bytes |
| `disk_read_bytes` | Disk bytes read since last interval |
| `disk_write_bytes` | Disk bytes written since last interval |
| `net_rx_bytes` | Network bytes received since last interval |
| `net_tx_bytes` | Network bytes transmitted since last interval |
| `load_avg_1/5/15` | System load averages |
| `disk_partitions` | Per-partition total, used, free bytes |

Disk and network values are **deltas** relative to the previous interval.

---

## API

### `POST /api/v1/metrics`

Authenticated via `X-API-Key` header. Returns `202 Accepted` immediately; persistence is handled asynchronously by Horizon.

```bash
curl -s -X POST http://localhost:8080/api/v1/metrics \
  -H 'Content-Type: application/json' \
  -H 'X-API-Key: YOUR_KEY' \
  -d '{
    "collected_at": "2026-05-09T10:00:00Z",
    "cpu_usage": 12.5,
    "ram_used": 1073741824,
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
  }'
```

---

## Architecture Notes

**API key auth in O(1)**: Each host stores a bcrypt hash (`api_key`) and an indexed 12-character prefix (`api_key_prefix`). Incoming requests are matched by prefix first (single indexed read), then verified with `password_verify()` against exactly one candidate — constant time regardless of fleet size.

**MySQL RANGE partitioning**: The `metrics` and `disk_partitions` tables are partitioned by `UNIX_TIMESTAMP(collected_at)` monthly. Retention cleanup is an instant metadata operation (`ALTER TABLE DROP PARTITION`) rather than a slow `DELETE` across millions of rows.

**Async ingestion**: The API controller validates the payload and returns `202` immediately. A Horizon job handles the DB write asynchronously, keeping agent POST latency low even under load.

---

## Roadmap

| Phase | Status | Description |
|-------|--------|-------------|
| 1 | ✅ Done | API ingestion, auth, MySQL partitioning, Horizon queue |
| 2 | ✅ Done | Livewire dashboard: host list, per-host metrics, disk partitions |
| 3 | ✅ Done | Historical Chart.js graphs, time range selector |
| 4 | ✅ Done | Alert rules, threshold evaluator, Telegram & Slack notifications |
| 5 | Pending | Agent image on GHCR, full Docker Compose with agent service, API docs |
| 6 | Pending | Multi-disk views, top processes, HTTP uptime checks |

---

## License

MIT
