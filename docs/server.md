# Argoos Server — Phase 1

Server Laravel 13 per la raccolta dati dagli agenti. Nessuna interfaccia grafica (Phase 2+).

---

## Database

**MySQL 8 con RANGE partitioning mensile.**

Con retention a 30 giorni il volume massimo è circa **4.3 M righe** (50 host × 2880 righe/giorno × 30 giorni). MySQL 8 con indice composto su `(host_id, collected_at)` gestisce questo volume senza problemi.

La tabella `metrics` e `disk_partitions` sono partizionate con `PARTITION BY RANGE (UNIX_TIMESTAMP(collected_at))`. Il future cleanup job userà `ALTER TABLE DROP PARTITION` — operazione istantanea sui metadati, non una `DELETE` lenta su milioni di righe.

> **Nota:** MySQL non supporta foreign key su tabelle RANGE-partizionate. L'integrità referenziale su `host_id` è garantita dal middleware `AuthenticateAgent`, che verifica l'host prima di ogni inserimento.

---

## Struttura

```
server/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   └── MetricController.php       # POST /api/v1/metrics
│   │   └── Middleware/
│   │       └── AuthenticateAgent.php      # valida X-API-Key
│   ├── Jobs/
│   │   └── ProcessMetricBatch.php         # persistenza asincrona via Horizon
│   └── Models/
│       ├── Host.php
│       ├── Metric.php
│       └── DiskPartition.php
├── database/migrations/
│   ├── 0001_01_01_000000_create_hosts_table.php
│   ├── 0001_01_01_000001_create_metrics_table.php
│   └── 0001_01_01_000002_create_disk_partitions_table.php
├── routes/
│   └── api.php
├── docker/
│   ├── php/Dockerfile                     # PHP-FPM 8.3-alpine
│   └── nginx/default.conf
└── docker-compose.yml
```

---

## API

| Method | URI | Auth | Risposta |
|---|---|---|---|
| `POST` | `/api/v1/metrics` | `X-API-Key` | `202 Accepted` |

### Payload (inviato dall'agente)

```json
{
  "collected_at": "2026-05-08T10:00:00Z",
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

Il controller risponde `202 Accepted` immediatamente. La scrittura su DB avviene in modo asincrono tramite il job `ProcessMetricBatch` processato da Horizon.

---

## Autenticazione

Ogni host dispone di una chiave API univoca. Il middleware `AuthenticateAgent` legge l'header `X-API-Key` e verifica la chiave con bcrypt.

Per rendere la verifica O(1) indipendentemente dal numero di host, la tabella `hosts` include una colonna `api_key_prefix` (i primi 12 caratteri della chiave in chiaro, indicizzata). La ricerca avviene prima sul prefix, poi `password_verify()` viene chiamato su un solo candidato.

```
X-API-Key: <chiave in chiaro 64 char>
            └─→ WHERE api_key_prefix = '<primi 12 char>'
                      └─→ password_verify(chiave, hash bcrypt)
```

---

## Stack Docker

| Servizio | Immagine | Porta esposta |
|---|---|---|
| `app` | PHP-FPM 8.3-alpine | — |
| `nginx` | nginx:1.27-alpine | `8080:80` |
| `mysql` | mysql:8.0 | `3306:3306` |
| `redis` | redis:7-alpine | — |
| `horizon` | PHP-FPM 8.3-alpine | — |

---

## Avvio

### 1. Configurazione

```bash
cp server/.env.example server/.env
# Modificare almeno DB_PASSWORD e DB_ROOT_PASSWORD
```

### 2. Build e avvio container

```bash
cd server
docker compose up --build -d
```

### 3. Migrazioni

```bash
docker compose exec app php artisan migrate
```

### 4. Registrare il primo host

```bash
docker compose exec app php artisan tinker
```

```php
$key = bin2hex(random_bytes(32)); // chiave da 64 caratteri hex

App\Models\Host::create([
    'label'          => 'server1',
    'ip'             => '192.168.1.10',
    'api_key'        => password_hash($key, PASSWORD_BCRYPT),
    'api_key_prefix' => substr($key, 0, 12),
]);

echo $key; // conservare questa chiave per configurare l'agente
```

### 5. Verifica

```bash
# Deve restituire 401
curl -s -X POST http://localhost:8080/api/v1/metrics \
  -H 'X-API-Key: chiave-sbagliata' | jq

# Deve restituire 202 (sostituire <KEY> con la chiave generata al passo 4)
curl -s -X POST http://localhost:8080/api/v1/metrics \
  -H 'Content-Type: application/json' \
  -H 'X-API-Key: <KEY>' \
  -d '{
    "collected_at": "2026-05-08T10:00:00Z",
    "cpu_usage": 12.5,
    "ram_used": 1073741824,
    "ram_total": 8589934592,
    "disk_read_bytes": 0,
    "disk_write_bytes": 0,
    "net_rx_bytes": 0,
    "net_tx_bytes": 0,
    "load_avg_1": 0.1,
    "load_avg_5": 0.1,
    "load_avg_15": 0.1,
    "disk_partitions": [
      { "mount": "/", "total": 107374182400, "used": 10737418240, "free": 96636764160 }
    ]
  }' | jq

# Verificare le righe inserite da Horizon
docker compose exec mysql mysql -u argoos -psecret argoos \
  -e "SELECT id, host_id, collected_at, cpu_usage FROM metrics LIMIT 5;"
```

---

## Variabili d'ambiente principali

| Variabile | Descrizione | Default |
|---|---|---|
| `DB_HOST` | Host MySQL | `mysql` |
| `DB_DATABASE` | Nome database | `argoos` |
| `DB_USERNAME` | Utente MySQL | `argoos` |
| `DB_PASSWORD` | Password MySQL | — |
| `DB_ROOT_PASSWORD` | Password root MySQL | — |
| `REDIS_HOST` | Host Redis | `redis` |
| `QUEUE_CONNECTION` | Driver code | `redis` |

---

## Roadmap server

| Phase | Stato | Descrizione |
|---|---|---|
| **1** | ✅ Done | API raccolta dati, modelli, autenticazione, Horizon |
| **2** | Pending | Dashboard Livewire minimale: lista host, last-seen, ultimi valori |
| **3** | Pending | Grafici storici Chart.js, time range selector, MetricAggregator + Redis cache |
| **4** | Pending | Alert rules UI, AlertEvaluator, notifiche Telegram / email |
| **5** | Pending | Docker Compose completo, agent image su GHCR, docs API |
| **6** | Pending | Vista multi-disco, top processes, HTTP uptime checks |
