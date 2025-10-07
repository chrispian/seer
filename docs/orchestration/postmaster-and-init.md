# Postmaster & Agent INIT System

**Sprint:** SPRINT-51  
**Created:** 2025-10-07  
**Status:** Complete

## Overview

The Postmaster and Agent INIT system provides a foundation for orchestrating AI agents with durable messaging, content-addressable storage, and standardized initialization workflows.

### Core Components

1. **Postmaster** - Message router with artifact handling and secret redaction
2. **Artifacts Store** - Content-addressable storage (CAS) with SHA256 deduplication
3. **Messaging System** - Inbox-based agent communication with read markers
4. **Agent INIT Protocol** - 4-step agent initialization (resume, profile, health, plan)
5. **Memory Service** - Durable and ephemeral key-value storage with compaction

---

## Architecture

### Data Flow

```
PM sends parcel (with attachments)
  ↓
ProcessParcel Job (queue: postmaster)
  ↓ 
ContentStore.put() → SHA256 hash → storage/orchestration/cas/{hash}
  ↓
SecretRedactor.redact() → [REDACTED:AWS_ACCESS_KEY]
  ↓
OrchestrationArtifact records created
  ↓
Envelope rewritten with fe:// URIs
  ↓
Message created (type: postmaster.delivery)
  ↓
Agent polls inbox → MCP: MessagesCheckTool
  ↓
Agent pulls artifacts → MCP: ArtifactsPullTool
  ↓
Agent runs INIT → AgentInitService (4 steps)
  ↓
Agent marks message read → MessagesAckTool
```

### Storage Layout

```
storage/
├── orchestration/
│   ├── cas/
│   │   ├── ab/
│   │   │   └── cd123...456.blob  # SHA256-addressed content
│   │   └── ef/
│   │       └── 789ab...cde.blob
│   └── memory/
│       └── agent-{id}/
│           ├── durable/
│           │   └── state.json     # Survives restarts
│           └── ephemeral/
│               └── context.json   # Cleared on compaction
```

---

## Components Reference

### 1. ContentStore Service

**Path:** `app/Services/Orchestration/Artifacts/ContentStore.php`

Content-addressable storage with SHA256 deduplication.

#### Methods

```php
// Store content, returns SHA256 hash
public function put(string $content, array $metadata = []): string

// Retrieve content by hash
public function get(string $hash): ?string

// Check if content exists
public function exists(string $hash): bool

// Get file size
public function getSize(string $hash): ?int

// Format fe:// URI
public function formatUri(string $hash, string $taskId, string $filename): string

// Parse fe:// URI
public function parseUri(string $feUri): ?array
```

#### Configuration

```php
// config/orchestration.php
'artifacts' => [
    'storage_path' => storage_path('orchestration/cas'),
    'max_size_bytes' => 100 * 1024 * 1024, // 100MB
],
```

#### URI Format

```
fe://artifacts/by-task/{taskId}/{hash}/{filename}
```

**Example:**
```
fe://artifacts/by-task/0199bc42-55bd-7893-9dbd-84f3a64ad1bc/ab12cd34.../report.pdf
```

---

### 2. ProcessParcel Job

**Path:** `app/Jobs/Postmaster/ProcessParcel.php`

Processes incoming parcels from the Project Manager (PM).

#### Dispatch

```php
use App\Jobs\Postmaster\ProcessParcel;

ProcessParcel::dispatch($parcel, $taskId);
```

#### Parcel Structure

```php
[
    'to_agent_id' => 'agent-uuid',
    'task_id' => 'task-uuid',
    'stream' => 'command.delegation',  // Message stream
    'type' => 'pm.task.created',       // Original type
    'headers' => [
        'from' => 'orchestration-pm',
        'priority' => 'normal',
    ],
    'envelope' => [
        'body' => 'Task description',
        'metadata' => [...],
    ],
    'attachments' => [
        'instructions' => [
            'content' => '...',           // Stored in CAS
            'filename' => 'readme.md',
            'mime_type' => 'text/markdown',
        ],
        'dataset' => [
            'content' => '...',
            'filename' => 'data.csv',
        ],
    ],
]
```

#### Processing Steps

1. **Store Attachments** → ContentStore with redaction
2. **Create OrchestrationArtifact** records
3. **Rewrite Envelope** → Replace content with `fe://` URIs
4. **Generate Manifest** → JSON summary of all artifacts
5. **Create Message** → Store in agent inbox

#### Rewritten Envelope

```php
[
    'envelope' => [
        'body' => 'Task description',
        'metadata' => [...],
    ],
    'attachments' => [
        'instructions' => [
            'fe_uri' => 'fe://artifacts/by-task/...',
            'hash' => 'ab12cd34...',
            'filename' => 'readme.md',
            'size_bytes' => 4096,
            'mime_type' => 'text/markdown',
        ],
    ],
]
```

---

### 3. SecretRedactor Service

**Path:** `app/Services/Orchestration/Security/SecretRedactor.php`

Pattern-based secret detection and redaction.

#### Methods

```php
// Redact secrets from content
public function redact(string $content): string

// Check if content has secrets
public function hasSecrets(string $content): bool

// Scan and return findings
public function scan(string $content): array
```

#### Supported Patterns

| Pattern | Example | Redacted As |
|---------|---------|-------------|
| AWS Access Key | `AKIAIOSFODNN7EXAMPLE` | `[REDACTED:AWS_ACCESS_KEY]` |
| AWS Secret Key | `wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY` | `[REDACTED:AWS_SECRET_KEY]` |
| Laravel APP_KEY | `base64:abcd1234...` | `[REDACTED:APP_KEY]` |
| Bearer Token | `Bearer eyJ0eXAi...` | `[REDACTED:BEARER_TOKEN]` |
| OpenAI API Key | `sk-proj-abc123...` | `[REDACTED:OPENAI_KEY]` |

#### Configuration

```php
// config/orchestration.php
'secret_redaction' => [
    'enabled' => true,
    'patterns' => [...],
],
```

---

### 4. Messaging API

**Controller:** `app/Http/Controllers/Orchestration/MessagingController.php`

RESTful API for agent messaging.

#### Endpoints

##### List Agent Inbox

```http
GET /api/orchestration/agents/{agentId}/inbox?status=unread&page=1
```

**Response:**
```json
{
  "data": [
    {
      "id": "msg-uuid",
      "stream": "command.delegation",
      "type": "postmaster.delivery",
      "task_id": "task-uuid",
      "to_agent_id": "agent-uuid",
      "from_agent_id": null,
      "headers": {
        "original_type": "pm.task.created",
        "artifacts_count": 2,
        "manifest_uri": "fe://..."
      },
      "envelope": {
        "body": "...",
        "attachments": {...}
      },
      "read_at": null,
      "created_at": "2025-10-07T12:34:56Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 5,
    "unread_count": 3
  }
}
```

##### Send Message

```http
POST /api/orchestration/messages/send
Content-Type: application/json

{
  "to_agent_id": "agent-uuid",
  "task_id": "task-uuid",
  "stream": "agent.updates",
  "type": "agent.status",
  "headers": {"priority": "high"},
  "envelope": {"status": "in_progress"}
}
```

##### Mark as Read

```http
POST /api/orchestration/messages/{messageId}/read
```

##### Broadcast to Project

```http
POST /api/orchestration/messages/broadcast
Content-Type: application/json

{
  "project_id": "project-uuid",
  "stream": "project.announcements",
  "type": "system.update",
  "envelope": {"message": "Deployment complete"}
}
```

---

### 5. Artifacts API

**Controller:** `app/Http/Controllers/Orchestration/ArtifactsController.php`

#### Endpoints

##### Create Artifact

```http
POST /api/orchestration/tasks/{taskId}/artifacts
Content-Type: application/json

{
  "content": "File contents here...",
  "filename": "report.txt",
  "mime_type": "text/plain",
  "metadata": {"source": "agent-analysis"}
}
```

**Response:**
```json
{
  "success": true,
  "artifact": {
    "id": "artifact-uuid",
    "hash": "ab12cd34...",
    "filename": "report.txt",
    "mime_type": "text/plain",
    "size_bytes": 1024,
    "size_formatted": "1.00 KB",
    "fe_uri": "fe://artifacts/by-task/...",
    "created_at": "2025-10-07T12:34:56Z"
  }
}
```

##### List Task Artifacts

```http
GET /api/orchestration/tasks/{taskId}/artifacts
```

**Response:**
```json
{
  "data": [...],
  "meta": {
    "task_id": "task-uuid",
    "count": 5,
    "total_size_bytes": 10485760
  }
}
```

##### Download Artifact

```http
GET /api/orchestration/artifacts/{artifactId}/download
```

**Headers:**
```
Content-Type: text/plain
Content-Length: 1024
X-Artifact-Hash: ab12cd34...
X-FE-URI: fe://...
Cache-Control: public, max-age=31536000
```

---

### 6. MCP Tools

**Path:** `app/Tools/Orchestration/`

Claude MCP tools for agent operations.

#### MessagesCheckTool

Check agent inbox for new messages.

```typescript
// MCP Request
{
  "tool": "messages_check",
  "arguments": {
    "agent_id": "agent-uuid",
    "status": "unread"  // or "all"
  }
}
```

#### MessageAckTool

Mark message as read.

```typescript
{
  "tool": "message_ack",
  "arguments": {
    "message_id": "msg-uuid"
  }
}
```

#### ArtifactsPullTool

Download artifact by fe:// URI or artifact ID.

```typescript
{
  "tool": "artifacts_pull",
  "arguments": {
    "fe_uri": "fe://artifacts/by-task/..."
    // OR
    "artifact_id": "artifact-uuid"
  }
}
```

#### HandoffTool

Handoff task to another agent.

```typescript
{
  "tool": "handoff",
  "arguments": {
    "task_id": "task-uuid",
    "to_agent_id": "agent-uuid",
    "reason": "Requires specialized expertise",
    "context": {...}
  }
}
```

---

### 7. Memory Service

**Path:** `app/Services/Orchestration/Memory/MemoryService.php`

Agent key-value storage with durable and ephemeral partitions.

#### Methods

```php
// Store durable memory (survives restarts)
public function setDurable(string $agentId, string $key, mixed $value): void

// Store ephemeral memory (cleared on compaction)
public function setEphemeral(string $agentId, string $key, mixed $value): void

// Retrieve memory
public function get(string $agentId, string $key, mixed $default = null): mixed

// Delete memory
public function forget(string $agentId, string $key): void

// Compact (clear ephemeral, keep durable)
public function compact(string $agentId): void

// List all keys
public function keys(string $agentId, string $partition = 'all'): array
```

#### Configuration

```php
// config/orchestration.php
'memory' => [
    'storage_path' => storage_path('orchestration/memory'),
    'ttl_hours' => 24,  // Auto-compact after 24h
],
```

---

### 8. AgentInitService

**Path:** `app/Services/Orchestration/Init/AgentInitService.php`

4-step agent initialization protocol.

#### Initialization Steps

```php
public function initialize(AgentProfile $agent, WorkItem $task): array
```

**Step 1: Resume Memory**
- Load durable memory from MemoryService
- Restore last known state
- Return context for agent

**Step 2: Load Profile**
- Fetch agent profile (capabilities, settings)
- Load system instructions
- Return profile data

**Step 3: Healthcheck**
- Verify MCP server connection
- Test tool availability
- Confirm model access

**Step 4: Plan Confirmation**
- Present task to agent
- Request execution plan
- Confirm readiness

#### Return Structure

```php
[
    'status' => 'ready',  // or 'failed'
    'steps' => [
        'resume_memory' => ['status' => 'success', 'data' => [...]],
        'load_profile' => ['status' => 'success', 'data' => [...]],
        'healthcheck' => ['status' => 'success', 'checks' => [...]],
        'plan_confirm' => ['status' => 'success', 'plan' => '...'],
    ],
    'agent_id' => 'agent-uuid',
    'task_id' => 'task-uuid',
    'initialized_at' => '2025-10-07T12:34:56Z',
]
```

---

## Database Schema

### messages Table

```sql
CREATE TABLE messages (
    id UUID PRIMARY KEY,
    stream VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    task_id UUID,
    project_id UUID,
    to_agent_id UUID,
    from_agent_id UUID,
    headers JSONB,
    envelope JSONB NOT NULL,
    read_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX idx_messages_to_agent ON messages(to_agent_id, read_at);
CREATE INDEX idx_messages_stream ON messages(stream);
CREATE INDEX idx_messages_task ON messages(task_id);
```

### orchestration_artifacts Table

```sql
CREATE TABLE orchestration_artifacts (
    id UUID PRIMARY KEY,
    task_id UUID NOT NULL,
    hash VARCHAR(64) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100),
    size_bytes BIGINT NOT NULL,
    metadata JSONB,
    fe_uri TEXT NOT NULL,
    storage_path TEXT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX idx_artifacts_task ON orchestration_artifacts(task_id);
CREATE INDEX idx_artifacts_hash ON orchestration_artifacts(hash);
```

---

## CLI Commands

### postmaster:run

Start the Postmaster queue worker.

```bash
php artisan postmaster:run
```

**Options:**
- `--queue=postmaster` - Queue name (default: postmaster)
- `--sleep=3` - Seconds to sleep when no jobs (default: 3)
- `--tries=3` - Max retry attempts (default: 3)

---

## Testing

### Unit Tests

```bash
# ContentStore tests (8 tests)
composer test:unit -- --filter=ContentStoreTest

# SecretRedactor tests (8 tests)
composer test:unit -- --filter=SecretRedactorTest
```

### Feature Tests

```bash
# MessagingApi tests (5 tests)
composer test:feature -- --filter=MessagingApiTest

# ArtifactsApi tests (5 tests)
composer test:feature -- --filter=ArtifactsApiTest

# PostmasterWorkflow integration tests (5 tests)
composer test:feature -- --filter=PostmasterWorkflowTest
```

### All Orchestration Tests

```bash
composer test -- --filter=Orchestration
```

---

## Usage Examples

### Example 1: PM Sends Task to Agent

```php
use App\Jobs\Postmaster\ProcessParcel;

$parcel = [
    'to_agent_id' => $agent->id,
    'task_id' => $task->id,
    'stream' => 'command.delegation',
    'type' => 'pm.task.created',
    'headers' => ['from' => 'orchestration-pm'],
    'envelope' => [
        'body' => 'Analyze the attached dataset',
        'priority' => 'high',
    ],
    'attachments' => [
        'dataset' => [
            'content' => file_get_contents('data.csv'),
            'filename' => 'sales_data.csv',
            'mime_type' => 'text/csv',
        ],
    ],
];

ProcessParcel::dispatch($parcel, $task->id);
```

### Example 2: Agent Checks Inbox

```php
// Via HTTP API
$response = Http::get("/api/orchestration/agents/{$agent->id}/inbox", [
    'status' => 'unread',
]);

$messages = $response->json('data');
```

### Example 3: Agent Pulls Artifact

```php
use App\Services\Orchestration\Artifacts\ContentStore;

$contentStore = app(ContentStore::class);

// From fe:// URI
$parsed = $contentStore->parseUri('fe://artifacts/by-task/...');
$content = $contentStore->get($parsed['hash']);

// Or via HTTP download
$response = Http::get("/api/orchestration/artifacts/{$artifactId}/download");
$content = $response->body();
```

### Example 4: Agent Initialization

```php
use App\Services\Orchestration\Init\AgentInitService;

$initService = app(AgentInitService::class);
$result = $initService->initialize($agent, $task);

if ($result['status'] === 'ready') {
    // Agent is initialized and ready
    $plan = $result['steps']['plan_confirm']['plan'];
}
```

---

## Configuration Reference

**File:** `config/orchestration.php`

```php
return [
    'artifacts' => [
        'storage_path' => storage_path('orchestration/cas'),
        'max_size_bytes' => 100 * 1024 * 1024, // 100MB
    ],

    'messaging' => [
        'inbox_retention_days' => 30,
        'max_attachments_per_message' => 10,
    ],

    'secret_redaction' => [
        'enabled' => env('ORCHESTRATION_REDACT_SECRETS', true),
        'patterns' => [
            'aws_access_key' => '/AKIA[0-9A-Z]{16}/',
            'aws_secret_key' => '/[A-Za-z0-9\/+=]{40}/',
            'app_key' => '/base64:[A-Za-z0-9+\/=]{43,}/',
            'bearer_token' => '/Bearer\s+[A-Za-z0-9\-._~+\/]+=*/',
            'openai_key' => '/sk-(proj-)?[A-Za-z0-9]{20,}/',
        ],
    ],

    'memory' => [
        'storage_path' => storage_path('orchestration/memory'),
        'ttl_hours' => 24,
    ],
];
```

---

## Migration Guide

### From Legacy Systems

1. **Update PM to send parcels** to `ProcessParcel::dispatch()`
2. **Migrate agent polling** to MCP tools (`MessagesCheckTool`)
3. **Replace direct file access** with `ArtifactsPullTool`
4. **Adopt INIT protocol** for agent startup

---

## Troubleshooting

### Issue: Artifacts not found

**Symptom:** 404 when downloading artifact  
**Solution:** Verify `storage/orchestration/cas` exists and is writable

```bash
mkdir -p storage/orchestration/cas
chmod -R 775 storage/orchestration
```

### Issue: Messages not appearing in inbox

**Symptom:** Agent inbox is empty  
**Check:**
1. Verify `to_agent_id` is correct
2. Check message `stream` filter
3. Confirm `read_at` is null for unread

### Issue: Secret redaction not working

**Symptom:** Secrets visible in artifacts  
**Check:**
1. `config/orchestration.php` → `secret_redaction.enabled = true`
2. Verify pattern matches in `SecretRedactor`

---

## Performance Considerations

### ContentStore Deduplication

- SHA256 ensures identical content → single storage
- 100MB file stored once, referenced 100 times → 100MB total

### Message Pagination

- Default page size: 15 messages
- Use `?page=2` for subsequent pages
- Unread count cached for performance

### Artifact Caching

- `Cache-Control: public, max-age=31536000` for immutable content
- SHA256 hash guarantees uniqueness
- Safe to cache indefinitely

---

## Security

### Secret Redaction

All content passing through `ProcessParcel` is automatically redacted using `SecretRedactor`.

**Redacted Patterns:**
- AWS credentials
- API keys
- Bearer tokens
- Laravel APP_KEY

### Access Control

- Messages scoped to `to_agent_id`
- Artifacts scoped to `task_id`
- Download requires valid artifact ID

---

## Future Enhancements

### Planned Features (Post-Sprint)

1. **Artifact Versioning** - Track content changes over time
2. **Compression** - Gzip large artifacts before storage
3. **S3 Backend** - Alternative to local filesystem
4. **Message TTL** - Auto-expire old messages
5. **Encryption at Rest** - Encrypt sensitive artifacts

---

## Related Documentation

- [Fragments Engine Overview](../FRAGMENTS_ENGINE_MVP_PRD.md)
- [Agent Task Creation](../AGENT_TASK_CREATION_PROMPT.md)
- [MCP Servers Guide](../mcp-servers/)

---

## Changelog

### 2025-10-07 - Initial Release (SPRINT-51)

- ✅ ContentStore with SHA256 CAS
- ✅ ProcessParcel job with attachment handling
- ✅ SecretRedactor with pattern-based detection
- ✅ Messaging API (inbox, send, read, broadcast)
- ✅ Artifacts API (create, list, download)
- ✅ 4 MCP tools (messages_check, message_ack, artifacts_pull, handoff)
- ✅ MemoryService (durable + ephemeral)
- ✅ AgentInitService (4-step INIT)
- ✅ 31 tests (90 assertions)

**Commits:** 276377d → 9509d0e (11 commits)
