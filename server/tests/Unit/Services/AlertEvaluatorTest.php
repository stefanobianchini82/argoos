<?php

use App\Models\AlertEvent;
use App\Models\AlertRule;
use App\Models\DiskPartition;
use App\Models\Host;
use App\Models\Metric;
use App\Models\Setting;
use App\Services\AlertEvaluator;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->evaluator = app(AlertEvaluator::class);
});

describe('AlertEvaluator::evaluate() — no metrics', function () {
    it('creates no alert event when there are no metrics in the window', function () {
        $rule = AlertRule::factory()
            ->forMetric('cpu_usage', '>', 80)
            ->create(['duration_minutes' => 5]);

        $this->evaluator->evaluate($rule);

        expect(AlertEvent::count())->toBe(0);
    });
});

describe('AlertEvaluator::evaluate() — threshold exceeded', function () {
    it('creates an alert event when the average exceeds the threshold', function () {
        $host = Host::factory()->create();
        Setting::set(Setting::ALERT_EMAIL, 'alert@example.com');

        $rule = AlertRule::factory()
            ->forMetric('cpu_usage', '>', 80)
            ->create([
                'host_id'          => $host->id,
                'duration_minutes' => 5,
                'channel'          => 'email',
                'channel_target'   => 'alert@example.com',
            ]);

        Metric::factory()->create([
            'host_id'      => $host->id,
            'collected_at' => now()->subMinutes(1),
            'cpu_usage'    => 90.0,
        ]);

        $this->evaluator->evaluate($rule);

        $event = AlertEvent::where('alert_rule_id', $rule->id)->first();
        expect($event)->not->toBeNull();
        expect($event->triggered_at)->not->toBeNull();
        expect($event->resolved_at)->toBeNull();
    });

    it('does not create a duplicate event when one is already open', function () {
        $host = Host::factory()->create();
        $rule = AlertRule::factory()
            ->forMetric('cpu_usage', '>', 80)
            ->create(['host_id' => $host->id, 'duration_minutes' => 5]);

        AlertEvent::factory()->create([
            'alert_rule_id' => $rule->id,
            'triggered_at'  => now()->subMinutes(2),
        ]);

        Metric::factory()->create([
            'host_id'      => $host->id,
            'collected_at' => now()->subMinutes(1),
            'cpu_usage'    => 95.0,
        ]);

        $this->evaluator->evaluate($rule);

        expect(AlertEvent::where('alert_rule_id', $rule->id)->count())->toBe(1);
    });

    it('updates peak_value when a higher value is observed on an open event', function () {
        $host = Host::factory()->create();
        $rule = AlertRule::factory()
            ->forMetric('cpu_usage', '>', 80)
            ->create(['host_id' => $host->id, 'duration_minutes' => 5]);

        $event = AlertEvent::factory()->create([
            'alert_rule_id' => $rule->id,
            'peak_value'    => 85.0,
        ]);

        Metric::factory()->create([
            'host_id'      => $host->id,
            'collected_at' => now()->subMinutes(1),
            'cpu_usage'    => 95.0,
        ]);

        $this->evaluator->evaluate($rule);

        expect($event->fresh()->peak_value)->toBe(95.0);
    });
});

describe('AlertEvaluator::evaluate() — threshold no longer exceeded', function () {
    it('resolves an open event when the metric falls below the threshold', function () {
        $host = Host::factory()->create();
        $rule = AlertRule::factory()
            ->forMetric('cpu_usage', '>', 80)
            ->create(['host_id' => $host->id, 'duration_minutes' => 5]);

        $event = AlertEvent::factory()->create([
            'alert_rule_id' => $rule->id,
            'resolved_at'   => null,
        ]);

        Metric::factory()->create([
            'host_id'      => $host->id,
            'collected_at' => now()->subMinutes(1),
            'cpu_usage'    => 50.0,
        ]);

        $this->evaluator->evaluate($rule);

        expect($event->fresh()->resolved_at)->not->toBeNull();
    });
});

describe('AlertEvaluator::evaluate() — operators', function () {
    it('triggers on < operator when value is below threshold', function () {
        $host = Host::factory()->create();
        Setting::set(Setting::ALERT_EMAIL, 'alert@example.com');

        $rule = AlertRule::factory()
            ->forMetric('cpu_usage', '<', 10)
            ->create([
                'host_id'          => $host->id,
                'duration_minutes' => 5,
                'channel'          => 'email',
                'channel_target'   => 'alert@example.com',
            ]);

        Metric::factory()->create([
            'host_id'      => $host->id,
            'collected_at' => now()->subMinutes(1),
            'cpu_usage'    => 5.0,
        ]);

        $this->evaluator->evaluate($rule);

        expect(AlertEvent::where('alert_rule_id', $rule->id)->exists())->toBeTrue();
    });

    it('triggers on >= operator when value equals threshold', function () {
        $host = Host::factory()->create();
        Setting::set(Setting::ALERT_EMAIL, 'alert@example.com');

        $rule = AlertRule::factory()
            ->forMetric('cpu_usage', '>=', 80)
            ->create([
                'host_id'          => $host->id,
                'duration_minutes' => 5,
                'channel'          => 'email',
                'channel_target'   => 'alert@example.com',
            ]);

        Metric::factory()->create([
            'host_id'      => $host->id,
            'collected_at' => now()->subMinutes(1),
            'cpu_usage'    => 80.0,
        ]);

        $this->evaluator->evaluate($rule);

        expect(AlertEvent::where('alert_rule_id', $rule->id)->exists())->toBeTrue();
    });
});

describe('AlertEvaluator::evaluate() — disk_usage_percent', function () {
    it('creates an alert event when any partition exceeds the threshold', function () {
        $host = Host::factory()->create();
        Setting::set(Setting::ALERT_EMAIL, 'alert@example.com');

        $rule = AlertRule::factory()
            ->forMetric('disk_usage_percent', '>', 80)
            ->create([
                'host_id'          => $host->id,
                'duration_minutes' => 5,
                'channel'          => 'email',
                'channel_target'   => 'alert@example.com',
            ]);

        $total = 100 * 1024 * 1024 * 1024;
        DiskPartition::factory()->create([
            'host_id'      => $host->id,
            'mount_point'  => '/data',
            'total'        => $total,
            'used'         => (int) ($total * 0.90),
            'free'         => (int) ($total * 0.10),
            'collected_at' => now()->subMinutes(1),
        ]);

        $this->evaluator->evaluate($rule);

        $event = AlertEvent::where('alert_rule_id', $rule->id)->first();
        expect($event)->not->toBeNull();
        expect($event->trigger_context['mount_point'])->toBe('/data');
        expect($event->trigger_context['usage_pct'])->toBeGreaterThan(80.0);
    });

    it('does not create an alert event when all partitions are below the threshold', function () {
        $host = Host::factory()->create();

        $rule = AlertRule::factory()
            ->forMetric('disk_usage_percent', '>', 80)
            ->create(['host_id' => $host->id, 'duration_minutes' => 5]);

        $total = 100 * 1024 * 1024 * 1024;
        DiskPartition::factory()->create([
            'host_id'      => $host->id,
            'mount_point'  => '/',
            'total'        => $total,
            'used'         => (int) ($total * 0.50),
            'free'         => (int) ($total * 0.50),
            'collected_at' => now()->subMinutes(1),
        ]);

        $this->evaluator->evaluate($rule);

        expect(AlertEvent::count())->toBe(0);
    });

    it('resolves an open event when all partitions fall below the threshold', function () {
        $host = Host::factory()->create();

        $rule = AlertRule::factory()
            ->forMetric('disk_usage_percent', '>', 80)
            ->create(['host_id' => $host->id, 'duration_minutes' => 5]);

        $event = AlertEvent::factory()->create([
            'alert_rule_id' => $rule->id,
            'resolved_at'   => null,
        ]);

        $total = 100 * 1024 * 1024 * 1024;
        DiskPartition::factory()->create([
            'host_id'      => $host->id,
            'mount_point'  => '/',
            'total'        => $total,
            'used'         => (int) ($total * 0.60),
            'free'         => (int) ($total * 0.40),
            'collected_at' => now()->subMinutes(1),
        ]);

        $this->evaluator->evaluate($rule);

        expect($event->fresh()->resolved_at)->not->toBeNull();
    });

    it('creates no event when there are no disk partition readings in the window', function () {
        $rule = AlertRule::factory()
            ->forMetric('disk_usage_percent', '>', 80)
            ->create(['duration_minutes' => 5]);

        $this->evaluator->evaluate($rule);

        expect(AlertEvent::count())->toBe(0);
    });

    it('triggers based on the worst partition, not the average', function () {
        $host = Host::factory()->create();
        Setting::set(Setting::ALERT_EMAIL, 'alert@example.com');

        $rule = AlertRule::factory()
            ->forMetric('disk_usage_percent', '>', 80)
            ->create([
                'host_id'          => $host->id,
                'duration_minutes' => 5,
                'channel'          => 'email',
                'channel_target'   => 'alert@example.com',
            ]);

        $total = 100 * 1024 * 1024 * 1024;

        // Partition at 30% — below threshold
        DiskPartition::factory()->create([
            'host_id'      => $host->id,
            'mount_point'  => '/',
            'total'        => $total,
            'used'         => (int) ($total * 0.30),
            'free'         => (int) ($total * 0.70),
            'collected_at' => now()->subMinutes(1),
        ]);

        // Partition at 95% — above threshold
        DiskPartition::factory()->create([
            'host_id'      => $host->id,
            'mount_point'  => '/data',
            'total'        => $total,
            'used'         => (int) ($total * 0.95),
            'free'         => (int) ($total * 0.05),
            'collected_at' => now()->subMinutes(1),
        ]);

        $this->evaluator->evaluate($rule);

        $event = AlertEvent::where('alert_rule_id', $rule->id)->first();
        expect($event)->not->toBeNull();
        expect($event->trigger_context['mount_point'])->toBe('/data');
    });
});
