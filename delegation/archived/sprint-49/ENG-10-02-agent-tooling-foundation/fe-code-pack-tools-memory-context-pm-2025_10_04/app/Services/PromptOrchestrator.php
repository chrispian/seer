<?php

namespace App\Services;

use App\Support\ToolRegistry;

class PromptOrchestrator
{
    public function __construct(protected ToolRegistry $reg) {}

    public function assemble(array $context = []): array
    {
        // TODO: pull session, agent config, slash prompts, memory rollups, tool schemas
        $tools = ['db.query', 'export.generate', 'memory.write', 'memory.search'];
        $sys = [
            'role' => $context['role'] ?? 'assistant',
            'style' => $context['style'] ?? 'concise',
            'rules' => [
                'prefer_deterministic_tools' => true,
                'dry_run_for_mutations' => true,
            ],
        ];
        $prompt = 'You are a capable assistant. Prefer deterministic tools. Use dry-run for any mutations.';

        return [
            'system_prompt' => $prompt,
            'config' => $sys,
            'tools' => $tools,
            'hash' => \App\Support\ToolRegistry::hash([$prompt, $sys, $tools]),
        ];
    }
}
