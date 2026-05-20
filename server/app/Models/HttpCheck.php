<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HttpCheck extends Model
{
    use HasFactory;

    public const CHANNELS = ['email', 'telegram', 'webhook', 'slack'];
    public const METHODS  = ['GET', 'HEAD', 'POST'];

    protected $fillable = [
        'host_id',
        'label',
        'url',
        'method',
        'timeout_seconds',
        'expected_status_code',
        'keyword_match',
        'channel',
        'channel_target',
        'is_active',
        'last_notified_at',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'last_notified_at' => 'datetime',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }

    public function httpCheckEvents(): HasMany
    {
        return $this->hasMany(HttpCheckEvent::class);
    }

    public function openEvent(): HasOne
    {
        return $this->hasOne(HttpCheckEvent::class)
            ->whereNull('resolved_at')
            ->latestOfMany('triggered_at');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
