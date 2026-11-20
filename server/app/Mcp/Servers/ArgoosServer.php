<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetActiveAlerts;
use App\Mcp\Tools\GetContainers;
use App\Mcp\Tools\GetDiskPartitions;
use App\Mcp\Tools\GetHostMetrics;
use App\Mcp\Tools\GetTopProcesses;
use App\Mcp\Tools\ListHosts;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Argoos')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
Argoos is a self-hosted server monitoring system. Agents on each host report metrics every ~30 seconds.
All tools are read-only and return the LATEST snapshot only (no historical series).
Start with list-hosts to discover hosts; other tools accept a host id or its exact label.
Units: sizes in bytes, percentages 0-100, timestamps ISO 8601 (UTC).
A host is "online" when it reported within the last 3 minutes.
TXT)]
class ArgoosServer extends Server
{
    protected array $tools = [
        ListHosts::class,
        GetHostMetrics::class,
        GetDiskPartitions::class,
        GetTopProcesses::class,
        GetContainers::class,
        GetActiveAlerts::class,
    ];
}
