<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    public const METRICS = [
        'cpu_usage'        => 'CPU Usage (%)',
        'ram_used'         => 'RAM Used (bytes)',
        'disk_read_bytes'  => 'Disk Read (bytes/s)',
        'disk_write_bytes' => 'Disk Write (bytes/s)',
        'net_rx_bytes'     => 'Network RX (bytes/s)',
        'net_tx_bytes'     => 'Network TX (bytes/s)',
        'load_avg_1'       => 'Load Avg 1m',
        'load_avg_5'       => 'Load Avg 5m',
        'load_avg_15'      => 'Load Avg 15m',
    ];

    public const OPERATORS = ['>', '<', '>=', '<='];

    public const CHANNELS = ['email', 'telegram', 'webhook'];

    protected $fillable = [
        'host_id',
        'metric',
        'operator',
        'threshold',
        'duration_minutes',
        'channel',
        'channel_target',
        'is_active',
        'last_notified_at',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'last_notified_at' => 'datetime',
        'threshold'        => 'float',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }

    public function alertEvents(): HasMany
    {
        return $this->hasMany(AlertEvent::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function metricLabel(): string
    {
        return self::METRICS[$this->metric] ?? $this->metric;
    }
}
