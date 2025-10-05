<?php

use Laravel\Mcp\Facades\Mcp;
use HollisLabs\ToolCrate\Servers\ToolCrateServer;

Mcp::local('tool-crate', ToolCrateServer::class);
