<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\Vault;

class VaultCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $action = $command->arguments['identifier'] ?? 'list';

        return match ($action) {
            'list' => $this->handleList($command),
            'create' => $this->handleCreate($command),
            'switch' => $this->handleSwitch($command),
            default => $this->handleSwitch($command, $action)
        };
    }

    private function handleList(CommandRequest $command): CommandResponse
    {
        $vaults = Vault::ordered()->get();

        if ($vaults->isEmpty()) {
            return new CommandResponse(
                type: 'vault',
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'list',
                    'message' => '📁 No vaults found. Create one with `/vault create "Vault Name"`',
                    'vaults' => [],
                ],
            );
        }

        $vaultData = $vaults->map(function (Vault $vault) {
            return [
                'id' => $vault->id,
                'name' => $vault->name,
                'description' => $vault->description,
                'is_default' => $vault->is_default,
                'projects_count' => $vault->projects()->count(),
                'fragments_count' => $vault->fragments()->count(),
            ];
        })->all();

        return new CommandResponse(
            type: 'vault',
            shouldOpenPanel: true,
            panelData: [
                'action' => 'list',
                'message' => '📁 Found **'.count($vaultData).'** vault'.((count($vaultData) !== 1) ? 's' : ''),
                'vaults' => $vaultData,
            ],
        );
    }

    private function handleCreate(CommandRequest $command): CommandResponse
    {
        $name = $command->arguments['name'] ?? null;

        if (empty($name)) {
            return new CommandResponse(
                type: 'vault',
                shouldShowErrorToast: true,
                message: 'Please provide a vault name. Example: `/vault create "My Vault"`',
            );
        }

        // Check if vault already exists
        if (Vault::where('name', $name)->exists()) {
            return new CommandResponse(
                type: 'vault',
                shouldShowErrorToast: true,
                message: "Vault '{$name}' already exists.",
            );
        }

        $vault = Vault::create([
            'name' => $name,
            'description' => $command->arguments['description'] ?? null,
            'is_default' => Vault::count() === 0, // First vault becomes default
            'sort_order' => Vault::max('sort_order') + 1,
        ]);

        return new CommandResponse(
            type: 'vault',
            shouldShowSuccessToast: true,
            message: "✅ Created vault: {$vault->name}",
            toastData: [
                'title' => 'Vault Created',
                'message' => $vault->name,
                'vaultId' => $vault->id,
            ],
        );
    }

    private function handleSwitch(CommandRequest $command, ?string $identifier = null): CommandResponse
    {
        $vaultName = $identifier ?? $command->arguments['name'] ?? null;

        if (empty($vaultName)) {
            return new CommandResponse(
                type: 'vault',
                shouldShowErrorToast: true,
                message: 'Please provide a vault name or ID to switch to. Example: `/vault "My Vault"`',
            );
        }

        // Try to find vault by name or ID
        $vault = is_numeric($vaultName)
            ? Vault::find((int) $vaultName)
            : Vault::where('name', 'LIKE', "%{$vaultName}%")->first();

        if (! $vault) {
            // Get available vaults for suggestion
            $availableVaults = Vault::ordered()->take(5)->pluck('name')->join(', ');
            $suggestion = $availableVaults ? " Available vaults: {$availableVaults}" : ' Use `/vault list` to see available vaults.';

            return new CommandResponse(
                type: 'vault',
                shouldShowErrorToast: true,
                message: "Vault '{$vaultName}' not found.{$suggestion}",
            );
        }

        return new CommandResponse(
            type: 'vault-switch',
            shouldShowSuccessToast: true,
            message: "📁 Switched to vault: {$vault->name}",
            data: [
                'vault_id' => $vault->id,
                'vault_name' => $vault->name,
            ],
            toastData: [
                'title' => 'Vault Switched',
                'message' => $vault->name,
                'vaultId' => $vault->id,
            ],
        );
    }
}
