# ADR 004: Use Foreign Keys for Model Selection in Chat Sessions

**Date:** 2025-10-14  
**Status:** Accepted  
**Deciders:** Product Team

## Context

Chat sessions need to track which AI model is being used. The current implementation stores this as two string columns:
- `model_provider` (e.g., "openai", "anthropic")
- `model_name` (e.g., "gpt-4o-mini", "claude-3-haiku")

This approach has several issues:

1. **No referential integrity**: Sessions can reference models that don't exist or have been removed
2. **String parsing required**: The UI sends `"provider_id/model_id"`, requiring string manipulation and lookups
3. **Confusing flow**: Value goes through multiple transformations (`/` → `:`, ID → name)
4. **Data inconsistency**: If a model's `model_id` changes in the `models` table, existing sessions break
5. **No tracking**: Can't tell which sessions are using deprecated models
6. **Violates relational design**: Storing denormalized data (provider name, model ID) instead of a relationship

### Current Problematic Flow

```
User selects model in UI
  ↓
UI sends: "2/claude-3-haiku" (provider_id/model_id string)
  ↓
UI transforms: "/" → ":" = "2:claude-3-haiku"
  ↓
Backend splits on ":" and looks up Provider.id=2
  ↓
Backend extracts provider.provider = "anthropic"
  ↓
Stores: model_provider="anthropic", model_name="claude-3-haiku"
```

**Problems exposed:**
- Session 37 had `model_provider="openai"`, `model_name="gpt-4.1-mini"` 
- Invalid because UI sent provider ID "1" instead of provider name "openai"
- Required multiple debugging cycles to understand the data flow
- String manipulation made the bug non-obvious

## Decision

**Replace string-based model storage with a foreign key relationship.**

### Schema Changes

Add `ai_model_id` column to `chat_sessions` table:

```php
Schema::table('chat_sessions', function (Blueprint $table) {
    $table->foreignId('ai_model_id')
        ->nullable()
        ->constrained('models')
        ->nullOnDelete();
});
```

Deprecate (but keep for backward compatibility):
- `model_provider` (string)
- `model_name` (string)

### API Changes

**ModelController::available()** - No change needed, continue returning model metadata

**ChatSessionController::updateModel()** - Simplified:

```php
public function updateModel(Request $request, ChatSession $chatSession)
{
    $request->validate([
        'ai_model_id' => 'required|integer|exists:models,id',
    ]);

    $chatSession->update([
        'ai_model_id' => $request->input('ai_model_id'),
    ]);

    return response()->json(['success' => true]);
}
```

**ChatSession model** - Add relationship:

```php
public function aiModel()
{
    return $this->belongsTo(AIModel::class, 'ai_model_id');
}
```

### Usage

```php
// When processing chat request
$session = ChatSession::with('aiModel.provider')->find($id);
$providerKey = $session->aiModel->provider->provider;  // "openai"
$modelId = $session->aiModel->model_id;  // "gpt-4o-mini"
```

## Consequences

### Positive

1. **Referential integrity**: Database enforces that sessions can only reference valid models
2. **Single source of truth**: Model metadata lives in one place (`models` table)
3. **Obvious data flow**: UI sends integer ID, backend stores integer ID
4. **Easier debugging**: Can see exactly which model row a session uses
5. **Model lifecycle tracking**: Can identify sessions using deprecated models
6. **Future-proof**: Can add model versioning, deprecation warnings, migration paths
7. **Performance**: One join vs. string lookups
8. **Follows relational design principles**: Proper normalized schema

### Negative

1. **Migration effort**: Need to backfill `ai_model_id` for existing sessions
2. **Backward compatibility**: Must maintain old columns temporarily
3. **Null handling**: Sessions without a model selection need careful handling

### Migration Strategy

1. **Phase 1**: Add `ai_model_id` column (nullable)
2. **Phase 2**: Backfill existing sessions by matching `model_provider`/`model_name` to `models` table
3. **Phase 3**: Update all code to use `ai_model_id`
4. **Phase 4**: Mark `model_provider`/`model_name` as deprecated
5. **Phase 5** (future): Remove deprecated columns after confirming no usage

## Alternatives Considered

### Alternative 1: Use AIModel ID without FK

Store the model ID but without a foreign key constraint. **Rejected** because it loses referential integrity benefits.

### Alternative 2: Composite key (provider_id, model_id)

Store both IDs as separate columns. **Rejected** because the `models` table already has a single primary key, and this adds unnecessary complexity.

### Alternative 3: Keep string columns but validate

Add validation to ensure strings match existing models. **Rejected** because it doesn't solve the fundamental issues of denormalization and string manipulation.

## Related Issues

- Session 37 bug: Invalid model selection stored as `model_provider="1"` (should be "openai")
- Confusion about data flow through UI → API → storage
- Need for proper relationships throughout the codebase

## Design Principle

**For this project: Always use proper foreign key relationships for entity references.**

This applies to:
- Model selection in chat sessions
- Provider references
- User associations
- Vault/Project relationships
- Any other entity-to-entity relationship

String-based references should only be used for:
- External API identifiers that we don't control
- Temporary compatibility during migrations
- Non-relational data (tags, metadata)

## References

- Database normalization best practices
- Laravel Eloquent relationships: https://laravel.com/docs/eloquent-relationships
- Related: ADR 003 (if exists) on database design principles
