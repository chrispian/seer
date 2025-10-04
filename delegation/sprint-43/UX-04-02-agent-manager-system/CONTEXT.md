# Agent Manager System Context

## Current AI Provider Architecture

### Existing Components
```php
// AI Provider Services (app/Services/AI/)
- OpenAIService, AnthropicService, OllamaService, OpenRouterService
- Unified AIProviderInterface for consistent interaction
- Model selection and switching functionality
- Stream handling and response processing

// Current Chat Integration
- ChatIsland.tsx with model selection dropdown
- useModelSelection hook for provider/model state
- Session-based model persistence
- Real-time model switching capability
```

### Agent Manager Requirements from Backlog
```markdown
# From delegation/backlog/agent-manager/agent_profile_manager_context_pack.md

## Goals (MVP)
- Create/manage agent profiles (name, description, personality, tone, style, role, mode, allowed tools, default model)
- Support system agents (non-deletable, required for engine ops) and user agents
- Support cloning with lineage tracking
- Allow per-scope defaults (global, workspace, project, command)
- Support modes (Agent, Plan, Chat, Assistant) that define capability boundaries
- Add per-message mode overrides at runtime
- Use hybrid primary agent model: each chat session has dedicated "primary chat agent"
```

## Target Database Schema

### agent_profiles Table
```sql
CREATE TABLE agent_profiles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    role VARCHAR(255),
    mode VARCHAR(50) NOT NULL CHECK (mode IN ('Agent', 'Plan', 'Chat', 'Assistant')),
    default_model VARCHAR(255),
    allowed_tools JSONB DEFAULT '[]',
    prompt_customizations JSONB DEFAULT '{}',
    personality JSONB DEFAULT '{}',
    tone VARCHAR(255),
    style JSONB DEFAULT '{}',
    is_system BOOLEAN DEFAULT FALSE,
    is_default_chat_agent BOOLEAN DEFAULT FALSE,
    parent_id UUID REFERENCES agent_profiles(id),
    version VARCHAR(50) DEFAULT '1.0.0',
    scope_overrides JSONB DEFAULT '{}',
    meta JSONB DEFAULT '{}',
    avatar_path VARCHAR(500), -- Path to uploaded avatar image
    avatar_type VARCHAR(50) DEFAULT 'initials' CHECK (avatar_type IN ('initials', 'upload', 'generated', 'emoji')),
    avatar_config JSONB DEFAULT '{}', -- Avatar configuration (colors, style, etc.)
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_agent_profiles_mode (mode),
    INDEX idx_agent_profiles_is_system (is_system),
    INDEX idx_agent_profiles_default_chat (is_default_chat_agent),
    INDEX idx_agent_profiles_parent (parent_id),
    INDEX idx_agent_profiles_avatar_type (avatar_type)
);

CREATE TABLE agent_profile_histories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    agent_profile_id UUID NOT NULL REFERENCES agent_profiles(id) ON DELETE CASCADE,
    version VARCHAR(50) NOT NULL,
    snapshot JSONB NOT NULL,
    change_summary TEXT,
    changed_by UUID, -- user id, nullable for system changes
    created_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_agent_history_profile (agent_profile_id),
    INDEX idx_agent_history_version (agent_profile_id, version)
);
```

### Logging Enhancement
```sql
-- Extend existing logging to capture agent context
ALTER TABLE logs ADD COLUMN session_id UUID;
ALTER TABLE logs ADD COLUMN primary_agent_id UUID;
ALTER TABLE logs ADD COLUMN active_agent_id UUID;
ALTER TABLE logs ADD COLUMN agent_mode VARCHAR(50);
ALTER TABLE logs ADD COLUMN tool_calls JSONB;
ALTER TABLE logs ADD COLUMN context_chain JSONB;
```

## Mode System Architecture

### Mode Definitions
```typescript
interface AgentMode {
  name: 'Agent' | 'Plan' | 'Chat' | 'Assistant'
  capabilities: {
    canExecuteCommands: boolean
    canAccessTools: boolean
    canModifySystem: boolean
    allowedToolCategories: string[]
    executionConstraints: Record<string, any>
  }
  description: string
}

const AGENT_MODES: Record<string, AgentMode> = {
  Agent: {
    name: 'Agent',
    capabilities: {
      canExecuteCommands: true,
      canAccessTools: true,
      canModifySystem: true,
      allowedToolCategories: ['all'],
      executionConstraints: { autoAccept: false }
    },
    description: 'Full execution with commands, tools, and system access'
  },
  Plan: {
    name: 'Plan',
    capabilities: {
      canExecuteCommands: false,
      canAccessTools: true,
      canModifySystem: false,
      allowedToolCategories: ['read-only'],
      executionConstraints: { sandboxed: true }
    },
    description: 'Sandboxed, read-only tools with no system mutation'
  },
  Chat: {
    name: 'Chat',
    capabilities: {
      canExecuteCommands: false,
      canAccessTools: false,
      canModifySystem: false,
      allowedToolCategories: [],
      executionConstraints: { conversationOnly: true }
    },
    description: 'Conversational only, no system execution'
  },
  Assistant: {
    name: 'Assistant',
    capabilities: {
      canExecuteCommands: true,
      canAccessTools: true,
      canModifySystem: false,
      allowedToolCategories: ['productivity', 'notes', 'calendar', 'email'],
      executionConstraints: { productivityFocus: true }
    },
    description: 'Cognitive assistant for productivity and second-brain functions'
  }
}
```

## Agent Resolution Hierarchy

### Scope Resolution Logic
```typescript
interface AgentScope {
  level: 'command' | 'project' | 'workspace' | 'global' | 'system'
  agentId?: string
  modeOverride?: string
  priority: number
}

class AgentResolver {
  async resolveAgent(context: {
    command?: string
    projectId?: string
    workspaceId?: string
    userId: string
    sessionId: string
  }): Promise<AgentProfile> {
    // 1. Command-specific agent
    if (context.command) {
      const commandAgent = await this.getCommandAgent(context.command)
      if (commandAgent) return commandAgent
    }
    
    // 2. Project-specific agent
    if (context.projectId) {
      const projectAgent = await this.getProjectAgent(context.projectId)
      if (projectAgent) return projectAgent
    }
    
    // 3. Workspace-specific agent
    if (context.workspaceId) {
      const workspaceAgent = await this.getWorkspaceAgent(context.workspaceId)
      if (workspaceAgent) return workspaceAgent
    }
    
    // 4. User's global default
    const globalAgent = await this.getGlobalAgent(context.userId)
    if (globalAgent) return globalAgent
    
    // 5. System fallback
    return await this.getSystemFallbackAgent()
  }
}
```

## UI Component Architecture

### Component Structure
```
AgentManager/
├── AgentProfileManager.tsx      # Main CRUD interface
├── AgentSelector.tsx            # Agent selection dropdown
├── AgentModeSelector.tsx        # Mode selection with badge
├── AgentCloneDialog.tsx         # Cloning interface
├── AgentHistoryView.tsx         # Version history display
├── AgentPermissions.tsx         # Tool permissions editor
├── AgentPersonality.tsx         # Personality settings editor
└── hooks/
    ├── useAgentProfiles.tsx     # CRUD operations
    ├── useAgentResolver.tsx     # Resolution logic
    └── useAgentHistory.tsx      # Version management
```

### Agent Selector Integration
```typescript
// Integration with existing chat interface
interface AgentSelectorProps {
  sessionId: string
  currentAgent?: AgentProfile
  onAgentChange: (agent: AgentProfile, modeOverride?: string) => void
  allowModeOverride?: boolean
  compact?: boolean
}

// Usage in ChatComposer
<div className="flex items-center gap-2">
  <AgentSelector 
    sessionId={currentSessionId}
    currentAgent={resolvedAgent}
    onAgentChange={handleAgentChange}
    allowModeOverride={true}
    compact={true}
  />
  <ModelSelector ... />
</div>
```

## Prompt Assembly System

### Prompt Structure
```typescript
interface AssembledPrompt {
  systemPreamble: string      // Agent + global guardrails
  developerDirectives: string // Framework-specific rules
  roleConstraints: string     // Mode-specific limitations
  toneInstructions: string    // Personality and style
  fewShotExamples?: string    // Optional examples
  userContext: string         // Current task/context
}

class PromptAssembler {
  assemblePrompt(
    agent: AgentProfile, 
    mode: AgentMode, 
    context: ChatContext
  ): AssembledPrompt {
    return {
      systemPreamble: this.buildSystemPreamble(agent),
      developerDirectives: this.getDeveloperDirectives(),
      roleConstraints: this.buildModeConstraints(mode),
      toneInstructions: this.buildToneInstructions(agent),
      fewShotExamples: this.getFewShotExamples(agent),
      userContext: this.buildUserContext(context)
    }
  }
}
```

## Tool Registry Integration

### Tool Validation
```typescript
interface ToolCapability {
  category: string
  permissions: string[]
  riskLevel: 'low' | 'medium' | 'high'
  requiresApproval: boolean
}

class ToolValidator {
  validateToolAccess(
    agent: AgentProfile, 
    mode: AgentMode, 
    toolId: string
  ): boolean {
    const tool = this.toolRegistry.getTool(toolId)
    
    // Check mode permissions
    if (!mode.capabilities.allowedToolCategories.includes(tool.category)) {
      return false
    }
    
    // Check agent-specific permissions
    if (!agent.allowed_tools.includes(toolId)) {
      return false
    }
    
    // Check risk level constraints
    if (tool.riskLevel === 'high' && !agent.is_system) {
      return false
    }
    
    return true
  }
}
```

## API Endpoints

### REST API Structure
```typescript
// Agent Profile CRUD
GET    /api/agents                    # List user's agents
POST   /api/agents                    # Create new agent
GET    /api/agents/{id}               # Get agent details
PUT    /api/agents/{id}               # Update agent
DELETE /api/agents/{id}               # Delete agent (user only)
POST   /api/agents/{id}/clone         # Clone agent with lineage

// Agent History and Versioning
GET    /api/agents/{id}/history       # Get version history
POST   /api/agents/{id}/restore/{ver} # Restore to version

// Agent Resolution and Defaults
GET    /api/agents/resolve            # Resolve agent for context
POST   /api/agents/defaults           # Set scope defaults
GET    /api/agents/system             # List system agents

// Agent Modes and Tools
GET    /api/agents/modes              # Get available modes
GET    /api/agents/tools              # Get available tools
POST   /api/agents/{id}/validate      # Validate agent config
```

## Event System Integration

### Events
```php
// Agent Profile Events
class AgentProfileCreated extends Event {
    public AgentProfile $profile;
    public ?User $creator;
}

class AgentProfileUpdated extends Event {
    public AgentProfile $profile;
    public array $changes;
    public ?User $updater;
}

class AgentProfileCloned extends Event {
    public AgentProfile $originalProfile;
    public AgentProfile $clonedProfile;
    public ?User $cloner;
}

class AgentResolved extends Event {
    public AgentProfile $resolvedAgent;
    public array $resolutionContext;
    public string $sessionId;
}
```

### Telemetry Integration
```php
// Enhanced logging for agent interactions
class AgentTelemetryObserver {
    public function handle(AgentResolved $event): void {
        Log::info('Agent resolved for session', [
            'session_id' => $event->sessionId,
            'agent_id' => $event->resolvedAgent->id,
            'agent_name' => $event->resolvedAgent->name,
            'mode' => $event->resolvedAgent->mode,
            'resolution_context' => $event->resolutionContext,
            'timestamp' => now()
        ]);
    }
}
```

## Integration Points

### Chat System Integration
```typescript
// Enhanced ChatIsland with agent management
const ChatIsland = () => {
  const [resolvedAgent, setResolvedAgent] = useState<AgentProfile>()
  const [modeOverride, setModeOverride] = useState<string>()
  
  // Resolve agent for current session
  useEffect(() => {
    const resolveAgent = async () => {
      const agent = await agentResolver.resolveAgent({
        sessionId: currentSessionId,
        userId: currentUser.id,
        // ... other context
      })
      setResolvedAgent(agent)
    }
    
    resolveAgent()
  }, [currentSessionId])
  
  // Update chat interface with agent context
  return (
    <div className="chat-container">
      <AgentSelector 
        currentAgent={resolvedAgent}
        onAgentChange={handleAgentChange}
        allowModeOverride={true}
      />
      <ChatComposer 
        agent={resolvedAgent}
        modeOverride={modeOverride}
        // ... other props
      />
    </div>
  )
}
```

### Command System Integration
```php
// Enhanced command execution with agent context
class CommandExecutor {
    public function execute(CommandRequest $request): CommandResponse {
        // Resolve agent for command context
        $agent = $this->agentResolver->resolveAgent([
            'command' => $request->command,
            'user_id' => $request->userId,
            'session_id' => $request->sessionId
        ]);
        
        // Validate agent permissions for command
        if (!$this->validateAgentPermissions($agent, $request->command)) {
            throw new UnauthorizedCommandException();
        }
        
        // Execute with agent context
        return $this->executeWithAgent($request, $agent);
    }
}
```

## Performance Considerations

### Caching Strategy
```php
// Agent resolution caching
class AgentResolver {
    private Cache $cache;
    
    public function resolveAgent(array $context): AgentProfile {
        $cacheKey = $this->buildCacheKey($context);
        
        return $this->cache->remember($cacheKey, 300, function () use ($context) {
            return $this->performResolution($context);
        });
    }
    
    private function buildCacheKey(array $context): string {
        return sprintf(
            'agent_resolution:%s:%s:%s:%s',
            $context['user_id'],
            $context['session_id'] ?? 'null',
            $context['command'] ?? 'null',
            $context['project_id'] ?? 'null'
        );
    }
}
```

### UI Performance
```typescript
// Optimized agent selector with memoization
const AgentSelector = React.memo(({ 
  currentAgent, 
  onAgentChange, 
  allowModeOverride 
}: AgentSelectorProps) => {
  const { data: agents, isLoading } = useQuery({
    queryKey: ['user-agents'],
    queryFn: fetchUserAgents,
    staleTime: 5 * 60 * 1000, // 5 minutes
  })
  
  // ... component implementation
})
```

## Agent Avatar System

### Avatar Types and Fallback Strategy
```typescript
interface AgentAvatar {
  type: 'initials' | 'upload' | 'generated' | 'emoji'
  path?: string
  config: {
    // For initials type
    initials?: string
    backgroundColor?: string
    textColor?: string
    
    // For generated type (future AI generation)
    style?: 'realistic' | 'cartoon' | 'abstract' | 'pixel'
    seed?: string
    
    // For emoji type
    emoji?: string
    
    // For reaction variants (future enhancement)
    reactions?: {
      happy?: string
      thinking?: string
      error?: string
      success?: string
      working?: string
    }
  }
}

// Avatar resolution strategy
const resolveAvatarUrl = (agent: AgentProfile): string => {
  switch (agent.avatar_type) {
    case 'upload':
      return agent.avatar_path || generateInitialsAvatar(agent)
    case 'generated':
      return agent.avatar_path || generateInitialsAvatar(agent)
    case 'emoji':
      return generateEmojiAvatar(agent.avatar_config.emoji || '🤖')
    case 'initials':
    default:
      return generateInitialsAvatar(agent)
  }
}
```

### Avatar Generation Utilities
```typescript
// Initials avatar generation (default fallback)
const generateInitialsAvatar = (agent: AgentProfile): string => {
  const initials = agent.avatar_config?.initials || 
    agent.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  
  const backgroundColor = agent.avatar_config?.backgroundColor || 
    generateColorFromName(agent.name)
  
  const textColor = agent.avatar_config?.textColor || 
    getContrastColor(backgroundColor)
  
  // Generate SVG data URL or use canvas
  return generateSVGAvatar({
    initials,
    backgroundColor,
    textColor,
    size: 40
  })
}

// Color generation from name for consistency
const generateColorFromName = (name: string): string => {
  const colors = [
    '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
    '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'
  ]
  
  const hash = name.split('').reduce((acc, char) => {
    return char.charCodeAt(0) + ((acc << 5) - acc)
  }, 0)
  
  return colors[Math.abs(hash) % colors.length]
}
```

### Avatar Upload System
```typescript
// Avatar upload component
interface AvatarUploadProps {
  currentAvatar?: AgentAvatar
  onAvatarChange: (avatar: AgentAvatar) => void
  maxSize?: number // MB
  allowedTypes?: string[]
}

const AvatarUpload: React.FC<AvatarUploadProps> = ({
  currentAvatar,
  onAvatarChange,
  maxSize = 2,
  allowedTypes = ['image/jpeg', 'image/png', 'image/webp']
}) => {
  const handleFileUpload = async (file: File) => {
    // Validate file
    if (!allowedTypes.includes(file.type)) {
      throw new Error('Invalid file type')
    }
    
    if (file.size > maxSize * 1024 * 1024) {
      throw new Error(`File too large. Maximum size: ${maxSize}MB`)
    }
    
    // Process and upload
    const processedFile = await processAvatarImage(file)
    const uploadPath = await uploadAvatarFile(processedFile)
    
    onAvatarChange({
      type: 'upload',
      path: uploadPath,
      config: {}
    })
  }
  
  return (
    <div className="avatar-upload-area">
      <AvatarPreview avatar={currentAvatar} size="lg" />
      <input
        type="file"
        accept={allowedTypes.join(',')}
        onChange={(e) => e.files?.[0] && handleFileUpload(e.files[0])}
      />
    </div>
  )
}
```

### Avatar Display Components
```typescript
// Reusable avatar component
interface AvatarProps {
  agent: AgentProfile
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  reaction?: 'default' | 'happy' | 'thinking' | 'error' | 'success' | 'working'
  className?: string
  showOnlineStatus?: boolean
}

const AgentAvatar: React.FC<AvatarProps> = ({
  agent,
  size = 'md',
  reaction = 'default',
  className,
  showOnlineStatus = false
}) => {
  const sizeMap = {
    xs: 'w-6 h-6',
    sm: 'w-8 h-8',
    md: 'w-10 h-10',
    lg: 'w-16 h-16',
    xl: 'w-24 h-24'
  }
  
  const avatarUrl = resolveAvatarUrl(agent, reaction)
  
  return (
    <div className={cn('relative', sizeMap[size], className)}>
      <img
        src={avatarUrl}
        alt={`${agent.name} avatar`}
        className="w-full h-full rounded-full object-cover"
        onError={(e) => {
          // Fallback to initials if image fails to load
          e.currentTarget.src = generateInitialsAvatar(agent)
        }}
      />
      
      {showOnlineStatus && (
        <div className="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full" />
      )}
      
      {/* Mode badge for context */}
      <div className="absolute -top-1 -right-1">
        <ModeBadge mode={agent.mode} size="xs" />
      </div>
    </div>
  )
}
```

### Backend Avatar Services
```php
// Avatar management service
class AgentAvatarService 
{
    public function uploadAvatar(UploadedFile $file, AgentProfile $agent): string 
    {
        $this->validateAvatarFile($file);
        
        // Process image (resize, optimize)
        $processedImage = $this->processAvatarImage($file);
        
        // Generate unique filename
        $filename = $this->generateAvatarFilename($agent, $file->getClientOriginalExtension());
        
        // Store in agent avatars directory
        $path = Storage::disk('public')->put("avatars/agents/{$agent->id}", $processedImage, $filename);
        
        // Delete old avatar if exists
        if ($agent->avatar_path && $agent->avatar_type === 'upload') {
            Storage::disk('public')->delete($agent->avatar_path);
        }
        
        // Update agent profile
        $agent->update([
            'avatar_type' => 'upload',
            'avatar_path' => $path,
            'avatar_config' => []
        ]);
        
        return Storage::disk('public')->url($path);
    }
    
    public function generateInitialsAvatar(AgentProfile $agent): string 
    {
        $initials = $this->extractInitials($agent->name);
        $backgroundColor = $this->generateColorFromName($agent->name);
        
        // Generate SVG
        $svg = $this->createInitialsSVG($initials, $backgroundColor);
        
        // Save as file or return data URL
        $filename = "initials-{$agent->id}.svg";
        $path = "avatars/generated/{$filename}";
        
        Storage::disk('public')->put($path, $svg);
        
        $agent->update([
            'avatar_type' => 'initials',
            'avatar_path' => $path,
            'avatar_config' => [
                'initials' => $initials,
                'backgroundColor' => $backgroundColor
            ]
        ]);
        
        return Storage::disk('public')->url($path);
    }
    
    private function processAvatarImage(UploadedFile $file): string 
    {
        // Resize to 200x200, optimize for web
        $image = Image::make($file);
        
        return $image
            ->fit(200, 200)
            ->encode('webp', 85)
            ->getEncoded();
    }
}
```

### Future Enhancement: AI-Generated Avatars
```typescript
// Future AI avatar generation service
interface AIAvatarRequest {
  agentName: string
  agentRole: string
  personality: Record<string, any>
  style: 'realistic' | 'cartoon' | 'abstract' | 'pixel'
  seed?: string
}

const generateAIAvatar = async (request: AIAvatarRequest): Promise<string> => {
  // Integration with AI image generation service
  const prompt = buildAvatarPrompt(request)
  
  const response = await fetch('/api/ai/generate-avatar', {
    method: 'POST',
    body: JSON.stringify({
      prompt,
      style: request.style,
      seed: request.seed
    })
  })
  
  const { imageUrl } = await response.json()
  return imageUrl
}

const buildAvatarPrompt = (request: AIAvatarRequest): string => {
  return `Professional avatar for AI assistant named ${request.agentName}, 
    role: ${request.agentRole}, 
    personality: ${JSON.stringify(request.personality)}, 
    style: ${request.style}, 
    clean background, centered composition`
}
```

### Avatar Reaction System (Future Enhancement)
```typescript
// Reaction-based avatar variants for visual feedback
interface AvatarReaction {
  type: 'happy' | 'thinking' | 'error' | 'success' | 'working'
  duration?: number
  animation?: 'pulse' | 'bounce' | 'spin' | 'glow'
}

const useAvatarReactions = (agent: AgentProfile) => {
  const [currentReaction, setCurrentReaction] = useState<AvatarReaction | null>(null)
  
  const showReaction = (reaction: AvatarReaction) => {
    setCurrentReaction(reaction)
    
    if (reaction.duration) {
      setTimeout(() => {
        setCurrentReaction(null)
      }, reaction.duration)
    }
  }
  
  const getAvatarWithReaction = (): string => {
    if (!currentReaction) {
      return resolveAvatarUrl(agent)
    }
    
    // Return reaction-specific avatar variant
    const reactionPath = agent.avatar_config?.reactions?.[currentReaction.type]
    return reactionPath || resolveAvatarUrl(agent)
  }
  
  return {
    currentReaction,
    showReaction,
    getAvatarWithReaction
  }
}

// Usage in chat for dynamic feedback
const ChatMessage = ({ message, agent }) => {
  const { showReaction, getAvatarWithReaction } = useAvatarReactions(agent)
  
  useEffect(() => {
    if (message.isStreaming) {
      showReaction({ type: 'working', duration: 2000, animation: 'pulse' })
    } else if (message.isComplete) {
      showReaction({ type: 'success', duration: 1000, animation: 'glow' })
    }
  }, [message.isStreaming, message.isComplete])
  
  return (
    <div className="flex gap-3">
      <AgentAvatar 
        agent={agent} 
        avatarUrl={getAvatarWithReaction()}
        className={currentReaction?.animation && `animate-${currentReaction.animation}`}
      />
      <div>{message.content}</div>
    </div>
  )
}
```

## Security Considerations

### Permission Validation
- System agents protected from modification by non-admin users
- Tool access validated against agent permissions and mode constraints
- Agent mode boundaries enforced at execution time
- Audit trail for all agent profile changes
- Scope permission validation for agent assignment

### Data Protection
- Agent profile data encrypted at rest
- Sensitive configuration data (API keys) stored separately
- User agent isolation (users can only see/modify their own agents)
- System agent read-only access for non-admin users
- Proper input validation for all agent configuration fields

### Avatar Security
- File upload validation (type, size, content scanning)
- Image processing to strip metadata and prevent malicious content
- Secure file storage with proper access controls
- Rate limiting for avatar uploads and AI generation
- Content moderation for uploaded and generated avatars
- Automatic fallback to safe initials avatars for any security violations