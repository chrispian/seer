# AGENT-001: Agent Broker Implementation

## Agent Profile
**Type**: Senior Engineer/Code Reviewer  
**Specialization**: AI Model Management, Context-Aware Systems, User Experience

## Task Overview
Implement intelligent agent/model selection that considers project context, user preferences, vault settings, and task requirements, with UI integration for user control and override capabilities.

## Context
Currently, model selection is static and hard-coded. Users get the same AI model regardless of their project context, task type, or preferences. We need dynamic agent selection that adapts to context while allowing user control.

## Technical Requirements

### **Agent Broker Architecture**
```php
interface AgentBroker
{
    public function selectAgent(AgentSelectionRequest $request): AgentSelection;
    public function getAvailableAgents(ContextRequest $context): array;
    public function recordAgentDecision(AgentSelection $selection, AgentResult $result): void;
    public function getUserPreferences(int $userId): AgentPreferences;
    public function getProjectDefaults(int $projectId): AgentDefaults;
}
```

### **Context-Aware Selection Logic**
```php
class AgentSelectionRules
{
    // Selection criteria priority (highest to lowest)
    const PRIORITIES = [
        'user_override' => 100,      // User explicitly chose agent/model
        'conversation_context' => 90, // Conversation-specific agent
        'task_requirements' => 80,    // Task needs specific capabilities
        'project_preferences' => 70,  // Project-level agent settings
        'vault_defaults' => 60,      // Vault-specific defaults
        'user_preferences' => 50,    // User's general preferences
        'content_analysis' => 40,    // Content-based recommendation
        'system_defaults' => 30,     // Global fallback
    ];
}
```

### **Configuration Framework**
```php
// config/fragments.php additions
'agent_broker' => [
    'enabled' => env('AGENT_BROKER_ENABLED', true),
    'debug_mode' => env('AGENT_BROKER_DEBUG', false),
    
    'selection_strategy' => [
        'use_context_analysis' => true,
        'use_task_detection' => true,
        'use_content_analysis' => true,
        'fallback_strategy' => 'user_preference', // or 'system_default'
    ],
    
    'agent_profiles' => [
        'general' => [
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet-latest',
            'capabilities' => ['general_chat', 'analysis', 'writing'],
            'use_cases' => ['default', 'conversation', 'questions'],
        ],
        'coding' => [
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet-latest',
            'capabilities' => ['code_analysis', 'debugging', 'technical_writing'],
            'use_cases' => ['code_review', 'programming', 'technical_tasks'],
        ],
        'quick' => [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'capabilities' => ['quick_responses', 'simple_tasks'],
            'use_cases' => ['simple_questions', 'quick_tasks', 'clarifications'],
        ],
        'local' => [
            'provider' => 'ollama',
            'model' => 'llama3:latest',
            'capabilities' => ['privacy', 'offline'],
            'use_cases' => ['sensitive_data', 'local_processing'],
        ],
    ],
    
    'task_detection' => [
        'coding_patterns' => [
            '/```[a-z]*/',              // Code blocks
            '/\b(function|class|def|var|const)\b/',
            '/\b(git|github|api|database|bug|debug)\b/i',
        ],
        'analysis_patterns' => [
            '/\b(analyze|review|examine|evaluate)\b/i',
            '/\b(summary|report|breakdown)\b/i',
        ],
        'quick_patterns' => [
            '/^(yes|no|maybe|ok|thanks|sure)\s*$/i',
            '/^.{1,20}$/s',             // Very short messages
        ],
    ],
    
    'project_overrides' => [
        // Project-specific agent preferences
        'default_agent' => null,      // null = use broker logic
        'allowed_agents' => null,     // null = all allowed
        'require_local' => false,     // Force local models only
    ],
    
    'ui_integration' => [
        'show_agent_selector' => env('SHOW_AGENT_SELECTOR', true),
        'show_selection_reason' => env('SHOW_SELECTION_REASON', true),
        'allow_mid_conversation_switch' => true,
        'remember_user_choices' => true,
    ],
],
```

## Implementation Plan

### **Phase 1: Core Agent Broker Service**
```php
<?php

namespace App\Services;

use App\DTOs\AgentSelectionRequest;
use App\DTOs\AgentSelection;
use Illuminate\Support\Facades\Log;

class AgentBroker
{
    public function __construct(
        private ContextAnalyzer $contextAnalyzer,
        private TaskDetector $taskDetector,
        private UserPreferenceService $userPreferences,
        private ProjectSettingsService $projectSettings
    ) {}

    public function selectAgent(AgentSelectionRequest $request): AgentSelection
    {
        $startTime = microtime(true);
        
        Log::info('🤖 Agent Broker: Selecting agent', [
            'conversation_id' => $request->conversationId,
            'user_id' => $request->userId,
            'project_id' => $request->projectId,
            'has_override' => !is_null($request->userOverride),
        ]);

        // Apply selection rules in priority order
        $selection = $this->applySelectionRules($request);
        
        // Validate selected agent is available
        $selection = $this->validateAndFallback($selection, $request);
        
        // Record decision for analytics
        $this->recordSelectionDecision($selection, $request);
        
        $selectionTime = (microtime(true) - $startTime) * 1000;
        
        Log::info('🤖 Agent selected', [
            'agent_profile' => $selection->agentProfile,
            'provider' => $selection->provider,
            'model' => $selection->model,
            'reason' => $selection->selectionReason,
            'confidence' => $selection->confidence,
            'selection_time_ms' => $selectionTime,
        ]);
        
        return $selection;
    }

    private function applySelectionRules(AgentSelectionRequest $request): AgentSelection
    {
        $candidates = [];
        
        // 1. User Override (Priority 100)
        if ($request->userOverride) {
            $candidates[] = [
                'priority' => 100,
                'agent' => $request->userOverride,
                'reason' => 'user_override',
                'confidence' => 1.0,
            ];
        }
        
        // 2. Conversation Context (Priority 90)
        if ($request->conversationId) {
            $conversationAgent = $this->getConversationAgent($request->conversationId);
            if ($conversationAgent) {
                $candidates[] = [
                    'priority' => 90,
                    'agent' => $conversationAgent,
                    'reason' => 'conversation_continuity',
                    'confidence' => 0.9,
                ];
            }
        }
        
        // 3. Task Requirements (Priority 80)
        $taskAnalysis = $this->taskDetector->analyzeTask($request->message, $request->context);
        if ($taskAnalysis->hasRecommendation()) {
            $candidates[] = [
                'priority' => 80,
                'agent' => $taskAnalysis->recommendedAgent,
                'reason' => "task_requirement: {$taskAnalysis->detectedTask}",
                'confidence' => $taskAnalysis->confidence,
            ];
        }
        
        // 4. Project Preferences (Priority 70)
        if ($request->projectId) {
            $projectAgent = $this->projectSettings->getPreferredAgent($request->projectId);
            if ($projectAgent) {
                $candidates[] = [
                    'priority' => 70,
                    'agent' => $projectAgent,
                    'reason' => 'project_preference',
                    'confidence' => 0.8,
                ];
            }
        }
        
        // 5. User Preferences (Priority 50)
        if ($request->userId) {
            $userAgent = $this->userPreferences->getPreferredAgent($request->userId);
            if ($userAgent) {
                $candidates[] = [
                    'priority' => 50,
                    'agent' => $userAgent,
                    'reason' => 'user_preference',
                    'confidence' => 0.7,
                ];
            }
        }
        
        // 6. Content Analysis (Priority 40)
        $contentAnalysis = $this->contextAnalyzer->analyzeContent($request->message);
        if ($contentAnalysis->hasRecommendation()) {
            $candidates[] = [
                'priority' => 40,
                'agent' => $contentAnalysis->recommendedAgent,
                'reason' => "content_analysis: {$contentAnalysis->reasonCode}",
                'confidence' => $contentAnalysis->confidence,
            ];
        }
        
        // 7. System Default (Priority 30)
        $candidates[] = [
            'priority' => 30,
            'agent' => config('fragments.agent_broker.agent_profiles.general'),
            'reason' => 'system_default',
            'confidence' => 0.5,
        ];
        
        // Select highest priority candidate
        usort($candidates, fn($a, $b) => $b['priority'] <=> $a['priority']);
        $selected = $candidates[0];
        
        return $this->buildAgentSelection($selected, $request);
    }

    private function buildAgentSelection(array $candidate, AgentSelectionRequest $request): AgentSelection
    {
        $agentConfig = $candidate['agent'];
        
        return new AgentSelection(
            agentProfile: $agentConfig['name'] ?? 'general',
            provider: $agentConfig['provider'],
            model: $agentConfig['model'],
            capabilities: $agentConfig['capabilities'] ?? [],
            selectionReason: $candidate['reason'],
            confidence: $candidate['confidence'],
            alternatives: $this->getAlternativeAgents($agentConfig),
            context: [
                'conversation_id' => $request->conversationId,
                'project_id' => $request->projectId,
                'task_detected' => $this->getDetectedTask($request),
            ]
        );
    }
}
```

### **Phase 2: Task Detection Service**
```php
class TaskDetector
{
    private array $taskPatterns;
    
    public function __construct()
    {
        $this->taskPatterns = config('fragments.agent_broker.task_detection');
    }

    public function analyzeTask(string $message, array $context): TaskAnalysis
    {
        $detectedTasks = [];
        
        // Check for coding patterns
        if ($this->matchesPatterns($message, $this->taskPatterns['coding_patterns'])) {
            $detectedTasks['coding'] = [
                'confidence' => $this->calculateCodingConfidence($message),
                'recommended_agent' => config('fragments.agent_broker.agent_profiles.coding'),
            ];
        }
        
        // Check for analysis patterns
        if ($this->matchesPatterns($message, $this->taskPatterns['analysis_patterns'])) {
            $detectedTasks['analysis'] = [
                'confidence' => $this->calculateAnalysisConfidence($message),
                'recommended_agent' => config('fragments.agent_broker.agent_profiles.general'),
            ];
        }
        
        // Check for quick response patterns
        if ($this->matchesPatterns($message, $this->taskPatterns['quick_patterns'])) {
            $detectedTasks['quick'] = [
                'confidence' => 0.9,
                'recommended_agent' => config('fragments.agent_broker.agent_profiles.quick'),
            ];
        }
        
        // Return highest confidence task
        if (!empty($detectedTasks)) {
            $bestTask = collect($detectedTasks)->sortByDesc('confidence')->first();
            $taskName = collect($detectedTasks)->search($bestTask);
            
            return new TaskAnalysis(
                detectedTask: $taskName,
                confidence: $bestTask['confidence'],
                recommendedAgent: $bestTask['recommended_agent'],
                allTasks: $detectedTasks
            );
        }
        
        return new TaskAnalysis();
    }
    
    private function calculateCodingConfidence(string $message): float
    {
        $confidence = 0.0;
        
        // Code blocks boost confidence significantly
        if (preg_match('/```[a-z]*.*?```/s', $message)) {
            $confidence += 0.7;
        }
        
        // Programming keywords
        $codingKeywords = ['function', 'class', 'def', 'var', 'const', 'git', 'api', 'debug'];
        $keywordMatches = 0;
        foreach ($codingKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $keywordMatches++;
            }
        }
        $confidence += min($keywordMatches * 0.1, 0.4);
        
        // Technical context
        if (preg_match('/\b(error|bug|fix|implement|refactor)\b/i', $message)) {
            $confidence += 0.2;
        }
        
        return min($confidence, 1.0);
    }
}
```

### **Phase 3: UI Integration**
```typescript
// React component for agent selection
interface AgentSelectorProps {
  currentAgent: AgentSelection;
  availableAgents: AgentProfile[];
  onAgentChange: (agent: AgentProfile) => void;
  showSelectionReason?: boolean;
}

function AgentSelector({ 
  currentAgent, 
  availableAgents, 
  onAgentChange, 
  showSelectionReason = true 
}: AgentSelectorProps) {
  const [isOpen, setIsOpen] = useState(false);
  
  return (
    <div className="agent-selector">
      <button 
        onClick={() => setIsOpen(!isOpen)}
        className="agent-selector-trigger"
        title={`Current: ${currentAgent.agentProfile} (${currentAgent.provider}/${currentAgent.model})`}
      >
        <AgentIcon agent={currentAgent.agentProfile} />
        <span className="agent-name">{currentAgent.agentProfile}</span>
        {showSelectionReason && (
          <span className="selection-reason">
            {formatSelectionReason(currentAgent.selectionReason)}
          </span>
        )}
        <ChevronDownIcon />
      </button>
      
      {isOpen && (
        <AgentMenu
          agents={availableAgents}
          currentAgent={currentAgent}
          onSelect={(agent) => {
            onAgentChange(agent);
            setIsOpen(false);
          }}
        />
      )}
    </div>
  );
}

// Enhanced chat interface with agent selection
function ChatInterface() {
  const [selectedAgent, setSelectedAgent] = useState<AgentSelection | null>(null);
  const [availableAgents, setAvailableAgents] = useState<AgentProfile[]>([]);
  
  useEffect(() => {
    // Fetch available agents based on current context
    fetchAvailableAgents().then(setAvailableAgents);
  }, []);
  
  const handleSendMessage = async (message: string) => {
    const response = await fetch('/api/messages', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        content: message,
        agent_override: selectedAgent ? {
          provider: selectedAgent.provider,
          model: selectedAgent.model,
        } : null,
      }),
    });
    
    const result = await response.json();
    
    // Update selected agent if broker made a different choice
    if (result.agent_selection) {
      setSelectedAgent(result.agent_selection);
    }
  };
  
  return (
    <div className="chat-interface">
      <div className="chat-header">
        <AgentSelector
          currentAgent={selectedAgent}
          availableAgents={availableAgents}
          onAgentChange={setSelectedAgent}
        />
      </div>
      
      <ChatMessages />
      
      <ChatComposer onSendMessage={handleSendMessage} />
    </div>
  );
}
```

### **Phase 4: Analytics and Learning**
```php
class AgentBrokerAnalytics
{
    public function recordAgentSelection(
        AgentSelection $selection, 
        AgentSelectionRequest $request,
        ?AgentResult $result = null
    ): void {
        DB::table('agent_selections')->insert([
            'conversation_id' => $request->conversationId,
            'user_id' => $request->userId,
            'project_id' => $request->projectId,
            'selected_agent' => $selection->agentProfile,
            'provider' => $selection->provider,
            'model' => $selection->model,
            'selection_reason' => $selection->selectionReason,
            'confidence' => $selection->confidence,
            'was_override' => !is_null($request->userOverride),
            'task_detected' => $selection->context['task_detected'] ?? null,
            'response_quality' => $result?->qualityScore,
            'response_time_ms' => $result?->responseTime,
            'created_at' => now(),
        ]);
    }
    
    public function getAgentPerformanceStats(): array
    {
        return DB::table('agent_selections')
            ->select([
                'selected_agent',
                'provider',
                'model',
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('AVG(response_quality) as avg_quality'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('SUM(CASE WHEN was_override THEN 1 ELSE 0 END) as override_count'),
            ])
            ->where('created_at', '>=', now()->subWeek())
            ->groupBy(['selected_agent', 'provider', 'model'])
            ->orderBy('usage_count', 'desc')
            ->get()
            ->toArray();
    }
    
    public function getUserAgentPreferences(int $userId): array
    {
        // Analyze user's override patterns to infer preferences
        return DB::table('agent_selections')
            ->select([
                'selected_agent',
                'provider',
                'model',
                DB::raw('COUNT(*) as times_chosen'),
                DB::raw('AVG(CASE WHEN was_override THEN 1 ELSE 0 END) as override_rate'),
            ])
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMonth())
            ->groupBy(['selected_agent', 'provider', 'model'])
            ->having('times_chosen', '>=', 3)
            ->orderBy('override_rate', 'desc')
            ->get()
            ->toArray();
    }
}
```

## Success Criteria
- [ ] Context-aware agent selection improves response quality
- [ ] UI allows easy agent switching with clear selection reasons
- [ ] Task detection accurately identifies coding, analysis, and quick tasks
- [ ] Project and user preferences are respected and learned over time
- [ ] Agent selection completes within 50ms
- [ ] Analytics provide insights into agent performance and user preferences
- [ ] Graceful fallbacks when preferred agents are unavailable
- [ ] User overrides are remembered and influence future selections

## Files to Create/Modify
### New Files
- `app/Services/AgentBroker.php`
- `app/Services/TaskDetector.php`
- `app/Services/ContextAnalyzer.php`
- `app/Services/UserPreferenceService.php`
- `app/Services/ProjectSettingsService.php`
- `app/Services/AgentBrokerAnalytics.php`
- `app/DTOs/AgentSelectionRequest.php`
- `app/DTOs/AgentSelection.php`
- `app/DTOs/TaskAnalysis.php`
- `resources/js/components/AgentSelector.tsx`
- `database/migrations/create_agent_selections_table.php`

### Modified Files
- `app/Http/Controllers/ChatApiController.php`
- `app/Services/ContextBroker.php`
- `config/fragments.php`
- `resources/js/islands/chat/ChatInterface.tsx`

## Testing Strategy
- Unit tests for task detection with various message types
- Integration tests for agent selection logic
- UI tests for agent selector component
- Performance tests to validate selection speed
- Analytics tests to verify data collection
- User experience tests to validate selection quality