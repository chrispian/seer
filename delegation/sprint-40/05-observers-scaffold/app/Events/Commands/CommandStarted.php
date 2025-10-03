<?php
namespace App\Events\Commands;
class CommandStarted { public function __construct(
    public string $slug, public ?string $workspaceId = null, public ?string $userId = null, public ?string $runId = null
){} }
