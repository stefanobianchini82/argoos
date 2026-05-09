<?php

use App\Models\DiskPartition;
use App\Models\Host;
use App\Models\Metric;
use Illuminate\Support\Str;

beforeEach(function () {
    $plaintext = Str::random(48);
    $this->host = Host::factory()->withApiKey($plaintext)->create();
    $this->apiKey = $plaintext;
});

describe('POST /api/v1/metrics', function () {
    it('stores metric and disk partitions and returns 201', function () {
        $payload = fullPayload();

        $this->postJson('/api/v1/metrics', $payload, ['X-API-Key' => $this->apiKey])
            ->assertStatus(201)
            ->assertJson(['status' => 'created']);

        expect(Metric::where('host_id', $this->host->id)->count())->toBe(1);
        expect(DiskPartition::where('host_id', $this->host->id)->count())->toBe(1);
    });

    it('stores multiple disk partitions', function () {
        $payload = fullPayload([
            'disk_partitions' => [
                ['mount' => '/', 'total' => 100_000_000_000, 'used' => 40_000_000_000, 'free' => 60_000_000_000],
                ['mount' => '/data', 'total' => 500_000_000_000, 'used' => 200_000_000_000, 'free' => 300_000_000_000],
            ],
        ]);

        $this->postJson('/api/v1/metrics', $payload, ['X-API-Key' => $this->apiKey])
            ->assertStatus(201);

        expect(DiskPartition::where('host_id', $this->host->id)->count())->toBe(2);
    });

    it('returns 422 when cpu_usage is missing', function () {
        $payload = fullPayload();
        unset($payload['cpu_usage']);

        $this->postJson('/api/v1/metrics', $payload, ['X-API-Key' => $this->apiKey])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cpu_usage']);
    });

    it('returns 422 when cpu_usage exceeds 100', function () {
        $this->postJson('/api/v1/metrics', fullPayload(['cpu_usage' => 101]), ['X-API-Key' => $this->apiKey])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cpu_usage']);
    });

    it('returns 422 when cpu_usage is negative', function () {
        $this->postJson('/api/v1/metrics', fullPayload(['cpu_usage' => -1]), ['X-API-Key' => $this->apiKey])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cpu_usage']);
    });

    it('returns 422 when disk_partitions is empty', function () {
        $this->postJson('/api/v1/metrics', fullPayload(['disk_partitions' => []]), ['X-API-Key' => $this->apiKey])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['disk_partitions']);
    });

    it('returns 422 when a partition mount is missing', function () {
        $this->postJson('/api/v1/metrics', fullPayload([
            'disk_partitions' => [['total' => 100, 'used' => 50, 'free' => 50]],
        ]), ['X-API-Key' => $this->apiKey])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['disk_partitions.0.mount']);
    });

    it('returns 422 when collected_at is not a valid date', function () {
        $this->postJson('/api/v1/metrics', fullPayload(['collected_at' => 'not-a-date']), ['X-API-Key' => $this->apiKey])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['collected_at']);
    });

    it('creates no records when validation fails', function () {
        $payload = fullPayload();
        unset($payload['ram_total']);

        $this->postJson('/api/v1/metrics', $payload, ['X-API-Key' => $this->apiKey]);

        expect(Metric::count())->toBe(0);
        expect(DiskPartition::count())->toBe(0);
    });
});

function fullPayload(array $overrides = []): array
{
    return array_merge([
        'collected_at'     => now()->toIso8601String(),
        'cpu_usage'        => 55.0,
        'ram_used'         => 4 * 1024 * 1024 * 1024,
        'ram_total'        => 8 * 1024 * 1024 * 1024,
        'disk_read_bytes'  => 204800,
        'disk_write_bytes' => 102400,
        'net_rx_bytes'     => 512000,
        'net_tx_bytes'     => 256000,
        'load_avg_1'       => 0.45,
        'load_avg_5'       => 0.38,
        'load_avg_15'      => 0.31,
        'disk_partitions'  => [
            ['mount' => '/', 'total' => 100_000_000_000, 'used' => 50_000_000_000, 'free' => 50_000_000_000],
        ],
    ], $overrides);
}
