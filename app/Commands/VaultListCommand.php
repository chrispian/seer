<?php

namespace App\Commands;

class VaultListCommand extends BaseCommand
{
    public function handle(): array
    {
        $vaults = $this->getVaults();

        return $this->respond([
            'vaults' => $vaults,
        ]);
    }

    private function getVaults(): array
    {
        if (class_exists(\App\Models\Vault::class)) {
            $vaults = \App\Models\Vault::query()
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(function ($vault) {
                    return [
                        'id' => $vault->id,
                        'name' => $vault->name,
                        'description' => $vault->description,
                        'is_default' => $vault->is_default,
                        'is_active' => $vault->is_active ?? true,
                        'metadata' => $vault->metadata ?? [],
                        'created_at' => $vault->created_at?->toISOString(),
                        'updated_at' => $vault->updated_at?->toISOString(),
                        'created_human' => $vault->created_at?->diffForHumans(),
                    ];
                })
                ->all();

            return $vaults;
        }

        return [];
    }

    public static function getName(): string
    {
        return 'Vault List';
    }

    public static function getDescription(): string
    {
        return 'List all vaults in the system';
    }

    public static function getUsage(): string
    {
        return '/vault';
    }

    public static function getCategory(): string
    {
        return 'Navigation';
    }
}
