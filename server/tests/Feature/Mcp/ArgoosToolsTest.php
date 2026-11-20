<?php

use App\Mcp\Servers\ArgoosServer;
use App\Mcp\Tools\GetActiveAlerts;
use App\Mcp\Tools\GetContainers;
use App\Mcp\Tools\GetDiskPartitions;
use App\Mcp\Tools\GetHostMetrics;
use App\Mcp\Tools\GetTopProcesses;
use App\Mcp\Tools\ListHosts;
use App\Models\AlertEvent;
use App\Models\AlertRule;
use App\Models\ContainerMetric;
use App\Models\DiskPartition;
use App\Models\Host;
use App\Models\HttpCheck;
use App\Models\HttpCheckEvent;
use App\Models\Metric;
use App\Models\ProcessMemory;

beforeEach(function () {
    $this->web = Host::factory()->online()->create(['label' => 'web01']);
    $this->db  = Host::factory()->offline()->create(['label' => 'db01']);
});

describe('list-hosts', function () {
    it('lists all hosts with status and never exposes the api key', function () {
        Metric::factory()->create([
            'host_id' => $this->web->id, 'cpu_usage' => 42.5,
            'ram_used' => 4 * 1024 ** 3, 'ram_total' => 8 * 1024 ** 3,
        ]);

        ArgoosServer::tool(ListHosts::class)
            ->assertOk()
            ->assertStructuredContentPath('count', 2)
            ->assertStructuredContentPath('hosts.0.label', 'db01')
            ->assertStructuredContentPath('hosts.0.online', false)
            ->assertStructuredContentPath('hosts.1.label', 'web01')
            ->assertStructuredContentPath('hosts.1.online', true)
            ->assertStructuredContentPath('hosts.1.cpu_percent', 42.5)
            ->assertStructuredContentPath('hosts.1.ram_percent', 50)
            ->assertDontSee(['api_key', $this->web->api_key_prefix]);
    });

    it('filters by status', function () {
        ArgoosServer::tool(ListHosts::class, ['status' => 'online'])
            ->assertStructuredContentPath('count', 1)
            ->assertStructuredContentPath('hosts.0.label', 'web01');
    });
});

describe('get-host-metrics', function () {
    it('returns the latest metric for a host by label', function () {
        Metric::factory()->create(['host_id' => $this->web->id, 'collected_at' => now()->subMinutes(5), 'cpu_usage' => 10]);
        Metric::factory()->create(['host_id' => $this->web->id, 'collected_at' => now(), 'cpu_usage' => 77.7]);

        ArgoosServer::tool(GetHostMetrics::class, ['host' => 'web01'])
            ->assertOk()
            ->assertStructuredContentPath('host.label', 'web01')
            ->assertStructuredContentPath('metrics.cpu_percent', 77.7);
    });

    it('resolves a host by numeric id', function () {
        Metric::factory()->create(['host_id' => $this->web->id]);

        ArgoosServer::tool(GetHostMetrics::class, ['host' => (string) $this->web->id])
            ->assertOk()
            ->assertStructuredContentPath('host.id', $this->web->id);
    });

    it('returns an error for an unknown host', function () {
        ArgoosServer::tool(GetHostMetrics::class, ['host' => 'nope'])
            ->assertHasErrors(['Host "nope" not found']);
    });

    it('returns an error when the host has no metrics', function () {
        ArgoosServer::tool(GetHostMetrics::class, ['host' => 'db01'])
            ->assertHasErrors(['No metrics collected yet']);
    });
});

describe('get-disk-partitions', function () {
    it('returns only the latest collection of partitions', function () {
        DiskPartition::factory()->create([
            'host_id' => $this->web->id, 'mount_point' => '/old',
            'collected_at' => now()->subMinutes(2),
        ]);
        DiskPartition::factory()->create([
            'host_id' => $this->web->id, 'mount_point' => '/',
            'total' => 100, 'used' => 25, 'free' => 75, 'collected_at' => now(),
        ]);

        ArgoosServer::tool(GetDiskPartitions::class, ['host' => 'web01'])
            ->assertOk()
            ->assertStructuredContentCount(1, 'partitions')
            ->assertStructuredContentPath('partitions.0.mount_point', '/')
            ->assertStructuredContentPath('partitions.0.used_percent', 25);
    });
});

describe('get-top-processes', function () {
    it('sorts and limits processes from the latest collection', function () {
        $at = now();
        foreach ([['nginx', 100, 1.0], ['mysqld', 900, 5.0], ['php-fpm', 500, 30.0]] as $i => [$name, $rss, $cpu]) {
            ProcessMemory::create([
                'host_id' => $this->web->id, 'pid' => $i + 1, 'name' => $name,
                'mem_rss' => $rss, 'cpu_percent' => $cpu, 'collected_at' => $at,
            ]);
        }

        ArgoosServer::tool(GetTopProcesses::class, ['host' => 'web01', 'limit' => 2])
            ->assertStructuredContentCount(2, 'processes')
            ->assertStructuredContentPath('processes.0.name', 'mysqld');

        ArgoosServer::tool(GetTopProcesses::class, ['host' => 'web01', 'sort_by' => 'cpu_percent', 'limit' => 1])
            ->assertStructuredContentPath('processes.0.name', 'php-fpm');
    });
});

describe('get-containers', function () {
    it('returns the latest container snapshot', function () {
        ContainerMetric::create([
            'host_id' => $this->web->id, 'container_id' => 'abc', 'container_name' => 'redis',
            'image' => 'redis:7', 'cpu_percent' => 3.2, 'memory_usage' => 50, 'memory_limit' => 200,
            'collected_at' => now(),
        ]);

        ArgoosServer::tool(GetContainers::class, ['host' => 'web01'])
            ->assertOk()
            ->assertStructuredContentPath('containers.0.name', 'redis')
            ->assertStructuredContentPath('containers.0.memory_percent', 25);
    });
});

describe('get-active-alerts', function () {
    it('returns only unresolved alerts, optionally filtered by host', function () {
        $webRule = AlertRule::factory()->forMetric('cpu_usage', '>', 80)->create(['host_id' => $this->web->id]);
        $dbRule  = AlertRule::factory()->create(['host_id' => $this->db->id]);
        AlertEvent::factory()->create(['alert_rule_id' => $webRule->id, 'peak_value' => 95]);
        AlertEvent::factory()->resolved()->create(['alert_rule_id' => $webRule->id]);
        AlertEvent::factory()->create(['alert_rule_id' => $dbRule->id]);

        ArgoosServer::tool(GetActiveAlerts::class)
            ->assertStructuredContentPath('alert_count', 2);

        ArgoosServer::tool(GetActiveAlerts::class, ['host' => 'web01'])
            ->assertStructuredContentPath('alert_count', 1)
            ->assertStructuredContentPath('alerts.0.metric', 'cpu_usage')
            ->assertStructuredContentPath('alerts.0.peak_value', 95);
    });

    it('returns active HTTP checks that are currently down', function () {
        $make = fn (string $label) => HttpCheck::create([
            'host_id' => $this->web->id, 'label' => $label, 'url' => "https://{$label}.test",
            'method' => 'GET', 'timeout_seconds' => 5, 'expected_status_code' => 200,
            'channel' => 'email', 'channel_target' => 'ops@example.test', 'is_active' => true,
        ]);
        $down = $make('down');
        $up   = $make('up');

        HttpCheckEvent::create(['http_check_id' => $down->id, 'is_up' => false, 'status_code' => 503, 'triggered_at' => now()]);
        HttpCheckEvent::create(['http_check_id' => $up->id, 'is_up' => false, 'status_code' => 500, 'triggered_at' => now()->subHour(), 'resolved_at' => now()]);

        ArgoosServer::tool(GetActiveAlerts::class)
            ->assertStructuredContentPath('http_failing_count', 1)
            ->assertStructuredContentPath('http_checks_down.0.label', 'down')
            ->assertStructuredContentPath('http_checks_down.0.status_code', 503);
    });
});
