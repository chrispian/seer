<?php

namespace App\Services\V2;

use App\Services\CommandRegistry;

class ActionAdapter
{
    public function execute(array $payload): array
    {
        $type = $payload['type'] ?? null;

        if ($type === 'command') {
            return $this->executeCommand($payload);
        }

        if ($type === 'navigate') {
            return $this->executeNavigate($payload);
        }

        throw new \InvalidArgumentException("Unknown action type: {$type}");
    }

    protected function executeCommand(array $payload): array
    {
        $commandName = $payload['command'] ?? null;
        $params = $payload['params'] ?? [];

        if (! $commandName) {
            throw new \InvalidArgumentException('Command name is required');
        }

        $commandName = ltrim($commandName, '/');

        if (! CommandRegistry::isPhpCommand($commandName)) {
            throw new \InvalidArgumentException("Command not found: {$commandName}");
        }

        $commandClass = CommandRegistry::getPhpCommand($commandName);
        $commandModel = CommandRegistry::getCommand($commandName);

        $command = new $commandClass($params);
        $command->setContext('web');

        if ($commandModel) {
            $command->setCommand($commandModel);
        }

        $result = $command->handle();

        return [
            'success' => true,
            'result' => $result,
            'hash' => hash('sha256', json_encode($result)),
        ];
    }

    protected function executeNavigate(array $payload): array
    {
        $url = $payload['url'] ?? null;

        if (! $url) {
            throw new \InvalidArgumentException('URL is required for navigate action');
        }

        return [
            'success' => true,
            'result' => [
                'type' => 'navigate',
                'url' => $url,
            ],
            'hash' => hash('sha256', json_encode(['url' => $url])),
        ];
    }
}
