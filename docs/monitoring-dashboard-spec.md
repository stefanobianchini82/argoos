# Argoos — Project Specification

> Self-hosted monitoring dashboard with a centralised Laravel server and lightweight Go agents.
> The name **Argoos** is a nod to Argus, the hundred-eyed giant of Greek mythology — always watching.

## Overview

A self-hosted, centralised monitoring system composed of two parts:

- **Server**: a Laravel application that receives, stores, and visualises metrics, manages alerts and sends notifications.
- **Agent** (`argoos-agent`): a minimal Go binary compiled into a Docker image (`FROM scratch`, ~8 MB) installed on each machine to be monitored, which periodically reads system metrics and sends them to the server.

---

## Architecture

```
┌─────────────────────────────────────────────────────┐
│                    CENTRAL SERVER                   │
│                                                     │
│  ┌─────────────┐  ┌──────────┐  ┌───────────────┐  │
│  │ Laravel API │  │ Laravel  │  │   Dashboard   │  │
│  │  + Queue    │  │ Horizon  │  │   (Livewire)  │  │
│  └──────┬──────┘  └──────────┘  └───────────────┘  │
│         │                                           │
│  ┌──────▼──────┐  ┌──────────┐                     │
│  │    MySQL    │  │  Redis   │                     │
│  │  (metrics)  │  │ (cache + │                     │
│  │             │  │  queue)  │                     │
│  └─────────────┘  └──────────┘                     │
└─────────────────────────────────────────────────────┘
         ▲                    ▲
         │  HTTPS + API Key   │
    ┌────┴────┐          ┌────┴────┐
    │  AGENT  │          │  AGENT  │
    │ server1 │          │ server2 │
    │(Docker) │          │(Docker) │
    └─────────┘          └─────────┘
```

Communication between agent and server happens over HTTPS. Each agent authenticates via a per-host `X-API-Key` header.

---

## Repository Structure

Single **monorepo** — agent and server are versioned together, share the same changelog and issue tracker.

```
argoos/
├── agent/                      # Go agent (argoos-agent)
│   ├── cmd/agent/main.go
│   ├── internal/
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── go.mod
│   └── README.md
├── server/                     # Laravel server (argoos-server)
│   ├── app/
│   ├── docker/
│   ├── docker-compose.yml
│   └── README.md
├── docs/
│   ├── getting-started.md
│   ├── configuration.md
│   └── alert-channels.md
├── .github/
│   └── workflows/
│       ├── agent.yml           # Go build + Docker push to GHCR
│       └── server.yml          # Laravel tests + lint
├── docker-compose.yml          # full stack for local development
├── CHANGELOG.md
├── CONTRIBUTING.md
└── README.md                   # main README with Argoos logo
```

---

### Responsibilities

- Collect system metrics every N seconds (configurable, default 30s)
- Send metrics via `POST /api/v1/metrics` to the central server
- Retry on failure with exponential backoff
- No persistent storage — fire and forget

### Technology

- **Language**: Go 1.22
- **Key library**: `gopsutil` — Go port of psutil, identical metrics coverage
- **Binary**: single static binary, no external dependencies (`CGO_ENABLED=0`)
- **Base image**: `FROM scratch` — final image ~8 MB, RAM at rest ~5 MB
- **Cross-compile**: trivial (`GOOS=linux GOARCH=arm64` for Raspberry Pi / ARM servers)
- **Config**: environment variables only

### Environment Variables

| Variable | Description | Default |
|---|---|---|
| `SERVER_URL` | Full URL of the central server | required |
| `API_KEY` | Authentication key for this host | required |
| `HOST_LABEL` | Human-readable name for this host | hostname |
| `COLLECT_INTERVAL` | Seconds between collections | `30` |
| `RETRY_ATTEMPTS` | Max retries on send failure | `3` |

### Directory Structure

```
agent/
├── Dockerfile
├── docker-compose.yml
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
    │   └── sender.go           # HTTP POST to server with retry
    └── config/
        └── config.go           # loads and validates env vars
```

### Metrics Collected

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

### Payload Format

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
    { "mount": "/", "total": 107374182400, "used": 53687091200, "free": 53687091200 },
    { "mount": "/data", "total": 214748364800, "used": 107374182400, "free": 107374182400 }
  ]
}
```

### Dockerfile (Agent)

```dockerfile
FROM golang:1.22-alpine AS builder
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 GOOS=linux go build -ldflags="-s -w" -o argoos-agent ./cmd/agent

FROM scratch
COPY --from=builder /app/argoos-agent /argoos-agent
ENTRYPOINT ["/argoos-agent"]
```

Final image size: **~8 MB**. RAM at rest: **~5 MB**.

---

## Server (Laravel)

### Technology Stack

- **Framework**: Laravel 11
- **Queue**: Laravel Horizon + Redis
- **Frontend**: Livewire v3 + Alpine.js + Chart.js
- **Database**: MySQL 8 (metrics storage)
- **Cache / Queue broker**: Redis 7

### Directory Structure

```
server/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── MetricController.php       # receives agent data
│   │   │   │   └── HostController.php         # host registration / management
│   │   │   └── Dashboard/
│   │   │       ├── DashboardController.php    # overview page
│   │   │       └── HostDetailController.php   # per-host detail page
│   │   └── Middleware/
│   │       └── AuthenticateAgent.php          # validates X-API-Key
│   ├── Jobs/
│   │   ├── ProcessMetricBatch.php             # persists incoming metrics
│   │   └── CheckAlertRules.php                # scheduled: evaluates thresholds
│   ├── Notifications/
│   │   ├── AlertTriggered.php                 # Telegram / Email / Webhook
│   │   └── HostOffline.php                    # fired when last_seen_at is stale
│   ├── Models/
│   │   ├── Host.php
│   │   ├── Metric.php
│   │   ├── DiskPartition.php
│   │   └── AlertRule.php
│   └── Services/
│       ├── MetricAggregator.php               # computes avg/min/max for charts
│       └── AlertEvaluator.php                 # threshold evaluation logic
├── resources/
│   └── views/
│       └── livewire/
│           ├── dashboard-overview.blade.php
│           ├── host-card.blade.php
│           ├── host-detail.blade.php
│           ├── metric-chart.blade.php
│           └── alert-rule-form.blade.php
└── docker-compose.yml
```

### Database Schema

```sql
-- Registered hosts
CREATE TABLE hosts (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label         VARCHAR(100) NOT NULL,
    ip            VARCHAR(45),
    api_key       VARCHAR(64) NOT NULL UNIQUE,  -- stored hashed (bcrypt)
    last_seen_at  TIMESTAMP NULL,
    created_at    TIMESTAMP,
    updated_at    TIMESTAMP
);

-- Time-series metrics (append-only)
CREATE TABLE metrics (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    host_id           BIGINT UNSIGNED NOT NULL,
    collected_at      TIMESTAMP NOT NULL,
    cpu_usage         FLOAT,
    ram_used          BIGINT UNSIGNED,
    ram_total         BIGINT UNSIGNED,
    disk_read_bytes   BIGINT UNSIGNED,
    disk_write_bytes  BIGINT UNSIGNED,
    net_rx_bytes      BIGINT UNSIGNED,
    net_tx_bytes      BIGINT UNSIGNED,
    load_avg_1        FLOAT,
    load_avg_5        FLOAT,
    load_avg_15       FLOAT,
    INDEX idx_host_collected (host_id, collected_at),
    FOREIGN KEY (host_id) REFERENCES hosts(id) ON DELETE CASCADE
) PARTITION BY RANGE (UNIX_TIMESTAMP(collected_at)) (
    -- partitions to be added monthly via scheduled job
    PARTITION p_initial VALUES LESS THAN MAXVALUE
);

-- Per-host disk partitions snapshot
CREATE TABLE disk_partitions (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    host_id      BIGINT UNSIGNED NOT NULL,
    mount_point  VARCHAR(255) NOT NULL,
    total        BIGINT UNSIGNED,
    used         BIGINT UNSIGNED,
    free         BIGINT UNSIGNED,
    collected_at TIMESTAMP NOT NULL,
    INDEX idx_host_collected (host_id, collected_at),
    FOREIGN KEY (host_id) REFERENCES hosts(id) ON DELETE CASCADE
);

-- Alert rules per host
CREATE TABLE alert_rules (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    host_id            BIGINT UNSIGNED NOT NULL,
    metric             VARCHAR(50) NOT NULL,      -- e.g. "cpu_usage", "ram_used"
    operator           ENUM('>', '<', '>=', '<=') NOT NULL,
    threshold          FLOAT NOT NULL,
    duration_minutes   INT NOT NULL DEFAULT 5,    -- must persist for N minutes
    channel            ENUM('telegram', 'email', 'webhook') NOT NULL,
    channel_target     VARCHAR(255) NOT NULL,     -- chat_id, email address, or URL
    is_active          BOOLEAN NOT NULL DEFAULT TRUE,
    last_notified_at   TIMESTAMP NULL,
    FOREIGN KEY (host_id) REFERENCES hosts(id) ON DELETE CASCADE
);

-- Alert event log
CREATE TABLE alert_events (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alert_rule_id   BIGINT UNSIGNED NOT NULL,
    triggered_at    TIMESTAMP NOT NULL,
    resolved_at     TIMESTAMP NULL,
    peak_value      FLOAT,
    FOREIGN KEY (alert_rule_id) REFERENCES alert_rules(id) ON DELETE CASCADE
);
```

### API Endpoints

| Method | URI | Auth | Description |
|---|---|---|---|
| `POST` | `/api/v1/metrics` | API Key | Agent posts metric payload |
| `GET` | `/api/v1/hosts` | Bearer token | List all hosts |
| `POST` | `/api/v1/hosts` | Bearer token | Register a new host |
| `DELETE` | `/api/v1/hosts/{id}` | Bearer token | Remove a host |

The agent uses `X-API-Key: <key>` header. The management API (if exposed) uses Laravel Sanctum tokens.

### Data Flow

```
Agent (every 30s)
  └─→ POST /api/v1/metrics  { collected_at, cpu_usage, ram_used, ... }
        └─→ MetricController
              ├─→ AuthenticateAgent middleware (validates API key, updates last_seen_at)
              └─→ dispatch(ProcessMetricBatch)
                    └─→ Horizon queue worker
                          ├─→ INSERT into metrics
                          └─→ INSERT into disk_partitions

Scheduler (every 1 min)
  └─→ CheckAlertRules::dispatch()
        └─→ AlertEvaluator: for each active rule
              ├─→ query avg metric value over last N minutes
              ├─→ if threshold exceeded → fire Notification
              └─→ if previously triggered + now resolved → update alert_events.resolved_at

Scheduler (every 1 min)
  └─→ check hosts.last_seen_at > 3 minutes → dispatch HostOffline notification
```

### Docker Compose (Server)

```yaml
version: '3.9'

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=production
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:1.25-alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: monitoring
      MYSQL_USER: app
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data

  horizon:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    command: php artisan horizon
    depends_on:
      - redis
      - mysql

  scheduler:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    command: sh -c "while true; do php artisan schedule:run; sleep 60; done"
    depends_on:
      - mysql
      - redis

volumes:
  mysql_data:
  redis_data:
```

---

## Dashboard (Livewire)

### Pages

| Page | Description |
|---|---|
| `/` | Overview: status cards for all hosts, last-seen badge, quick metrics |
| `/hosts/{id}` | Detail: charts for CPU, RAM, disk I/O, network over selectable time ranges |
| `/hosts/{id}/alerts` | Configure alert rules for the host |
| `/events` | Global alert event log |

### Chart Time Ranges

- Last 1 hour (raw data, 30s resolution)
- Last 6 hours (averaged per 5 min)
- Last 24 hours (averaged per 15 min)
- Last 7 days (averaged per 1 hour)

Aggregation handled by `MetricAggregator` service, results cached in Redis with appropriate TTL.

---

## Notification Channels

| Channel | Implementation |
|---|---|
| **Telegram** | HTTP call to Bot API via Laravel Notification |
| **Email** | Laravel `Mail` via configured SMTP |
| **Webhook** | HTTP POST with JSON payload to arbitrary URL |

---

## Development Roadmap

| Phase | Deliverable |
|---|---|
| **1** | Go agent: collector + sender. Laravel API endpoint that receives and persists metrics. Authentication middleware. |
| **2** | Minimal Livewire dashboard: host list, last-seen status, latest metric values (no charts). |
| **3** | Historical charts with Chart.js. Time range selector. MetricAggregator service with Redis caching. |
| **4** | Alert rules UI. AlertEvaluator scheduler job. Telegram + email notifications. |
| **5** | Full Docker Compose for server. Agent Docker image published to GHCR. README and API docs. |
| **6** | Multi-disk detail view. Top processes endpoint (optional agent module). HTTP uptime checks. |

---

## Non-Goals (v1)

- No user authentication / multi-user support (single admin, local network assumed)
- No ClickHouse or time-series DB (MySQL with partitioning is sufficient for tens of hosts)
- No agent auto-update mechanism
- No TLS termination inside the stack (assumed handled by external reverse proxy)