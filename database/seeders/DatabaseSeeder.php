<?php

    namespace Database\Seeders;

    use Illuminate\Database\Seeder;
    use App\Models\SeerLog;

    class DatabaseSeeder extends Seeder
    {
        public function run(): void
        {
            SeerLog::factory(10)->create();
        }
    }