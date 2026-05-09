<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'alert_rule_id',
        'triggered_at',
        'resolved_at',
        'peak_value',
        'trigger_context',
    ];

    protected $casts = [
        'triggered_at'    => 'datetime',
        'resolved_at'     => 'datetime',
        'peak_value'      => 'float',
        'trigger_context' => 'array',
    ];

    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
