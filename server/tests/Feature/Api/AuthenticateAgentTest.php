<?php

use App\Models\Host;
use Illuminate\Support\Str;

describe('AuthenticateAgent middleware', function () {
    it('returns 401 when X-API-Key header is missing', function () {
        $this->postJson('/api/v1/metrics', [])
            ->assertStatus(401)
            ->assertJson(['error' => 'Missing X-API-Key header']);
    });

    it('returns 401 when the key prefix does not match any host', function () {
        $this->postJson('/api/v1/metrics', [], ['X-API-Key' => Str::random(48)])
            ->assertStatus(401)
            ->assertJson(['error' => 'Invalid API key']);
    });

    it('returns 401 when the prefix matches but bcrypt verification fails', function () {
        $plaintext = Str::random(48);
        Host::factory()->withApiKey($plaintext)->create();

        $wrongKey = substr($plaintext, 0, 12) . Str::random(36);

        $this->postJson('/api/v1/metrics', [], ['X-API-Key' => $wrongKey])
            ->assertStatus(401)
            ->assertJson(['error' => 'Invalid API key']);
    });

    it('updates last_seen_at on successful authentication', function () {
        $plaintext = Str::random(48);
        $host = Host::factory()->withApiKey($plaintext)->create(['last_seen_at' => null]);

        $this->postJson('/api/v1/metrics', validMetricPayload(), ['X-API-Key' => $plaintext]);

        expect($host->fresh()->last_seen_at)->not->toBeNull();
    });

    it('passes the resolved host to the controller via request attributes', function () {
        $plaintext = Str::random(48);
        $host = Host::factory()->withApiKey($plaintext)->create();

        $this->postJson('/api/v1/metrics', validMetricPayload(), ['X-API-Key' => $plaintext])
            ->assertStatus(201);

        expect(\App\Models\Metric::where('host_id', $host->id)->exists())->toBeTrue();
    });
});

function validMetricPayload(): array
{
    return [
        'collected_at'      => now()->toIso8601String(),
        'cpu_usage'         => 42.5,
        'ram_used'          => 2 * 1024 * 1024 * 1024,
        'ram_total'         => 8 * 1024 * 1024 * 1024,
        'disk_read_bytes'   => 1024,
        'disk_write_bytes'  => 512,
        'net_rx_bytes'      => 2048,
        'net_tx_bytes'      => 1024,
        'load_avg_1'        => 0.5,
        'load_avg_5'        => 0.4,
        'load_avg_15'       => 0.3,
        'disk_partitions'   => [
            ['mount' => '/', 'total' => 100 * 1024 * 1024 * 1024, 'used' => 50 * 1024 * 1024 * 1024, 'free' => 50 * 1024 * 1024 * 1024],
        ],
    ];
}
