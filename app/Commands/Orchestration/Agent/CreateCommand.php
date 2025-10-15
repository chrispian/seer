<?php

namespace App\Commands\Orchestration\Agent;

use App\Commands\BaseCommand;

class CreateCommand extends BaseCommand
{
    protected array $arguments;

    public function __construct(array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    public function handle(): array
    {
        return $this->respond([
            'message' => 'Agent creation UI coming soon! This will open a form modal to create a new agent.',
            'success' => true,
        ]);
    }

    public static function getName(): string
    {
        return 'Create Agent';
    }

    public static function getDescription(): string
    {
        return 'Create a new orchestration agent';
    }

    public static function getUsage(): string
    {
        return '/orch-agent-new [name:value designation:value ...]';
    }

    public static function getCategory(): string
    {
        return 'Orchestration';
    }

    public static function getInputSchema(): array
    {
        return [
            'name' => [
                'type' => 'string',
                'description' => 'Agent name',
                'required' => false,
            ],
            'designation' => [
                'type' => 'string',
                'description' => 'Agent role/designation',
                'required' => false,
            ],
            'profile_id' => [
                'type' => 'string',
                'description' => 'Agent profile UUID',
                'required' => false,
            ],
            'persona' => [
                'type' => 'string',
                'description' => 'Agent persona description',
                'required' => false,
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Agent status (active/inactive)',
                'required' => false,
            ],
        ];
    }
}
