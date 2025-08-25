<?php

namespace App\Console\Commands;

use App\Actions\AnalyzeRecallPatterns as AnalyzeRecallPatternsAction;
use Illuminate\Console\Command;

class AnalyzeRecallPatterns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recall:analyze {--user=} {--days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze recall search patterns and user behavior';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->option('user') ? (int) $this->option('user') : null;
        $days = (int) $this->option('days');
        
        $this->info("Analyzing recall patterns for the past {$days} days...");
        if ($userId) {
            $this->info("Filtering for user ID: {$userId}");
        }
        
        $analyzer = app(AnalyzeRecallPatternsAction::class);
        $analysis = $analyzer($userId, $days);
        
        // Display summary
        $this->newLine();
        $this->line('<fg=cyan>📊 RECALL ANALYTICS SUMMARY</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $summary = $analysis['summary'];
        $this->line("Total Searches: <fg=green>{$summary['total_searches']}</fg=green>");
        $this->line("Successful Selections: <fg=green>{$summary['successful_selections']}</fg=green>");
        $this->line("Dismissals: <fg=yellow>{$summary['dismissals']}</fg=yellow>");
        $this->line("Success Rate: <fg=green>{$summary['success_rate']}%</fg=green>");
        $this->line("Avg Results per Search: <fg=blue>{$summary['average_results_per_search']}</fg=blue>");
        
        // Selection metrics
        if (!empty($analysis['selection_metrics']['average_click_position'])) {
            $this->newLine();
            $this->line('<fg=cyan>🎯 SELECTION METRICS</fg=cyan>');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━');
            
            $metrics = $analysis['selection_metrics'];
            $this->line("Average Click Position: <fg=blue>{$metrics['average_click_position']}</fg=blue>");
            
            $topN = $metrics['top_n_performance'];
            $this->line("Top-1 Selections: <fg=green>{$topN['top_1']['percentage']}%</fg=green>");
            $this->line("Top-3 Selections: <fg=green>{$topN['top_3']['percentage']}%</fg=green>");
            $this->line("Top-5 Selections: <fg=green>{$topN['top_5']['percentage']}%</fg=green>");
        }
        
        // Query patterns
        if (!empty($analysis['query_patterns']['most_frequent_queries'])) {
            $this->newLine();
            $this->line('<fg=cyan>🔍 POPULAR QUERIES</fg=cyan>');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━');
            
            foreach (array_slice($analysis['query_patterns']['most_frequent_queries'], 0, 5, true) as $query => $count) {
                $this->line("  <fg=white>{$query}</fg=white> <fg=yellow>({$count} times)</fg=yellow>");
            }
        }
        
        // Recommendations
        if (!empty($analysis['recommendations'])) {
            $this->newLine();
            $this->line('<fg=cyan>💡 RECOMMENDATIONS</fg=cyan>');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━');
            
            foreach ($analysis['recommendations'] as $rec) {
                $priority = match($rec['priority']) {
                    'high' => '<fg=red>HIGH</fg=red>',
                    'medium' => '<fg=yellow>MEDIUM</fg=yellow>',
                    'low' => '<fg=green>LOW</fg=green>',
                    default => $rec['priority']
                };
                $this->line("  [{$priority}] {$rec['message']}");
            }
        }
        
        $this->newLine();
        return Command::SUCCESS;
    }
}
