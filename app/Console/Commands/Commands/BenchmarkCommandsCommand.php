<?php

namespace App\Console\Commands\Commands;

use App\Services\Commands\DSL\CommandRunner;
use Illuminate\Console\Command;

class BenchmarkCommandsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'frag:command:benchmark {commands?* : Commands to benchmark} {--runs=5 : Number of runs per command} {--timeout=30 : Timeout per command in seconds}';

    /**
     * The console command description.
     */
    protected $description = 'Benchmark command execution performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $commands = $this->argument('commands') ?: ['help', 'clear', 'frag', 'note', 'bookmark', 'join', 'channels', 'session'];
        $runs = (int) $this->option('runs');
        $timeout = (int) $this->option('timeout');

        $this->info("🚀 Benchmarking Commands ({$runs} runs each)");
        $this->newLine();

        $runner = app(CommandRunner::class);
        $defaultContext = [
            'ctx' => [
                'body' => 'Test input for benchmarking',
                'selection' => '',
                'user' => ['id' => 1, 'name' => 'Benchmark User'],
                'workspace' => ['id' => 1],
                'session' => ['id' => 'benchmark-session'],
            ],
        ];

        $results = [];

        foreach ($commands as $command) {
            $this->line("Testing {$command}...");

            $durations = [];
            $errors = 0;

            for ($i = 0; $i < $runs; $i++) {
                $startTime = microtime(true);

                try {
                    $execution = $runner->execute($command, $defaultContext, false);
                    $duration = round((microtime(true) - $startTime) * 1000, 2);

                    if ($execution['success']) {
                        $durations[] = $duration;
                    } else {
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                }

                // Prevent overwhelming the system
                usleep(100000); // 100ms between runs
            }

            if (! empty($durations)) {
                $results[$command] = [
                    'min' => min($durations),
                    'max' => max($durations),
                    'avg' => round(array_sum($durations) / count($durations), 2),
                    'median' => $this->median($durations),
                    'errors' => $errors,
                    'success_rate' => round((count($durations) / $runs) * 100, 1),
                ];
            } else {
                $results[$command] = [
                    'min' => 0,
                    'max' => 0,
                    'avg' => 0,
                    'median' => 0,
                    'errors' => $errors,
                    'success_rate' => 0,
                ];
            }
        }

        $this->displayResults($results);

        return self::SUCCESS;
    }

    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📊 Benchmark Results');
        $this->newLine();

        $headers = ['Command', 'Min (ms)', 'Max (ms)', 'Avg (ms)', 'Median (ms)', 'Success Rate'];
        $rows = [];

        foreach ($results as $command => $stats) {
            $rows[] = [
                $command,
                $stats['min'],
                $stats['max'],
                $stats['avg'],
                $stats['median'],
                $stats['success_rate'].'%',
            ];
        }

        $this->table($headers, $rows);

        // Summary statistics
        $allAvgs = array_column($results, 'avg');
        $allSuccessRates = array_column($results, 'success_rate');

        $this->newLine();
        $this->info('📈 Summary');
        $this->line('  Overall avg performance: '.round(array_sum($allAvgs) / count($allAvgs), 2).'ms');
        $this->line('  Overall success rate: '.round(array_sum($allSuccessRates) / count($allSuccessRates), 1).'%');
        $this->line('  Fastest command: '.array_keys($results, min($results))[0].' ('.min($allAvgs).'ms avg)');
        $this->line('  Slowest command: '.array_keys($results, max($results))[0].' ('.max($allAvgs).'ms avg)');
    }

    protected function median(array $numbers): float
    {
        sort($numbers);
        $count = count($numbers);

        if ($count % 2 === 0) {
            return ($numbers[$count / 2 - 1] + $numbers[$count / 2]) / 2;
        } else {
            return $numbers[floor($count / 2)];
        }
    }
}
