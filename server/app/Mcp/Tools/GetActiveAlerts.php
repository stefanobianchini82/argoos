<?php

namespace App\Mcp\Tools;

use App\Models\AlertEvent;
use App\Models\AlertRule;
use App\Models\HttpCheck;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Get currently open (unresolved) metric alerts and failing HTTP uptime checks, optionally for a single host.')]
class GetActiveAlerts extends HostTool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $hostId = null;
        if (filled($request->get('host'))) {
            $host = $this->resolveHost($request->get('host'));
            if (is_string($host)) {
                return Response::error($host);
            }
            $hostId = $host->id;
        }

        $alerts = AlertEvent::with('alertRule.host')
            ->whereNull('resolved_at')
            ->when($hostId, fn ($q) => $q->whereHas('alertRule', fn ($r) => $r->where('host_id', $hostId)))
            ->orderByDesc('triggered_at')
            ->get()
            ->map(fn (AlertEvent $e) => [
                'host'         => $e->alertRule?->host?->label,
                'host_id'      => $e->alertRule?->host_id,
                'metric'       => $e->alertRule?->metric,
                'metric_label' => AlertRule::METRICS[$e->alertRule?->metric] ?? null,
                'condition'    => $e->alertRule ? "{$e->alertRule->operator} {$e->alertRule->threshold}" : null,
                'peak_value'   => $e->peak_value,
                'triggered_at' => $e->triggered_at?->toIso8601String(),
            ])->values()->all();

        $httpChecks = HttpCheck::with(['openEvent', 'host'])
            ->active()
            ->when($hostId, fn ($q) => $q->where('host_id', $hostId))
            ->whereHas('openEvent')
            ->get()
            ->map(fn (HttpCheck $c) => [
                'host'         => $c->host?->label,
                'host_id'      => $c->host_id,
                'label'        => $c->label,
                'url'          => $c->url,
                'status_code'  => $c->openEvent?->status_code,
                'triggered_at' => $c->openEvent?->triggered_at?->toIso8601String(),
            ])->values()->all();

        return Response::structured([
            'alert_count'       => count($alerts),
            'http_failing_count' => count($httpChecks),
            'alerts'            => $alerts,
            'http_checks_down'  => $httpChecks,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'host' => $schema->string()->description('Optional host id or label; omit for all hosts.'),
        ];
    }
}
