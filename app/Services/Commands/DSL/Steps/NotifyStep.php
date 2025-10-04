<?php

namespace App\Services\Commands\DSL\Steps;

class NotifyStep extends Step
{
    public function getType(): string
    {
        return 'notify';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $message = $config['with']['message'] ?? '';
        $level = $config['with']['level'] ?? 'info';
        $panelData = $config['with']['panel_data'] ?? null;

        if (! $message) {
            throw new \InvalidArgumentException('Notify step requires a message');
        }

        if ($dryRun) {
            $result = [
                'dry_run' => true,
                'message' => $message,
                'level' => $level,
                'would_notify' => true,
            ];
            if ($panelData) {
                $result['panel_data'] = $panelData;
            }

            return $result;
        }

        // Log the notification
        \Log::info('Command notification', [
            'message' => $message,
            'level' => $level,
            'context' => 'slash_command',
        ]);

        $result = [
            'message' => $message,
            'level' => $level,
            'notified' => true,
        ];

        if ($panelData) {
            $result['panel_data'] = $panelData;
        }

        return $result;
    }

    public function validate(array $config): bool
    {
        return isset($config['with']['message']);
    }
}
