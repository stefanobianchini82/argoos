<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessMemory extends Model
{
    public $timestamps = false;

    protected $table = 'process_memory';

    protected $fillable = [
        'host_id',
        'pid',
        'name',
        'mem_rss',
        'cpu_percent',
        'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'pid'          => 'integer',
        'mem_rss'      => 'integer',
        'cpu_percent'  => 'float',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }
}
