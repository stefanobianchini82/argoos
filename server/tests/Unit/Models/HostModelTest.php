<?php

use App\Models\Host;
use App\Models\Metric;
use Illuminate\Support\Str;

describe('Host::findByApiKey()', function () {
    it('returns the host when the key is valid', function () {
        $plaintext = Str::random(48);
        $host = Host::factory()->withApiKey($plaintext)->create();

        expect(Host::findByApiKey($plaintext)?->id)->toBe($host->id);
    });

    it('returns null when the key is completely wrong', function () {
        expect(Host::findByApiKey(Str::random(48)))->toBeNull();
    });

    it('returns null when the prefix matches but the full key does not', function () {
        $plaintext = Str::random(48);
        Host::factory()->withApiKey($plaintext)->create();

        $wrongKey = substr($plaintext, 0, 12) . Str::random(36);

        expect(Host::findByApiKey($wrongKey))->toBeNull();
    });
});

describe('Host::isOnline()', function () {
    it('returns true when last_seen_at is less than 3 minutes ago', function () {
        $host = Host::factory()->create(['last_seen_at' => now()->subMinutes(1)]);

        expect($host->isOnline())->toBeTrue();
    });

    it('returns false when last_seen_at is more than 3 minutes ago', function () {
        $host = Host::factory()->create(['last_seen_at' => now()->subMinutes(5)]);

        expect($host->isOnline())->toBeFalse();
    });

    it('returns false when last_seen_at is null', function () {
        $host = Host::factory()->create(['last_seen_at' => null]);

        expect($host->isOnline())->toBeFalse();
    });

    it('returns false exactly at the 3-minute boundary', function () {
        $host = Host::factory()->create(['last_seen_at' => now()->subMinutes(3)]);

        expect($host->isOnline())->toBeFalse();
    });
});

describe('Host latestMetric relation', function () {
    it('returns the most recently collected metric', function () {
        $host = Host::factory()->create();

        Metric::factory()->create(['host_id' => $host->id, 'collected_at' => now()->subMinutes(10)]);
        $latest = Metric::factory()->create(['host_id' => $host->id, 'collected_at' => now()->subMinutes(1)]);

        expect($host->latestMetric->id)->toBe($latest->id);
    });

    it('returns null when the host has no metrics', function () {
        $host = Host::factory()->create();

        expect($host->latestMetric)->toBeNull();
    });
});
