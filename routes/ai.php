<?php

use App\Http\Middleware\DeveloperMcpAccess;
use App\Http\Middleware\NoStore;
use App\Mcp\CareerServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/career', CareerServer::class)->middleware(['auth:sanctum', DeveloperMcpAccess::class, NoStore::class, 'throttle:180,1']);
