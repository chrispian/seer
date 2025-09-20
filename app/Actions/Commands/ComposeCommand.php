<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;

class ComposeCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $type = $command->arguments['type'] ?? 'note';
        $title = $command->arguments['title'] ?? null;
        $tags = $command->arguments['tags'] ?? [];
        $vault = $command->arguments['vault'] ?? null;
        $project = $command->arguments['project'] ?? null;

        // Get current context for defaults
        $vaultId = $command->arguments['vault_id'] ?? null;
        $projectId = $command->arguments['project_id'] ?? null;

        $composeData = [
            'type' => $type,
            'title' => $title,
            'tags' => $tags,
            'vault' => $vault,
            'project' => $project,
            'vault_id' => $vaultId,
            'project_id' => $projectId,
            'modes' => $this->getAvailableModes(),
            'templates' => $this->getTemplates($type),
        ];

        return new CommandResponse(
            type: 'compose',
            shouldOpenPanel: true,
            panelData: [
                'action' => 'open',
                'message' => '✏️ **Compose Panel**',
                'compose' => $composeData,
            ],
        );
    }

    private function getAvailableModes(): array
    {
        return [
            [
                'value' => 'note',
                'label' => 'Note',
                'description' => 'General note or thought',
                'icon' => '📝',
            ],
            [
                'value' => 'todo',
                'label' => 'Todo',
                'description' => 'Task or action item',
                'icon' => '✅',
            ],
            [
                'value' => 'idea',
                'label' => 'Idea',
                'description' => 'Creative or project idea',
                'icon' => '💡',
            ],
            [
                'value' => 'quote',
                'label' => 'Quote',
                'description' => 'Quote or reference',
                'icon' => '💬',
            ],
            [
                'value' => 'link',
                'label' => 'Link',
                'description' => 'URL or bookmark',
                'icon' => '🔗',
            ],
            [
                'value' => 'code',
                'label' => 'Code',
                'description' => 'Code snippet or example',
                'icon' => '💻',
            ],
            [
                'value' => 'meeting',
                'label' => 'Meeting',
                'description' => 'Meeting notes or agenda',
                'icon' => '🤝',
            ],
            [
                'value' => 'research',
                'label' => 'Research',
                'description' => 'Research notes or findings',
                'icon' => '🔍',
            ],
        ];
    }

    private function getTemplates(string $type): array
    {
        return match ($type) {
            'todo' => [
                [
                    'name' => 'Simple Task',
                    'content' => '## Task\n\n- [ ] \n\n## Notes\n\n',
                ],
                [
                    'name' => 'Project Task',
                    'content' => "## Task\n\n- [ ] \n\n## Acceptance Criteria\n\n- [ ] \n- [ ] \n\n## Notes\n\n",
                ],
            ],
            'meeting' => [
                [
                    'name' => 'Meeting Notes',
                    'content' => "## Meeting: \n\n**Date:** \n**Attendees:** \n\n## Agenda\n\n1. \n\n## Discussion\n\n\n## Action Items\n\n- [ ] \n\n## Next Steps\n\n",
                ],
                [
                    'name' => 'Standup',
                    'content' => "## Daily Standup - \n\n### Yesterday\n\n- \n\n### Today\n\n- \n\n### Blockers\n\n- \n\n",
                ],
            ],
            'research' => [
                [
                    'name' => 'Research Notes',
                    'content' => "## Research: \n\n### Objective\n\n\n### Findings\n\n- \n\n### Sources\n\n- \n\n### Next Steps\n\n- \n\n",
                ],
            ],
            'code' => [
                [
                    'name' => 'Code Snippet',
                    'content' => "## Code: \n\n### Description\n\n\n### Code\n\n```\n\n```\n\n### Usage\n\n\n### Notes\n\n",
                ],
            ],
            default => [
                [
                    'name' => 'Blank',
                    'content' => '',
                ],
                [
                    'name' => 'Simple Note',
                    'content' => "## \n\n",
                ],
                [
                    'name' => 'Structured Note',
                    'content' => "## Title\n\n### Summary\n\n\n### Details\n\n\n### Tags\n\n#",
                ],
            ]
        };
    }
}
