<?php

namespace App\Services\Commands\DSL\Steps;

use App\Models\Fragment;

class FragmentCreateStep extends Step
{
    public function getType(): string
    {
        return 'fragment.create';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $type = $config['with']['type'] ?? 'log';
        $title = $config['with']['title'] ?? null;
        $content = $config['with']['content'] ?? '';
        $state = $config['with']['state'] ?? [];
        $tags = $config['with']['tags'] ?? [];
        $metadata = $config['with']['metadata'] ?? [];

        $fragmentData = [
            'type' => $type,
            'message' => $content,
            'state' => $state,
            'tags' => $tags,
            'metadata' => $metadata,
        ];

        if ($title) {
            $fragmentData['title'] = $title;
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'fragment_data' => $fragmentData,
                'would_create' => true,
            ];
        }

        try {
            $fragment = Fragment::create($fragmentData);

            return [
                'fragment_id' => $fragment->id,
                'type' => $fragment->type,
                'created_at' => $fragment->created_at->toISOString(),
            ];

        } catch (\Exception $e) {
            throw new \RuntimeException("Fragment creation failed: {$e->getMessage()}");
        }
    }

    public function validate(array $config): bool
    {
        return isset($config['with']) && is_array($config['with']);
    }
}
