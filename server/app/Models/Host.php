<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Host extends Model
{
    protected $fillable = [
        'label',
        'ip',
        'api_key',
        'api_key_prefix',
        'last_seen_at',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function diskPartitions(): HasMany
    {
        return $this->hasMany(DiskPartition::class);
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
