<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HttpCheckEvent extends Model
{
    protected $fillable = [
        'http_check_id',
        'is_up',
        'status_code',
        'response_ms',
        'triggered_at',
        'resolved_at',
        'context',
    ];

    protected $casts = [
        'is_up'        => 'boolean',
        'triggered_at' => 'datetime',
        'resolved_at'  => 'datetime',
        'context'      => 'array',
    ];

    public function httpCheck(): BelongsTo
    {
        return $this->belongsTo(HttpCheck::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
