# TELEMETRY-006: Local Telemetry Sink & Query Interface - Implementation Plan

## Estimated Time: 8 hours

## Phase 1: Console Commands for Telemetry Analysis (3 hours)

### 1.1 Create Telemetry Overview Command
**File**: `app/Console/Commands/Telemetry/TelemetryOverviewCommand.php`

```php
<?php

namespace App\Console\Commands\Telemetry;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TelemetryOverviewCommand extends Command
{
    protected $signature = 'telemetry:overview 
                           {--last=1h : Time period (1h, 6h, 24h, 7d)}
                           {--format=table : Output format (table|json)}';
    
    protected $description = 'Show telemetry overview for specified time period';
    
    public function handle()
    {
        $period = $this->option('last');
        $format = $this->option('format');
        
        $since = $this->parsePeriod($period);
        
        $stats = [
            'tool_invocations' => $this->getToolStats($since),
            'command_executions' => $this->getCommandStats($since),
            'chat_activity' => $this->getChatStats($since),
            'processing_activity' => $this->getProcessingStats($since),
            'errors' => $this->getErrorStats($since)
        ];
        
        if ($format === 'json') {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
        } else {
            $this->displayOverviewTable($stats, $period);
        }
    }
    
    private function parsePeriod(string $period): Carbon
    {
        return match($period) {
            '1h' => Carbon::now()->subHour(),
            '6h' => Carbon::now()->subHours(6),
            '24h' => Carbon::now()->subDay(),
            '7d' => Carbon::now()->subWeek(),
            default => Carbon::now()->subHour()
        };
    }
    
    private function getToolStats(Carbon $since): array
    {
        $total = DB::table('tool_invocations')
            ->where('created_at', '>=', $since)
            ->count();
            
        $successful = DB::table('tool_invocations')
            ->where('created_at', '>=', $since)
            ->where('status', 'ok')
            ->count();
            
        $avgDuration = DB::table('tool_invocations')
            ->where('created_at', '>=', $since)
            ->whereNotNull('duration_ms')
            ->avg('duration_ms');
            
        $topTools = DB::table('tool_invocations')
            ->where('created_at', '>=', $since)
            ->select('tool_slug', DB::raw('COUNT(*) as count'))
            ->groupBy('tool_slug')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'tool_slug');
            
        return [
            'total' => $total,
            'successful' => $successful,
            'success_rate' => $total > 0 ? round($successful / $total * 100, 1) : 0,
            'avg_duration_ms' => round($avgDuration ?? 0, 1),
            'top_tools' => $topTools->toArray()
        ];
    }
    
    private function getCommandStats(Carbon $since): array
    {
        $total = DB::table('command_executions')
            ->where('executed_at', '>=', $since)
            ->count();
            
        // Parse from logs for detailed command stats
        return [
            'total' => $total,
            'dry_runs' => DB::table('command_executions')
                ->where('executed_at', '>=', $since)
                ->where('dry_run', true)
                ->count()
        ];
    }
    
    private function getChatStats(Carbon $since): array
    {
        // Parse structured logs for chat statistics
        return $this->parseLogsForStats('chat.', $since);
    }
    
    private function getProcessingStats(Carbon $since): array
    {
        // Parse structured logs for processing statistics
        return $this->parseLogsForStats('processing.', $since);
    }
    
    private function getErrorStats(Carbon $since): array
    {
        $toolErrors = DB::table('tool_invocations')
            ->where('created_at', '>=', $since)
            ->where('status', 'error')
            ->count();
            
        // Parse logs for other error types
        $logErrors = $this->parseLogsForErrors($since);
        
        return [
            'tool_errors' => $toolErrors,
            'total_errors' => $toolErrors + $logErrors
        ];
    }
    
    private function parseLogsForStats(string $prefix, Carbon $since): array
    {
        // Implementation for parsing structured logs
        // This would read recent log files and extract telemetry events
        return ['parsed' => true]; // Placeholder
    }
    
    private function parseLogsForErrors(Carbon $since): int
    {
        // Parse error logs for count
        return 0; // Placeholder
    }
}
```

### 1.2 Create Correlation Trace Command
**File**: `app/Console/Commands/Telemetry/TraceCorrelationCommand.php`

```php
<?php

namespace App\Console\Commands\Telemetry;

use App\Services\Telemetry\ToolInvocationLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TraceCorrelationCommand extends Command
{
    protected $signature = 'telemetry:trace 
                           {correlation-id : Correlation ID to trace}
                           {--format=table : Output format (table|json|timeline)}';
    
    protected $description = 'Trace all activity for a correlation ID';
    
    public function handle()
    {
        $correlationId = $this->argument('correlation-id');
        $format = $this->option('format');
        
        $trace = $this->gatherCorrelationTrace($correlationId);
        
        if (empty($trace['events'])) {
            $this->error("No telemetry found for correlation ID: {$correlationId}");
            return 1;
        }
        
        switch ($format) {
            case 'json':
                $this->line(json_encode($trace, JSON_PRETTY_PRINT));
                break;
            case 'timeline':
                $this->displayTimeline($trace['events']);
                break;
            default:
                $this->displayTraceTable($trace);
        }
    }
    
    private function gatherCorrelationTrace(string $correlationId): array
    {
        $events = [];
        
        // Get tool invocations
        $toolInvocations = ToolInvocationLogger::queryByCorrelation($correlationId);
        foreach ($toolInvocations as $tool) {
            $events[] = [
                'timestamp' => $tool->created_at,
                'type' => 'tool_invocation',
                'details' => "Tool: {$tool->tool_slug}",
                'status' => $tool->status,
                'duration_ms' => $tool->duration_ms,
                'data' => $tool
            ];
        }
        
        // Get command executions
        $commands = DB::table('command_executions')
            ->whereJsonContains('result->correlation_id', $correlationId)
            ->orWhere('id', 'LIKE', $correlationId) // if execution_id matches
            ->get();
            
        foreach ($commands as $command) {
            $events[] = [
                'timestamp' => $command->executed_at,
                'type' => 'command_execution',
                'details' => "Command: {$command->command_name}",
                'status' => 'completed',
                'duration_ms' => null,
                'data' => $command
            ];
        }
        
        // Parse logs for chat and processing events
        $logEvents = $this->parseLogsForCorrelation($correlationId);
        $events = array_merge($events, $logEvents);
        
        // Sort by timestamp
        usort($events, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        
        return [
            'correlation_id' => $correlationId,
            'events' => $events,
            'summary' => $this->generateTraceSummary($events)
        ];
    }
    
    private function parseLogsForCorrelation(string $correlationId): array
    {
        // Parse recent log files for correlation ID
        $events = [];
        
        // Implementation would read log files and extract JSON events
        // matching the correlation ID
        
        return $events;
    }
    
    private function displayTimeline(array $events): void
    {
        $this->info("Timeline for correlation ID:");
        
        foreach ($events as $event) {
            $time = $event['timestamp']->format('H:i:s.v');
            $duration = $event['duration_ms'] ? " ({$event['duration_ms']}ms)" : '';
            $status = $event['status'] === 'error' ? ' ❌' : ' ✅';
            
            $this->line("{$time} [{$event['type']}] {$event['details']}{$duration}{$status}");
        }
    }
}
```

### 1.3 Create Performance Analysis Command
**File**: `app/Console/Commands/Telemetry/PerformanceAnalysisCommand.php`

```php
<?php

namespace App\Console\Commands\Telemetry;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerformanceAnalysisCommand extends Command
{
    protected $signature = 'telemetry:performance 
                           {--type=all : Analysis type (all|chat|commands|tools|processing)}
                           {--last=24h : Time period}
                           {--format=table : Output format}';
    
    protected $description = 'Analyze performance metrics from telemetry data';
    
    public function handle()
    {
        $type = $this->option('type');
        $period = $this->option('last');
        $format = $this->option('format');
        
        $since = $this->parsePeriod($period);
        
        $analysis = match($type) {
            'chat' => $this->analyzeChatPerformance($since),
            'commands' => $this->analyzeCommandPerformance($since),
            'tools' => $this->analyzeToolPerformance($since),
            'processing' => $this->analyzeProcessingPerformance($since),
            default => $this->analyzeAllPerformance($since)
        };
        
        if ($format === 'json') {
            $this->line(json_encode($analysis, JSON_PRETTY_PRINT));
        } else {
            $this->displayPerformanceTable($analysis, $type);
        }
    }
    
    private function analyzeToolPerformance(Carbon $since): array
    {
        $tools = DB::table('tool_invocations')
            ->where('created_at', '>=', $since)
            ->whereNotNull('duration_ms')
            ->select([
                'tool_slug',
                DB::raw('COUNT(*) as invocations'),
                DB::raw('AVG(duration_ms) as avg_duration'),
                DB::raw('MIN(duration_ms) as min_duration'),
                DB::raw('MAX(duration_ms) as max_duration'),
                DB::raw('PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration_ms) as p95_duration')
            ])
            ->groupBy('tool_slug')
            ->orderByDesc('invocations')
            ->get();
            
        return [
            'type' => 'tools',
            'period' => $since->diffForHumans(),
            'tools' => $tools->map(function ($tool) {
                return [
                    'tool' => $tool->tool_slug,
                    'invocations' => $tool->invocations,
                    'avg_ms' => round($tool->avg_duration, 1),
                    'min_ms' => round($tool->min_duration, 1),
                    'max_ms' => round($tool->max_duration, 1),
                    'p95_ms' => round($tool->p95_duration ?? 0, 1)
                ];
            })->toArray()
        ];
    }
    
    private function displayPerformanceTable(array $analysis, string $type): void
    {
        $this->info("Performance Analysis: {$type} ({$analysis['period']})");
        
        if ($type === 'tools' && isset($analysis['tools'])) {
            $this->table([
                'Tool', 'Invocations', 'Avg (ms)', 'Min (ms)', 'Max (ms)', 'P95 (ms)'
            ], collect($analysis['tools'])->map(function ($tool) {
                return [
                    $tool['tool'],
                    $tool['invocations'],
                    $tool['avg_ms'],
                    $tool['min_ms'],
                    $tool['max_ms'],
                    $tool['p95_ms']
                ];
            }));
        }
    }
}
```

## Phase 2: Web Dashboard Foundation (3 hours)

### 2.1 Create Internal Routes for Telemetry Dashboard
**File**: `routes/internal.php` (enhance existing)

```php
// Add to existing internal routes
Route::prefix('telemetry')->group(function () {
    Route::get('/', [TelemetryController::class, 'overview'])->name('telemetry.overview');
    Route::get('/chat', [TelemetryController::class, 'chat'])->name('telemetry.chat');
    Route::get('/commands', [TelemetryController::class, 'commands'])->name('telemetry.commands');
    Route::get('/tools', [TelemetryController::class, 'tools'])->name('telemetry.tools');
    Route::get('/errors', [TelemetryController::class, 'errors'])->name('telemetry.errors');
    Route::get('/trace/{correlationId}', [TelemetryController::class, 'trace'])->name('telemetry.trace');
    
    // API endpoints for dashboard data
    Route::get('/api/overview', [TelemetryApiController::class, 'overview']);
    Route::get('/api/performance', [TelemetryApiController::class, 'performance']);
    Route::get('/api/trace/{correlationId}', [TelemetryApiController::class, 'trace']);
});
```

### 2.2 Create Telemetry Controller
**File**: `app/Http/Controllers/Internal/TelemetryController.php`

```php
<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Telemetry\TelemetryAggregator;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{
    public function overview(Request $request)
    {
        $period = $request->get('period', '24h');
        $aggregator = app(TelemetryAggregator::class);
        
        $data = [
            'overview' => $aggregator->getOverview($period),
            'recent_errors' => $aggregator->getRecentErrors(10),
            'performance_summary' => $aggregator->getPerformanceSummary($period)
        ];
        
        return view('internal.telemetry.overview', compact('data', 'period'));
    }
    
    public function chat(Request $request)
    {
        $period = $request->get('period', '24h');
        $aggregator = app(TelemetryAggregator::class);
        
        $data = [
            'chat_stats' => $aggregator->getChatStats($period),
            'recent_conversations' => $aggregator->getRecentConversations(20),
            'provider_usage' => $aggregator->getProviderUsage($period)
        ];
        
        return view('internal.telemetry.chat', compact('data', 'period'));
    }
    
    public function tools(Request $request)
    {
        $period = $request->get('period', '24h');
        $aggregator = app(TelemetryAggregator::class);
        
        $data = [
            'tool_stats' => $aggregator->getToolStats($period),
            'tool_performance' => $aggregator->getToolPerformance($period),
            'recent_invocations' => $aggregator->getRecentToolInvocations(50)
        ];
        
        return view('internal.telemetry.tools', compact('data', 'period'));
    }
    
    public function trace(string $correlationId)
    {
        $aggregator = app(TelemetryAggregator::class);
        $trace = $aggregator->getCorrelationTrace($correlationId);
        
        if (empty($trace['events'])) {
            abort(404, "No telemetry found for correlation ID: {$correlationId}");
        }
        
        return view('internal.telemetry.trace', compact('trace', 'correlationId'));
    }
}
```

### 2.3 Create Telemetry Aggregator Service
**File**: `app/Services/Telemetry/TelemetryAggregator.php`

```php
<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TelemetryAggregator
{
    public function getOverview(string $period): array
    {
        $since = $this->parsePeriod($period);
        
        return [
            'period' => $period,
            'since' => $since,
            'tool_invocations' => $this->getToolOverview($since),
            'command_executions' => $this->getCommandOverview($since),
            'error_summary' => $this->getErrorSummary($since)
        ];
    }
    
    public function getToolStats(string $period): array
    {
        $since = $this->parsePeriod($period);
        
        return DB::table('tool_invocations')
            ->where('created_at', '>=', $since)
            ->select([
                'tool_slug',
                DB::raw('COUNT(*) as total_invocations'),
                DB::raw('SUM(CASE WHEN status = "ok" THEN 1 ELSE 0 END) as successful'),
                DB::raw('AVG(duration_ms) as avg_duration'),
                DB::raw('MAX(duration_ms) as max_duration')
            ])
            ->groupBy('tool_slug')
            ->orderByDesc('total_invocations')
            ->get()
            ->map(function ($stat) {
                $stat->success_rate = $stat->total_invocations > 0 
                    ? round($stat->successful / $stat->total_invocations * 100, 1) 
                    : 0;
                $stat->avg_duration = round($stat->avg_duration ?? 0, 1);
                $stat->max_duration = round($stat->max_duration ?? 0, 1);
                return $stat;
            });
    }
    
    public function getCorrelationTrace(string $correlationId): array
    {
        $events = [];
        
        // Get tool invocations
        $tools = ToolInvocationLogger::queryByCorrelation($correlationId);
        foreach ($tools as $tool) {
            $events[] = [
                'timestamp' => $tool->created_at,
                'type' => 'tool_invocation',
                'component' => 'Tools',
                'action' => $tool->tool_slug,
                'status' => $tool->status,
                'duration_ms' => $tool->duration_ms,
                'details' => json_decode($tool->request ?? '{}', true)
            ];
        }
        
        // Sort events by timestamp
        usort($events, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        
        return [
            'correlation_id' => $correlationId,
            'events' => $events,
            'summary' => $this->generateTraceSummary($events)
        ];
    }
    
    private function parsePeriod(string $period): Carbon
    {
        return match($period) {
            '1h' => Carbon::now()->subHour(),
            '6h' => Carbon::now()->subHours(6),
            '24h' => Carbon::now()->subDay(),
            '7d' => Carbon::now()->subWeek(),
            default => Carbon::now()->subDay()
        };
    }
}
```

## Phase 3: Dashboard Views (1.5 hours)

### 3.1 Create Overview Dashboard View
**File**: `resources/views/internal/telemetry/overview.blade.php`

```php
@extends('internal.layout')

@section('title', 'Telemetry Overview')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Telemetry Overview</h1>
        
        <div class="flex space-x-2">
            <select id="period-selector" class="border rounded px-3 py-1">
                <option value="1h" {{ $period === '1h' ? 'selected' : '' }}>Last Hour</option>
                <option value="6h" {{ $period === '6h' ? 'selected' : '' }}>Last 6 Hours</option>
                <option value="24h" {{ $period === '24h' ? 'selected' : '' }}>Last 24 Hours</option>
                <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>Last 7 Days</option>
            </select>
            <button onclick="location.reload()" class="bg-blue-500 text-white px-3 py-1 rounded">
                Refresh
            </button>
        </div>
    </div>
    
    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Tool Invocations</h3>
            <div class="text-3xl font-bold text-blue-600">
                {{ $data['overview']['tool_invocations']['total'] ?? 0 }}
            </div>
            <div class="text-sm text-gray-500">
                {{ $data['overview']['tool_invocations']['success_rate'] ?? 0 }}% success rate
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Command Executions</h3>
            <div class="text-3xl font-bold text-green-600">
                {{ $data['overview']['command_executions']['total'] ?? 0 }}
            </div>
            <div class="text-sm text-gray-500">
                {{ $data['overview']['command_executions']['dry_runs'] ?? 0 }} dry runs
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Errors</h3>
            <div class="text-3xl font-bold {{ $data['overview']['error_summary']['total'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                {{ $data['overview']['error_summary']['total'] ?? 0 }}
            </div>
            <div class="text-sm text-gray-500">
                in last {{ $period }}
            </div>
        </div>
    </div>
    
    <!-- Recent Errors -->
    @if(!empty($data['recent_errors']))
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Recent Errors</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Error</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correlation</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($data['recent_errors'] as $error)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $error->created_at->format('H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $error->type ?? 'tool' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ Str::limit($error->error ?? 'Unknown error', 80) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($error->correlation_id)
                                <a href="{{ route('telemetry.trace', $error->correlation_id) }}" 
                                   class="text-blue-600 hover:text-blue-800">
                                    {{ Str::limit($error->correlation_id, 8) }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    
    <!-- Navigation Links -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('telemetry.chat') }}" class="bg-blue-100 hover:bg-blue-200 rounded-lg p-4 text-center">
            <div class="text-2xl mb-2">💬</div>
            <div class="font-semibold">Chat Telemetry</div>
        </a>
        <a href="{{ route('telemetry.commands') }}" class="bg-green-100 hover:bg-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl mb-2">⚡</div>
            <div class="font-semibold">Commands</div>
        </a>
        <a href="{{ route('telemetry.tools') }}" class="bg-purple-100 hover:bg-purple-200 rounded-lg p-4 text-center">
            <div class="text-2xl mb-2">🔧</div>
            <div class="font-semibold">Tools</div>
        </a>
        <a href="{{ route('telemetry.errors') }}" class="bg-red-100 hover:bg-red-200 rounded-lg p-4 text-center">
            <div class="text-2xl mb-2">🚨</div>
            <div class="font-semibold">Error Analysis</div>
        </a>
    </div>
</div>

<script>
document.getElementById('period-selector').addEventListener('change', function() {
    const period = this.value;
    const url = new URL(window.location);
    url.searchParams.set('period', period);
    window.location = url;
});
</script>
@endsection
```

### 3.2 Create Tools Dashboard View
**File**: `resources/views/internal/telemetry/tools.blade.php`

```php
@extends('internal.layout')

@section('title', 'Tool Telemetry')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Tool Telemetry</h1>
        <a href="{{ route('telemetry.overview') }}" class="text-blue-600 hover:text-blue-800">
            ← Back to Overview
        </a>
    </div>
    
    <!-- Tool Statistics -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Tool Performance ({{ $period }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tool</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invocations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Success Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Max Duration</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($data['tool_stats'] as $tool)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $tool->tool_slug }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $tool->total_invocations }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $tool->success_rate >= 95 ? 'bg-green-100 text-green-800' : 
                                   ($tool->success_rate >= 80 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ $tool->success_rate }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $tool->avg_duration }}ms
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $tool->max_duration }}ms
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Tool Invocations -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Recent Tool Invocations</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tool</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correlation</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($data['recent_invocations'] as $invocation)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $invocation->created_at->format('H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $invocation->tool_slug }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $invocation->status === 'ok' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $invocation->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $invocation->duration_ms ? round($invocation->duration_ms, 1) . 'ms' : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($invocation->correlation_id)
                                <a href="{{ route('telemetry.trace', $invocation->correlation_id) }}" 
                                   class="text-blue-600 hover:text-blue-800">
                                    {{ Str::limit($invocation->correlation_id, 8) }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
```

## Phase 4: Testing & Documentation (0.5 hours)

### 4.1 Integration Tests for Console Commands
**File**: `tests/Feature/Console/TelemetryCommandsTest.php`

```php
<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryCommandsTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_telemetry_overview_command()
    {
        $this->artisan('telemetry:overview --last=1h')
            ->assertExitCode(0);
    }
    
    public function test_telemetry_trace_command()
    {
        // Create test data with correlation ID
        $correlationId = 'test-correlation-123';
        
        // ... create test tool invocations, etc.
        
        $this->artisan("telemetry:trace {$correlationId}")
            ->assertExitCode(0);
    }
}
```

## Implementation Checklist

- [ ] Create telemetry overview console command
- [ ] Create correlation trace console command  
- [ ] Create performance analysis console command
- [ ] Create internal telemetry routes
- [ ] Create telemetry controller
- [ ] Create telemetry aggregator service
- [ ] Create overview dashboard view
- [ ] Create tools dashboard view
- [ ] Create correlation trace view
- [ ] Integration tests for console commands
- [ ] Integration tests for web dashboard

## Success Metrics

- Console commands provide comprehensive telemetry analysis
- Web dashboard loads in <500ms
- Correlation tracing works across all telemetry systems
- Query performance <200ms for single correlation analysis
- Memory usage <15MB additional overhead for telemetry interface