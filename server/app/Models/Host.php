<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\AlertRule;
use Spatie\Tags\HasTags;

class Host extends Model
{
    use HasFactory, HasTags;
    protected $fillable = [
        'label',
        'description',
        'ip',
        'api_key',
        'api_key_prefix',
        'last_seen_at',
        'last_offline_notified_at',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $casts = [
        'last_seen_at'             => 'datetime',
        'last_offline_notified_at' => 'datetime',
    ];

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function latestMetric(): HasOne
    {
        return $this->hasOne(Metric::class)->latestOfMany('collected_at');
    }

    public function diskPartitions(): HasMany
    {
        return $this->hasMany(DiskPartition::class);
    }

    public function alertRules(): HasMany
    {
        return $this->hasMany(AlertRule::class);
    }

    public function httpChecks(): HasMany
    {
        return $this->hasMany(HttpCheck::class);
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->diffInMinutes(now()) < 3;
    }

    /**
     * Find a host by plaintext API key.
     * Uses the indexed prefix column to fetch at most one candidate,
     * then verifies the bcrypt hash — O(1) regardless of host count.
     */
    public static function findByApiKey(string $plaintext): ?self
    {
        $prefix = substr($plaintext, 0, 12);

        $host = static::where('api_key_prefix', $prefix)->first();

        if ($host === null) {
            return null;
        }

        return password_verify($plaintext, $host->api_key) ? $host : null;
    }
}
