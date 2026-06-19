<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContainerMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'host_id',
        'container_id',
        'container_name',
        'image',
        'cpu_percent',
        'memory_usage',
        'memory_limit',
        'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'cpu_percent'  => 'float',
        'memory_usage' => 'integer',
        'memory_limit' => 'integer',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }
}
