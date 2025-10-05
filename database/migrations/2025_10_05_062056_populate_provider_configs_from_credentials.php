<?php

use App\Models\Provider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('providers')) {
            return;
        }

        // Get all unique providers from existing credentials
        $providers = DB::table('a_i_credentials')
            ->select('provider')
            ->distinct()
            ->pluck('provider');

        foreach ($providers as $provider) {
            // Create provider config if it doesn't exist
            $config = Provider::firstOrCreate([
                'provider' => $provider,
            ], [
                'enabled' => true,
                'priority' => 50,
                'ui_preferences' => [
                    'display_name' => ucfirst($provider),
                ],
            ]);

            // Sync capabilities from fragments config
            $config->syncFromConfig();

            // Link existing credentials to this config
            DB::table('a_i_credentials')
                ->where('provider', $provider)
                ->whereNull('provider_config_id')
                ->update(['provider_config_id' => $config->id]);
        }

        // Also create configs for providers in fragments.php that don't have credentials yet
        $fragmentsProviders = array_keys(config('fragments.models.providers', []));

        foreach ($fragmentsProviders as $provider) {
            $config = Provider::firstOrCreate([
                'provider' => $provider,
            ], [
                'enabled' => true,
                'priority' => 50,
                'ui_preferences' => [
                    'display_name' => config("fragments.models.providers.{$provider}.name", ucfirst($provider)),
                ],
            ]);

            $config->syncFromConfig();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('provider_configs')) {
            return;
        }

        // Remove provider_config_id from credentials
        DB::table('a_i_credentials')->update(['provider_config_id' => null]);

        // Delete all provider configs
        DB::table('provider_configs')->delete();
    }
};
