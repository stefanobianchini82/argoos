<?php

use App\Models\Host;
use App\Models\Metric;
use App\Services\MetricAggregator;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->aggregator = app(MetricAggregator::class);
    $this->host = Host::factory()->create();
});

describe('MetricAggregator::getForRange() — structure', function () {
    it('returns an array with all expected keys', function () {
        $result = $this->aggregator->getForRange($this->host, '1h');

        expect($result)->toHaveKeys([
            'labels', 'cpu_usage', 'ram_pct',
            'disk_read_kb', 'disk_write_kb', 'net_rx_kb', 'net_tx_kb', 'load_avg_1',
        ]);
    });

    it('returns empty arrays when there are no metrics in the window', function () {
        $result = $this->aggregator->getForRange($this->host, '1h');

        expect($result['labels'])->toBeEmpty();
        expect($result['cpu_usage'])->toBeEmpty();
    });
});

describe('MetricAggregator::getForRange() — 1h range', function () {
    it('returns metrics collected within the last hour', function () {
        Metric::factory()->create([
            'host_id'      => $this->host->id,
            'collected_at' => now()->subMinutes(30),
            'cpu_usage'    => 50.0,
            'ram_used'     => 2 * 1024 * 1024 * 1024,
            'ram_total'    => 8 * 1024 * 1024 * 1024,
        ]);

        $result = $this->aggregator->getForRange($this->host, '1h');

        expect(count($result['labels']))->toBe(1);
        expect($result['cpu_usage'][0])->toBe(50.0);
    });

    it('excludes metrics older than 1 hour', function () {
        Metric::factory()->create([
            'host_id'      => $this->host->id,
            'collected_at' => now()->subMinutes(90),
            'cpu_usage'    => 80.0,
        ]);

        $result = $this->aggregator->getForRange($this->host, '1h');

        expect($result['labels'])->toBeEmpty();
    });

    it('calculates ram_pct correctly', function () {
        Metric::factory()->create([
            'host_id'      => $this->host->id,
            'collected_at' => now()->subMinutes(5),
            'ram_used'     => 4 * 1024 * 1024 * 1024,
            'ram_total'    => 8 * 1024 * 1024 * 1024,
        ]);

        $result = $this->aggregator->getForRange($this->host, '1h');

        expect($result['ram_pct'][0])->toBe(50.0);
    });

    it('converts disk bytes to kilobytes', function () {
        Metric::factory()->create([
            'host_id'          => $this->host->id,
            'collected_at'     => now()->subMinutes(5),
            'disk_read_bytes'  => 1024,
            'disk_write_bytes' => 2048,
        ]);

        $result = $this->aggregator->getForRange($this->host, '1h');

        expect($result['disk_read_kb'][0])->toBe(1.0);
        expect($result['disk_write_kb'][0])->toBe(2.0);
    });
});

describe('MetricAggregator caching', function () {
    it('returns a cached result on the second call without additional queries', function () {
        Metric::factory()->create([
            'host_id'      => $this->host->id,
            'collected_at' => now()->subMinutes(5),
        ]);

        $first = $this->aggregator->getForRange($this->host, '1h');

        Metric::factory()->create([
            'host_id'      => $this->host->id,
            'collected_at' => now()->subMinutes(3),
        ]);

        $second = $this->aggregator->getForRange($this->host, '1h');

        expect($second)->toEqual($first);
    });

    it('uses separate cache keys per host', function () {
        $otherHost = Host::factory()->create();

        Metric::factory()->create([
            'host_id'      => $this->host->id,
            'collected_at' => now()->subMinutes(5),
            'cpu_usage'    => 10.0,
        ]);

        Metric::factory()->create([
            'host_id'      => $otherHost->id,
            'collected_at' => now()->subMinutes(5),
            'cpu_usage'    => 90.0,
        ]);

        Cache::flush();

        $result1 = $this->aggregator->getForRange($this->host, '1h');
        $result2 = $this->aggregator->getForRange($otherHost, '1h');

        expect($result1['cpu_usage'][0])->toBe(10.0);
        expect($result2['cpu_usage'][0])->toBe(90.0);
    });
});
