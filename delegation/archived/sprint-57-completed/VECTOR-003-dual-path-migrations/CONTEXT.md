# Context: Dual-Path Database Migrations

## Current State Analysis

### **Existing Migration Issues**
Current migrations are PostgreSQL-only and will fail on SQLite:

```php
// database/migrations/2025_08_30_045548_create_fragment_embeddings.php:23
DB::statement('ALTER TABLE fragment_embeddings ADD COLUMN embedding vector(1536)');
```

This SQL fails on SQLite because:
1. `vector(1536)` type doesn't exist in SQLite
2. sqlite-vec extension uses BLOB storage instead
3. No conditional logic for different database drivers

### **Migration Strategy Requirements**
Need dual-path migrations that:
1. **Detect database driver** and apply appropriate schema
2. **Preserve PostgreSQL functionality** exactly
3. **Add SQLite compatibility** with BLOB vector storage
4. **Support rollbacks** safely on both platforms
5. **Handle indexes** differently per database type

## Target Architecture

### **Driver Detection Pattern**
```php
// Standard pattern for dual-path migrations
public function up(): void
{
    $driver = DB::connection()->getDriverName();
    
    match ($driver) {
        'pgsql' => $this->createPostgreSQLSchema(),
        'sqlite' => $this->createSQLiteSchema(),
        default => throw new \InvalidArgumentException("Unsupported driver: {$driver}")
    };
}
```

### **PostgreSQL Schema (Preserve Existing)**
```sql
-- Keep existing pgvector approach
CREATE TABLE fragment_embeddings (
    id bigserial PRIMARY KEY,
    fragment_id bigint NOT NULL REFERENCES fragments(id) ON DELETE CASCADE,
    provider varchar(255) NOT NULL,
    model varchar(255) NOT NULL,
    dims integer NOT NULL,
    embedding vector(1536),  -- pgvector native type
    content_hash varchar(255) NOT NULL,
    created_at timestamp DEFAULT now(),
    updated_at timestamp DEFAULT now(),
    UNIQUE(fragment_id, provider, model, content_hash)
);

CREATE INDEX fragment_embeddings_cosine_idx 
ON fragment_embeddings USING ivfflat (embedding vector_cosine_ops);
```

### **SQLite Schema (New Implementation)**
```sql
-- SQLite approach with BLOB storage
CREATE TABLE fragment_embeddings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fragment_id INTEGER NOT NULL,
    provider TEXT NOT NULL,
    model TEXT NOT NULL,
    dims INTEGER NOT NULL,
    embedding BLOB,  -- Vector data as binary BLOB
    content_hash TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fragment_id) REFERENCES fragments(id) ON DELETE CASCADE
);

-- Unique constraint
CREATE UNIQUE INDEX fragment_embeddings_unique 
ON fragment_embeddings (fragment_id, provider, model, content_hash);

-- Vector index (virtual table)
CREATE VIRTUAL TABLE fragment_embeddings_idx USING vec0(
    embedding FLOAT[1536]
);
```

## Implementation Challenges

### **Schema Differences**
| Feature | PostgreSQL | SQLite | Challenge |
|---------|------------|--------|-----------|
| Vector Type | `vector(1536)` | `BLOB` | Type mapping |
| Auto Increment | `bigserial` | `INTEGER PRIMARY KEY AUTOINCREMENT` | Syntax difference |
| Constraints | Full FK support | Basic FK support | Feature parity |
| Indexes | `ivfflat` | Virtual tables | Index strategy |
| Timestamps | `timestamp` | `DATETIME` | Type difference |

### **Extension Dependencies**
```php
// PostgreSQL: Check pgvector availability
if (!DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector'")) {
    throw new \Exception('pgvector extension required');
}

// SQLite: Check sqlite-vec availability  
try {
    DB::select("SELECT vec_version()");
} catch (\Exception $e) {
    // Extension not available - migrations should still work
    Log::warning('sqlite-vec extension not available');
}
```

### **Migration Ordering**
```php
// Need to handle existing installations
1. Check if fragment_embeddings table exists
2. If exists with vector column (PostgreSQL), preserve
3. If new installation, use appropriate schema for driver
4. Handle data migration if switching drivers
```

## Risk Assessment

### **High Risk Areas**
1. **Breaking Existing Deployments**: PostgreSQL installations must continue working
2. **Data Migration**: Moving between PostgreSQL and SQLite requires vector format conversion
3. **Rollback Complexity**: Different schemas complicate rollback procedures

### **Medium Risk Areas**
1. **Index Performance**: SQLite virtual tables may perform differently than PostgreSQL indexes
2. **Extension Availability**: sqlite-vec may not be available in all environments
3. **Schema Validation**: Laravel schema validation may not handle driver differences

### **Mitigation Strategies**
1. **Extensive Testing**: Test migrations on both clean and existing databases
2. **Gradual Rollout**: Phase implementation with fallback options
3. **Documentation**: Clear upgrade/downgrade procedures
4. **Monitoring**: Add telemetry to track migration success/failure rates