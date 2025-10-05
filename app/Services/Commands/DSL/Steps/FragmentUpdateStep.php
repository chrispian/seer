<?php

namespace App\Services\Commands\DSL\Steps;

use App\Models\Fragment;

class FragmentUpdateStep extends Step
{
    public function getType(): string
    {
        return 'fragment.update';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $with = $config['with'] ?? [];

        if (! isset($with['id'])) {
            throw new \InvalidArgumentException('Fragment update requires an id');
        }

        $fragmentId = $with['id'];
        $updateData = $with['data'] ?? [];

        if ($dryRun) {
            return [
                'dry_run' => true,
                'would_update' => true,
                'fragment_id' => $fragmentId,
                'update_data' => $updateData,
            ];
        }

        // Find the fragment
        $fragment = Fragment::find($fragmentId);
        if (! $fragment) {
            throw new \InvalidArgumentException("Fragment not found: {$fragmentId}");
        }

        // Store original data for response
        $originalData = [
            'state' => $fragment->state,
            'message' => $fragment->message,
            'title' => $fragment->title,
            'tags' => $fragment->tags,
        ];

        // Apply updates
        foreach ($updateData as $key => $value) {
            if (str_contains($key, '.')) {
                // Handle nested updates like 'state.status'
                $parts = explode('.', $key, 2);
                $mainKey = $parts[0];
                $subKey = $parts[1];

                if ($mainKey === 'state') {
                    $currentState = $fragment->state ?? [];
                    $currentState[$subKey] = $value;
                    $fragment->state = $currentState;
                } else {
                    // Handle other nested keys if needed
                    throw new \InvalidArgumentException("Nested update not supported for key: {$mainKey}");
                }
            } else {
                // Handle direct column updates
                switch ($key) {
                    case 'message':
                    case 'title':
                    case 'tags':
                        $fragment->{$key} = $value;
                        break;
                    case 'state':
                        if (is_array($value)) {
                            $fragment->state = $value;
                        } else {
                            throw new \InvalidArgumentException('State must be an array');
                        }
                        break;
                    default:
                        throw new \InvalidArgumentException("Update not allowed for key: {$key}");
                }
            }
        }

        // Save the fragment
        $fragment->save();

        // Reload with relationships for response
        $fragment->load('type');

        return [
            'success' => true,
            'fragment_id' => $fragment->id,
            'updated_fields' => array_keys($updateData),
            'original_data' => $originalData,
            'updated_data' => [
                'state' => $fragment->state,
                'message' => $fragment->message,
                'title' => $fragment->title,
                'tags' => $fragment->tags,
            ],
            'fragment' => [
                'id' => $fragment->id,
                'message' => $fragment->message,
                'title' => $fragment->title,
                'type' => [
                    'name' => $fragment->type?->label ?? ucfirst($fragment->type?->value ?? 'fragment'),
                    'value' => $fragment->type?->value ?? 'fragment',
                ],
                'tags' => $fragment->tags ?? [],
                'state' => $fragment->state ?? [],
                'created_at' => $fragment->created_at,
                'updated_at' => $fragment->updated_at,
            ],
        ];
    }

    public function validate(array $config): bool
    {
        $with = $config['with'] ?? [];

        return isset($with['id']) && isset($with['data']) && is_array($with['data']);
    }
}
