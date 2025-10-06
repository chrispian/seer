<?php

namespace App\Console\Commands;

use App\Enums\AgentMode;
use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Services\AgentOrchestrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class OrchestrationAgentSaveCommand extends Command
{
    protected $signature = 'orchestration:agent:save
        {--id= : Agent UUID for updates}
        {--name= : Agent name (required when creating)}
        {--slug= : Agent slug}
        {--type= : Agent type slug}
        {--mode= : Agent mode slug}
        {--status= : Agent status (active/inactive/archived)}
        {--description= : Agent description}
        {--capability=* : Capability string (repeatable)}
        {--constraint=* : Constraint string (repeatable)}
        {--tool=* : Tool string (repeatable)}
        {--meta=* : Metadata key=value pairs}
        {--no-upsert : Fail if the agent does not exist}
        {--json : Output JSON instead of tables}';

    protected $description = 'Create or update an agent profile via orchestration services.';

    public function handle(AgentOrchestrationService $service): int
    {
        $attributes = array_filter([
            'id' => $this->option('id'),
            'name' => $this->option('name'),
            'slug' => $this->option('slug'),
            'type' => $this->option('type'),
            'mode' => $this->option('mode'),
            'status' => $this->option('status'),
            'description' => $this->option('description'),
        ], static fn ($value) => $value !== null && $value !== '');

        if (isset($attributes['type']) && ! AgentType::tryFrom($attributes['type'])) {
            $this->error(sprintf('Unknown agent type [%s].', $attributes['type']));

            return self::FAILURE;
        }

        if (isset($attributes['mode']) && ! AgentMode::tryFrom($attributes['mode'])) {
            $this->error(sprintf('Unknown agent mode [%s].', $attributes['mode']));

            return self::FAILURE;
        }

        if (isset($attributes['status']) && ! AgentStatus::tryFrom(Str::lower($attributes['status']))) {
            $this->error(sprintf('Unknown agent status [%s].', $attributes['status']));

            return self::FAILURE;
        }

        $capabilities = $this->option('capability');
        if ($capabilities !== []) {
            $attributes['capabilities'] = array_values(array_filter($capabilities));
        }

        $constraints = $this->option('constraint');
        if ($constraints !== []) {
            $attributes['constraints'] = array_values(array_filter($constraints));
        }

        $tools = $this->option('tool');
        if ($tools !== []) {
            $attributes['tools'] = array_values(array_filter($tools));
        }

        $meta = [];
        foreach ($this->option('meta') as $pair) {
            if (! str_contains($pair, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $pair, 2);
            $meta[trim($key)] = trim($value);
        }

        if ($meta !== []) {
            $attributes['metadata'] = $meta;
        }

        $upsert = ! $this->option('no-upsert');

        $agent = $service->save($attributes, $upsert);
        $detail = $service->detail($agent, [
            'assignments_limit' => 5,
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($detail, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info(sprintf('Agent %s saved as %s', $detail['agent']['name'] ?? 'n/a', $detail['agent']['status'] ?? 'n/a'));

        return self::SUCCESS;
    }
}
