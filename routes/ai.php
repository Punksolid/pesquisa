<?php

use App\Mcp\Servers\PesquisaServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp/pesquisa', PesquisaServer::class)
    ->middleware(['auth:api', 'throttle:mcp']);
