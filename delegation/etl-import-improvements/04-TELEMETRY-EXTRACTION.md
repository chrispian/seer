# Area 4: Agent Telemetry Extraction Pipeline

## Overview
Build comprehensive telemetry extraction system to analyze agent logs and derive actionable insights about performance, tool usage, error patterns, and session characteristics.

## Current Problems
- Agent logs stored but not analyzed
- No visibility into tool usage patterns
- Missing performance metrics
- Cannot identify error trends
- No session complexity analysis
- Limited insights for optimization

## Proposed Solution

### Core Telemetry Architecture

```php
namespace App\Services\Telemetry;

class AgentTelemetryService
{
    protected MetricsExtractor $metrics;
    protected PatternAnalyzer $patterns;
    protected SessionAnalyzer $sessions;
    protected TelemetryStorage $storage;
    
    public function processLogs(Collection $logs): TelemetryResult
    {
        $result = new TelemetryResult();
        
        // Group by session
        $sessions = $logs->groupBy('session_id');
        
        foreach ($sessions as $sessionId => $sessionLogs) {
            $sessionMetrics = $this->analyzeSession($sessionId, $sessionLogs);
            $result->addSession($sessionMetrics);
        }
        
        // Aggregate metrics
        $result->aggregate();
        
        // Store in time-series database
        $this->storage->store($result);
        
        return $result;
    }
}
```

### Metrics Extraction

```php
class MetricsExtractor
{
    public function extract(AgentLog $log): array
    {
        return [
            'tokens' => $this->extractTokenMetrics($log),
            'tools' => $this->extractToolMetrics($log),
            'timing' => $this->extractTimingMetrics($log),
            'errors' => $this->extractErrorMetrics($log),
            'context' => $this->extractContextMetrics($log),
        ];
    }
    
    protected function extractTokenMetrics(AgentLog $log): array
    {
        $content = $log->structured_data['payload']['content'] ?? '';
        
        // Estimate tokens using tiktoken or similar
        $inputTokens = $this->estimateTokens($content);
        
        // Extract from API response if available
        $usage = $log->structured_data['usage'] ?? null;
        
        return [
            'input_tokens' => $usage['input_tokens'] ?? $inputTokens,
            'output_tokens' => $usage['output_tokens'] ?? null,
            'total_tokens' => $usage['total_tokens'] ?? null,
            'estimated' => !isset($usage['input_tokens']),
            'model' => $log->model,
            'provider' => $log->provider,
        ];
    }
    
    protected function extractToolMetrics(AgentLog $log): ?array
    {
        if (!$log->tool_calls) {
            return null;
        }
        
        $toolData = $log->tool_calls;
        
        return [
            'tool_name' => $toolData['name'] ?? null,
            'tool_category' => $this->categorizeT Tool($toolData['name']),
            'execution_time' => $this->calculateExecutionTime($log),
            'success' => !($toolData['is_error'] ?? false),
            'error_type' => $toolData['error_type'] ?? null,
            'input_size' => strlen(json_encode($toolData['input'] ?? [])),
            'output_size' => strlen(json_encode($toolData['content'] ?? [])),
        ];
    }
    
    protected function extractTimingMetrics(AgentLog $log): array
    {
        return [
            'timestamp' => $log->log_timestamp,
            'response_time' => $this->calculateResponseTime($log),
            'queue_time' => $this->calculateQueueTime($log),
            'processing_time' => $this->calculateProcessingTime($log),
        ];
    }
}
```

### Pattern Analysis

```php
class PatternAnalyzer
{
    protected array $errorPatterns = [
        'rate_limit' => '/rate.?limit|429|too.?many.?requests/i',
        'timeout' => '/timeout|timed.?out|deadline/i',
        'auth_failure' => '/auth|unauthorized|403|401/i',
        'network' => '/network|connection|refused|unreachable/i',
        'validation' => '/validation|invalid|schema/i',
        'tool_error' => '/tool.?error|tool.?failed/i',
    ];
    
    protected array $behaviorPatterns = [
        'retry' => '/retry|retrying|attempt/i',
        'fallback' => '/fallback|alternative|instead/i',
        'clarification' => '/clarify|unclear|ambiguous/i',
        'exploration' => '/searching|looking|finding/i',
    ];
    
    public function analyzePatterns(Collection $logs): array
    {
        $patterns = [
            'errors' => $this->detectErrorPatterns($logs),
            'behaviors' => $this->detectBehaviorPatterns($logs),
            'sequences' => $this->detectToolSequences($logs),
            'anomalies' => $this->detectAnomalies($logs),
        ];
        
        return $patterns;
    }
    
    protected function detectToolSequences(Collection $logs): array
    {
        $sequences = [];
        $currentSequence = [];
        
        foreach ($logs as $log) {
            if ($log->tool_calls) {
                $currentSequence[] = $log->tool_calls['name'];
                
                if (count($currentSequence) >= 2) {
                    $key = implode('->', $currentSequence);
                    $sequences[$key] = ($sequences[$key] ?? 0) + 1;
                }
                
                if (count($currentSequence) > 5) {
                    array_shift($currentSequence);
                }
            }
        }
        
        return $sequences;
    }
    
    protected function detectAnomalies(Collection $logs): array
    {
        $anomalies = [];
        
        // Detect unusual response times
        $responseTimes = $logs->pluck('response_time')->filter();
        $mean = $responseTimes->average();
        $stdDev = $this->standardDeviation($responseTimes);
        
        foreach ($logs as $log) {
            if ($log->response_time > ($mean + 3 * $stdDev)) {
                $anomalies[] = [
                    'type' => 'response_time_spike',
                    'log_id' => $log->id,
                    'value' => $log->response_time,
                    'threshold' => $mean + 3 * $stdDev,
                ];
            }
        }
        
        // Detect error clusters
        $errorClusters = $this->detectErrorClusters($logs);
        foreach ($errorClusters as $cluster) {
            $anomalies[] = [
                'type' => 'error_cluster',
                'start' => $cluster['start'],
                'end' => $cluster['end'],
                'count' => $cluster['count'],
            ];
        }
        
        return $anomalies;
    }
}
```

### Session Analysis

```php
class SessionAnalyzer
{
    public function analyzeSession(string $sessionId, Collection $logs): SessionMetrics
    {
        $metrics = new SessionMetrics($sessionId);
        
        // Basic metrics
        $metrics->duration = $this->calculateDuration($logs);
        $metrics->messageCount = $logs->count();
        $metrics->toolCallCount = $logs->where('tool_calls', '!=', null)->count();
        
        // Complexity assessment
        $metrics->complexity = $this->assessComplexity($logs);
        
        // Success rate
        $metrics->successRate = $this->calculateSuccessRate($logs);
        
        // Token usage
        $metrics->totalTokens = $this->calculateTotalTokens($logs);
        $metrics->tokenEfficiency = $this->calculateTokenEfficiency($logs);
        
        // Tool usage breakdown
        $metrics->toolUsage = $this->analyzeToolUsage($logs);
        
        // Error analysis
        $metrics->errors = $this->analyzeErrors($logs);
        
        // Performance
        $metrics->avgResponseTime = $this->calculateAverageResponseTime($logs);
        $metrics->p95ResponseTime = $this->calculatePercentileResponseTime($logs, 95);
        
        return $metrics;
    }
    
    protected function assessComplexity(Collection $logs): string
    {
        $score = 0;
        
        // Factor in message count
        $messageCount = $logs->count();
        if ($messageCount > 50) $score += 3;
        elseif ($messageCount > 20) $score += 2;
        elseif ($messageCount > 10) $score += 1;
        
        // Factor in unique tools used
        $uniqueTools = $logs->pluck('tool_calls.name')->filter()->unique()->count();
        if ($uniqueTools > 10) $score += 3;
        elseif ($uniqueTools > 5) $score += 2;
        elseif ($uniqueTools > 2) $score += 1;
        
        // Factor in error rate
        $errorRate = $this->calculateErrorRate($logs);
        if ($errorRate > 0.2) $score += 2;
        elseif ($errorRate > 0.1) $score += 1;
        
        // Factor in duration
        $duration = $this->calculateDuration($logs);
        if ($duration > 3600) $score += 2; // Over 1 hour
        elseif ($duration > 1800) $score += 1; // Over 30 minutes
        
        // Classify complexity
        if ($score >= 8) return 'very_high';
        if ($score >= 6) return 'high';
        if ($score >= 4) return 'medium';
        if ($score >= 2) return 'low';
        return 'very_low';
    }
    
    protected function analyzeToolUsage(Collection $logs): array
    {
        $toolLogs = $logs->where('tool_calls', '!=', null);
        
        $usage = [];
        foreach ($toolLogs as $log) {
            $toolName = $log->tool_calls['name'] ?? 'unknown';
            
            if (!isset($usage[$toolName])) {
                $usage[$toolName] = [
                    'count' => 0,
                    'success_count' => 0,
                    'error_count' => 0,
                    'total_time' => 0,
                    'avg_time' => 0,
                ];
            }
            
            $usage[$toolName]['count']++;
            
            if ($log->tool_calls['is_error'] ?? false) {
                $usage[$toolName]['error_count']++;
            } else {
                $usage[$toolName]['success_count']++;
            }
            
            $execTime = $this->calculateExecutionTime($log);
            $usage[$toolName]['total_time'] += $execTime;
        }
        
        // Calculate averages
        foreach ($usage as $toolName => &$stats) {
            $stats['avg_time'] = $stats['count'] > 0 
                ? $stats['total_time'] / $stats['count'] 
                : 0;
            $stats['success_rate'] = $stats['count'] > 0
                ? $stats['success_count'] / $stats['count']
                : 0;
        }
        
        return $usage;
    }
}
```

### Real-time Telemetry Dashboard

```php
class TelemetryDashboard
{
    public function getRealtimeMetrics(): array
    {
        return [
            'current' => $this->getCurrentMetrics(),
            'trends' => $this->getTrends(),
            'alerts' => $this->getActiveAlerts(),
            'recommendations' => $this->getRecommendations(),
        ];
    }
    
    protected function getCurrentMetrics(): array
    {
        $recentLogs = AgentLog::where('created_at', '>', now()->subMinutes(5))->get();
        
        return [
            'active_sessions' => $recentLogs->pluck('session_id')->unique()->count(),
            'requests_per_minute' => $recentLogs->count() / 5,
            'avg_response_time' => $recentLogs->avg('response_time'),
            'error_rate' => $this->calculateErrorRate($recentLogs),
            'token_usage_rate' => $this->calculateTokenRate($recentLogs),
        ];
    }
    
    protected function getTrends(): array
    {
        $hourly = $this->getHourlyMetrics();
        $daily = $this->getDailyMetrics();
        
        return [
            'token_usage_trend' => $this->calculateTrend($hourly->pluck('tokens')),
            'error_rate_trend' => $this->calculateTrend($hourly->pluck('error_rate')),
            'complexity_trend' => $this->calculateTrend($daily->pluck('avg_complexity')),
            'tool_diversity_trend' => $this->calculateTrend($daily->pluck('unique_tools')),
        ];
    }
    
    protected function getActiveAlerts(): array
    {
        $alerts = [];
        
        // Check for high error rate
        $errorRate = $this->getCurrentErrorRate();
        if ($errorRate > 0.15) {
            $alerts[] = [
                'type' => 'high_error_rate',
                'severity' => 'warning',
                'message' => "Error rate at {$errorRate}% (threshold: 15%)",
            ];
        }
        
        // Check for token usage spike
        $tokenRate = $this->getCurrentTokenRate();
        $avgTokenRate = $this->getAverageTokenRate();
        if ($tokenRate > $avgTokenRate * 2) {
            $alerts[] = [
                'type' => 'token_spike',
                'severity' => 'info',
                'message' => "Token usage 2x above average",
            ];
        }
        
        return $alerts;
    }
    
    protected function getRecommendations(): array
    {
        $recommendations = [];
        
        // Analyze tool usage patterns
        $toolStats = $this->getToolStatistics();
        foreach ($toolStats as $tool => $stats) {
            if ($stats['error_rate'] > 0.3) {
                $recommendations[] = [
                    'type' => 'tool_optimization',
                    'tool' => $tool,
                    'message' => "Consider optimizing {$tool} - 30% error rate",
                ];
            }
        }
        
        // Analyze session patterns
        $sessionStats = $this->getSessionStatistics();
        if ($sessionStats['avg_retries'] > 2) {
            $recommendations[] = [
                'type' => 'retry_reduction',
                'message' => "High retry rate detected - review error handling",
            ];
        }
        
        return $recommendations;
    }
}
```

### Telemetry Storage

```php
class TelemetryStorage
{
    protected $influxDB;
    
    public function store(TelemetryResult $result): void
    {
        $points = [];
        
        foreach ($result->getSessions() as $session) {
            $points[] = [
                'measurement' => 'agent_sessions',
                'tags' => [
                    'session_id' => $session->id,
                    'provider' => $session->provider,
                    'model' => $session->model,
                    'complexity' => $session->complexity,
                ],
                'fields' => [
                    'duration' => $session->duration,
                    'message_count' => $session->messageCount,
                    'tool_count' => $session->toolCallCount,
                    'token_count' => $session->totalTokens,
                    'success_rate' => $session->successRate,
                    'avg_response_time' => $session->avgResponseTime,
                ],
                'timestamp' => $session->startTime,
            ];
        }
        
        $this->influxDB->writePoints($points);
    }
    
    public function query(string $query): array
    {
        return $this->influxDB->query($query)->getPoints();
    }
}
```

## Database Schema

```sql
-- Telemetry summary table
CREATE TABLE agent_telemetry_summaries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    session_id VARCHAR(255),
    provider VARCHAR(50),
    model VARCHAR(100),
    start_time TIMESTAMP,
    end_time TIMESTAMP,
    duration INT,
    message_count INT,
    tool_call_count INT,
    total_tokens INT,
    success_rate FLOAT,
    complexity VARCHAR(20),
    avg_response_time FLOAT,
    p95_response_time FLOAT,
    tool_usage JSONB,
    error_breakdown JSONB,
    patterns JSONB,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_telemetry_session ON agent_telemetry_summaries(session_id);
CREATE INDEX idx_telemetry_time ON agent_telemetry_summaries(start_time, end_time);
CREATE INDEX idx_telemetry_provider ON agent_telemetry_summaries(provider, model);

-- Telemetry alerts table
CREATE TABLE telemetry_alerts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    type VARCHAR(50),
    severity VARCHAR(20),
    message TEXT,
    context JSONB,
    triggered_at TIMESTAMP DEFAULT NOW(),
    acknowledged BOOLEAN DEFAULT FALSE,
    acknowledged_by UUID REFERENCES users(id),
    acknowledged_at TIMESTAMP
);

CREATE INDEX idx_alerts_triggered ON telemetry_alerts(triggered_at);
CREATE INDEX idx_alerts_acknowledged ON telemetry_alerts(acknowledged);
```

## Implementation Strategy

### Phase 1: Extraction Foundation (Week 1)
1. Build MetricsExtractor class
2. Implement token estimation
3. Create timing calculations
4. Add tool metrics extraction

### Phase 2: Analysis Engine (Week 2)
1. Implement PatternAnalyzer
2. Build SessionAnalyzer
3. Create complexity scoring
4. Add anomaly detection

### Phase 3: Storage & Visualization (Week 3)
1. Set up InfluxDB integration
2. Build real-time dashboard
3. Create alerting system
4. Add recommendations engine

## Success Criteria
- 100% of sessions analyzed
- <1 minute processing lag
- Accurate token estimation (±10%)
- Tool usage patterns identified
- Actionable recommendations generated

## Monitoring & Alerts
```php
// config/telemetry.php
return [
    'alerts' => [
        'error_rate_threshold' => 0.15,
        'token_spike_multiplier' => 2,
        'response_time_p95' => 5000, // ms
        'session_duration_max' => 7200, // 2 hours
    ],
    
    'analysis' => [
        'complexity_factors' => [
            'message_count' => [10, 20, 50],
            'unique_tools' => [2, 5, 10],
            'duration' => [1800, 3600], // seconds
            'error_rate' => [0.1, 0.2],
        ],
    ],
    
    'storage' => [
        'driver' => 'influxdb',
        'retention_days' => 90,
        'downsample_after' => 30, // days
    ],
];
```

## Next Steps
1. Deploy InfluxDB instance
2. Create Grafana dashboards
3. Set up alert notifications
4. Build admin telemetry UI
5. Create weekly performance reports