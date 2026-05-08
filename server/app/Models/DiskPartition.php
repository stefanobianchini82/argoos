<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiskPartition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'host_id',
        'mount_point',
        'total',
        'used',
        'free',
        'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'total'        => 'integer',
        'used'         => 'integer',
        'free'         => 'integer',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }
}
