<?php

use App\Mcp\Servers\ArgoosServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', ArgoosServer::class)
    ->middleware(['auth.mcp', 'throttle:60,1']);
