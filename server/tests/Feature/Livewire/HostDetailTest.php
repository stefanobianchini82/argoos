<?php

use App\Livewire\HostDetail;
use App\Models\DiskPartition;
use App\Models\Host;
use App\Models\Metric;
use Livewire\Livewire;

beforeEach(function () {
    $this->host = Host::factory()->create();
});

describe('HostDetail — delete confirmation', function () {
    it('sets confirmingDelete to true on confirmDelete()', function () {
        Livewire::test(HostDetail::class, ['host' => $this->host])
            ->call('confirmDelete')
            ->assertSet('confirmingDelete', true);
    });

    it('sets confirmingDelete back to false on cancelDelete()', function () {
        Livewire::test(HostDetail::class, ['host' => $this->host])
            ->call('confirmDelete')
            ->call('cancelDelete')
            ->assertSet('confirmingDelete', false);
    });
});

describe('HostDetail — delete host', function () {
    it('deletes host, metrics, and disk partitions, then redirects', function () {
        Metric::factory()->count(3)->create(['host_id' => $this->host->id]);
        DiskPartition::factory()->count(2)->create(['host_id' => $this->host->id]);

        Livewire::test(HostDetail::class, ['host' => $this->host])
            ->call('deleteHost')
            ->assertRedirect('/');

        expect(Host::find($this->host->id))->toBeNull();
        expect(Metric::where('host_id', $this->host->id)->count())->toBe(0);
        expect(DiskPartition::where('host_id', $this->host->id)->count())->toBe(0);
    });
});

describe('HostDetail — range selection', function () {
    it('updates range to 1h and dispatches charts-updated event', function () {
        Livewire::test(HostDetail::class, ['host' => $this->host])
            ->call('setRange', '1h')
            ->assertSet('range', '1h')
            ->assertDispatched('charts-updated');
    });

    it('does not change range or dispatch event for an invalid range', function () {
        Livewire::test(HostDetail::class, ['host' => $this->host])
            ->call('setRange', 'invalid')
            ->assertSet('range', '1h')
            ->assertNotDispatched('charts-updated');
    });

    it('updates range property for all valid ranges without running DB aggregation', function () {
        // Ranges beyond 1h use MySQL-specific SQL (FROM_UNIXTIME) not available in SQLite.
        // We mock the MetricAggregator to verify range assignment without hitting MySQL functions.
        $this->mock(\App\Services\MetricAggregator::class)
            ->shouldReceive('getForRange')
            ->andReturn([
                'labels' => [], 'cpu_usage' => [], 'ram_pct' => [],
                'disk_read_kb' => [], 'disk_write_kb' => [],
                'net_rx_kb' => [], 'net_tx_kb' => [], 'load_avg_1' => [],
            ]);

        foreach (['1h', '6h', '24h', '7d'] as $range) {
            Livewire::test(HostDetail::class, ['host' => $this->host])
                ->call('setRange', $range)
                ->assertSet('range', $range);
        }
    });
});
