<?php

namespace App\Services\Commands\DSL\Steps;

class ResponsePanelStep extends Step
{
    public function getType(): string
    {
        return 'response.panel';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $with = $config['with'] ?? [];

        $responseType = $with['type'] ?? 'panel';
        $panelData = $with['panel_data'] ?? [];
        $message = $with['message'] ?? '';

        if ($dryRun) {
            return [
                'dry_run' => true,
                'would_show_panel' => true,
                'response_type' => $responseType,
                'panel_data' => $panelData,
                'message' => $message,
            ];
        }

        // Build the response data structure
        $result = [
            'type' => $responseType,
            'message' => $message,
            'shouldOpenPanel' => true,
            'panel_data' => $panelData,
        ];

        // Add type-specific formatting
        switch ($responseType) {
            case 'recall':
                $result = $this->formatRecallResponse($result, $panelData);
                break;

            case 'inbox':
                $result = $this->formatInboxResponse($result, $panelData);
                break;

            case 'todo':
                $result = $this->formatTodoResponse($result, $panelData);
                break;

            default:
                // Generic panel response
                break;
        }

        return $result;
    }

    protected function formatRecallResponse(array $result, array $panelData): array
    {
        // Ensure fragments are properly formatted
        $fragments = $panelData['fragments'] ?? [];
        $fragmentType = $panelData['type'] ?? 'fragment';
        $status = $panelData['status'] ?? null;
        $search = $panelData['search'] ?? null;
        $tags = $panelData['tags'] ?? [];

        // Build contextual message if not provided
        if (empty($result['message'])) {
            $count = count($fragments);
            $message = "📝 Found **{$count}** {$fragmentType}".($count !== 1 ? 's' : '');

            $filters = [];
            if ($status && $status !== 'open') {
                $filters[] = "status:{$status}";
            }
            if ($search) {
                $filters[] = "matching '{$search}'";
            }
            if (! empty($tags)) {
                $filters[] = 'tagged #'.implode(', #', $tags);
            }

            if (! empty($filters)) {
                $message .= ' '.implode(' and ', $filters);
            }

            $result['message'] = $message;
        }

        // Ensure panel data structure for recall
        $result['panel_data'] = array_merge($panelData, [
            'type' => $fragmentType,
            'fragments' => $fragments,
            'message' => $result['message'],
        ]);

        return $result;
    }

    protected function formatInboxResponse(array $result, array $panelData): array
    {
        $action = $panelData['action'] ?? 'pending';
        $fragments = $panelData['fragments'] ?? [];

        // Build contextual message if not provided
        if (empty($result['message'])) {
            $count = count($fragments);
            $actionText = match ($action) {
                'pending' => 'pending item',
                'bookmarked' => 'bookmarked item',
                'todos' => 'todo',
                'all' => 'actionable item',
                default => 'item',
            };

            if ($count === 0) {
                $result['message'] = "📥 No {$actionText}s found.";
            } else {
                $result['message'] = "📥 Found **{$count}** {$actionText}".($count !== 1 ? 's' : '');
            }
        }

        // Ensure panel data structure for inbox
        $result['panel_data'] = array_merge($panelData, [
            'action' => $action,
            'fragments' => $fragments,
            'message' => $result['message'],
        ]);

        return $result;
    }

    protected function formatTodoResponse(array $result, array $panelData): array
    {
        $status = $panelData['status'] ?? 'open';
        $fragments = $panelData['fragments'] ?? [];

        // Build contextual message if not provided
        if (empty($result['message'])) {
            $count = count($fragments);
            $statusText = $status === 'complete' ? 'completed' : $status;

            if ($count === 0) {
                $result['message'] = "📝 No {$statusText} todos found.";
            } else {
                $result['message'] = "📝 Found **{$count}** {$statusText} todo".($count !== 1 ? 's' : '');
            }
        }

        // Ensure panel data structure for todos
        $result['panel_data'] = array_merge($panelData, [
            'type' => 'todo',
            'status' => $status,
            'fragments' => $fragments,
            'message' => $result['message'],
        ]);

        return $result;
    }

    public function validate(array $config): bool
    {
        $with = $config['with'] ?? [];

        return isset($with['panel_data']) && is_array($with['panel_data']);
    }
}
