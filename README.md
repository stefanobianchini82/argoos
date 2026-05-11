# Argoos

Self-hosted infrastructure monitoring system. A lightweight Go agent collects system metrics from each server and ships them to a central Laravel dashboard with alerting, real-time graphs, and Telegram/Slack notifications.

---

## Overview

Argoos is made of two independent components:

| Component | Language | Role |
|-----------|----------|------|
| **Server** | Laravel 13 (PHP 8.4) | Receives, stores, and displays metrics. Runs the dashboard, alert engine, and notification dispatch. |
| **Agent** | Go 1.22 | Collects CPU, RAM, disk, network metrics every N seconds and ships them to the server via HTTP. ~8 MB Docker image, zero persistent state. |

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
| Server framework | Laravel 13, PHP 8.4, Livewire |
| Database | MySQL 8.0 (RANGE partitioning) |
| Queue | Redis 7 + Laravel Horizon |
| Frontend | Tailwind CSS, Chart.js, Vite |
| Notifications | Telegram Bot API, Slack Incoming Webhooks |
| Containers | Docker Compose |

---

## Quick Start

All production images are published to GHCR. No local build required.

### Prerequisites

- Docker and Docker Compose
- Git

### 1. Clone the repository

```bash
git clone https://github.com/stefanobianchini82/argoos.git
cd argoos
```

### 2. Configure the environment

```bash
cp .env.example .env
```

Generate an application key and paste it into `APP_KEY`:

```bash
docker run --rm ghcr.io/stefanobianchini82/argoos-server:latest php artisan key:generate --show
```

Edit `.env` and set at minimum:

| Variable | Description |
|----------|-------------|
| `APP_KEY` | Generated above |
| `DB_PASSWORD` | MySQL user password |
| `DB_ROOT_PASSWORD` | MySQL root password |
| `DASHBOARD_PASSWORD` | Basic auth password for the dashboard |

### 3. Start the stack

```bash
docker compose up -d
```

This pulls and starts six services: `nginx` (port 8080), `app` (PHP-FPM), `mysql`, `redis`, `horizon`.

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
    'label'          => 'my-server',
    'ip'             => '192.168.1.10',
    'api_key'        => password_hash($key, PASSWORD_BCRYPT),
    'api_key_prefix' => substr($key, 0, 12),
]);

echo $key; // save this
```

### 6. Open the dashboard

```
http://<server-ip>:8080
```

Credentials: `DASHBOARD_USER` / `DASHBOARD_PASSWORD` from `.env` (default user: `admin`).

To start receiving metrics, deploy the agent on each host you want to monitor — see [Monitoring Additional Hosts](#monitoring-additional-hosts) below.

---

## Monitoring Hosts

To monitor servers, run the agent directly on each one. No server-side components needed.

```bash
docker run -d --restart unless-stopped \
  -e SERVER_URL=http://<server-ip>:8080/api/v1/metrics \
  -e API_KEY=<key-generated-for-this-host> \
  -e HOST_LABEL=web-server-1 \
  -e COLLECT_INTERVAL=30 \
  ghcr.io/stefanobianchini82/argoos-agent:latest
```

Repeat steps 5–6 of the Quick Start (via tinker) to generate a separate API key for each host.

### Agent configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `SERVER_URL` | Metrics endpoint URL | — |
| `API_KEY` | Authentication key for this host | — |
| `HOST_LABEL` | Human-readable host name | system hostname |
| `COLLECT_INTERVAL` | Seconds between collections | `30` |
| `RETRY_ATTEMPTS` | Max HTTP retries (exponential backoff) | `3` |

---

## Server Configuration

### Environment variables (`.env`)

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_KEY` | Laravel application key | — |
| `DB_DATABASE` | Database name | `argoos` |
| `DB_USERNAME` | MySQL user | `argoos` |
| `DB_PASSWORD` | MySQL password | — |
| `DB_ROOT_PASSWORD` | MySQL root password | — |
| `DASHBOARD_USER` | Basic auth username | `admin` |
| `DASHBOARD_PASSWORD` | Basic auth password | — |
| `TELEGRAM_BOT_TOKEN` | Telegram Bot token for alerts | — |

### Docker services

| Service | Image | Exposed port |
|---------|-------|-------------|
| `nginx` | nginx:1.27-alpine | 8080 → 80 |
| `app` | ghcr.io/…/argoos-server:latest | — |
| `horizon` | ghcr.io/…/argoos-server:latest | — |
| `mysql` | mysql:8.0 | 3306 |
| `redis` | redis:7-alpine | — |

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
curl -s -X POST http://<server-ip>:8080/api/v1/metrics \
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

## Development

The following instructions are for contributors building from source.

### Server (local, without Docker)

```bash
cd server
composer install
cp .env.example .env
# Set DB_CONNECTION=sqlite, DB_DATABASE=database/database.sqlite
touch database/database.sqlite
php artisan key:generate
php artisan migrate
composer run dev   # starts Artisan, Horizon, Pail, and Vite concurrently
```

### Running tests

The server uses [PEST](https://pestphp.com/) (89 tests, SQLite in-memory).

```bash
cd server
./vendor/bin/pest

# In Docker:
docker compose exec app ./vendor/bin/pest
```

### Building the agent image

```bash
cd agent
docker build -t argoos-agent:latest .
# Result: ~8 MB image (FROM scratch, static binary + CA certs)
```

### Debugging agent output (file mode)

```bash
docker run --rm \
  -e HOST_LABEL=my-server \
  -e COLLECT_INTERVAL=10 \
  -e OUTPUT_FILE=stdout \
  argoos-agent:latest
```

---

## Roadmap

| Phase | Status | Description |
|-------|--------|-------------|
| 1 | ✅ Done | API ingestion, auth, MySQL partitioning, Horizon queue |
| 2 | ✅ Done | Livewire dashboard: host list, per-host metrics, disk partitions |
| 3 | ✅ Done | Historical Chart.js graphs, time range selector |
| 4 | ✅ Done | Alert rules, threshold evaluator, Telegram & Slack notifications |
| 5 | ✅ Done | Agent image on GHCR, full Docker Compose with agent service, API docs |
| 6 | Pending | Multi-disk views, top processes, HTTP uptime checks |

---

## License

MIT
