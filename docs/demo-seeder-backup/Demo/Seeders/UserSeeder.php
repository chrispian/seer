<?php

namespace Database\Seeders\Demo\Seeders;

use App\Models\User;
use Database\Seeders\Demo\Contracts\DemoSubSeeder;
use Database\Seeders\Demo\Support\DemoSeedContext;
use Illuminate\Support\Facades\Hash;

class UserSeeder implements DemoSubSeeder
{
    public function seed(DemoSeedContext $context): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'profile_settings' => [
                    'timezone' => 'UTC',
                    'demo_account' => true,
                ],
                'profile_completed_at' => now()->subDays(7),
            ]
        );

        $context->set('users', 'primary', $user);
        $context->info("<info>✔</info> Demo user ensured ({$user->email})");
    }

    public function cleanup(DemoSeedContext $context): void
    {
        // Keep demo user persistent to avoid breaking saved credentials.
    }
}
