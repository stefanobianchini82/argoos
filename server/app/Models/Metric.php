<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'host_id',
        'collected_at',
        'cpu_usage',
        'ram_used',
        'ram_total',
        'disk_read_bytes',
        'disk_write_bytes',
        'net_rx_bytes',
        'net_tx_bytes',
        'load_avg_1',
        'load_avg_5',
        'load_avg_15',
        'uptime_seconds',
    ];

    protected $casts = [
        'collected_at'     => 'datetime',
        'cpu_usage'        => 'float',
        'ram_used'         => 'integer',
        'ram_total'        => 'integer',
        'disk_read_bytes'  => 'integer',
        'disk_write_bytes' => 'integer',
        'net_rx_bytes'     => 'integer',
        'net_tx_bytes'     => 'integer',
        'load_avg_1'       => 'float',
        'load_avg_5'       => 'float',
        'load_avg_15'      => 'float',
        'uptime_seconds'   => 'integer',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }
}
