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
        $responseData = $config['with']['response_data'] ?? null;

        // Message is optional for some response types (like clear)
        // if (! $message && ! $responseData) {
        //     throw new \InvalidArgumentException('Notify step requires a message or response_data');
        // }

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
            if ($responseData) {
                $result['response_data'] = $responseData;
            }

            return $result;
        }

        // Log the notification (only if there's a message)
        if ($message) {
            \Log::info('Command notification', [
                'message' => $message,
                'level' => $level,
                'context' => 'slash_command',
            ]);
        }

        $result = [
            'message' => $message,
            'level' => $level,
            'notified' => true,
        ];

        if ($panelData) {
            $result['panel_data'] = $panelData;
        }

        if ($responseData) {
            $result['response_data'] = $responseData;
        }

        return $result;
    }

    public function validate(array $config): bool
    {
        $with = $config['with'] ?? [];
        // Either message or response_data should be present
        return isset($with['message']) || isset($with['response_data']);
    }
}
