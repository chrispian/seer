# Vector Store Deployment Guide

This guide covers deploying Fragments Engine with vector search capabilities across different environments.

## Overview

Fragments Engine supports dual-database vector storage:
- **PostgreSQL + pgvector**: Full-featured vector search for server deployments
- **SQLite + sqlite-vec**: Embedded vector search for desktop/standalone deployments

## Server Deployment (PostgreSQL)

### Prerequisites
- PostgreSQL 12+ 
- pgvector extension

### Installation
```bash
# Install pgvector extension
sudo apt install postgresql-contrib
sudo -u postgres psql -c "CREATE EXTENSION vector;"

# Configure Laravel
cp .env.example .env
# Set DB_CONNECTION=pgsql and configure PostgreSQL settings
```

### Verification
```bash
php artisan vector:status --detailed
php artisan vector:config validate
```

## Desktop Deployment (SQLite)

### NativePHP Integration

For desktop applications using NativePHP, the system automatically configures SQLite with vector capabilities.

#### Extension Management

The application includes automatic sqlite-vec extension detection and loading:

```php
// Automatic extension loading in production
if (VectorMigrationHelper::isSQLite()) {
    $extensionPath = config('vectors.drivers.sqlite.extension_path');
    if ($extensionPath && file_exists($extensionPath)) {
        DB::statement("SELECT load_extension('{$extensionPath}')");
    }
}
```

#### Fallback Strategies

When sqlite-vec is not available, the system gracefully falls back to:
1. FTS5 text search only
2. Basic LIKE-based search as ultimate fallback

### Packaging for Distribution

For NativePHP desktop applications:

#### 1. SQLite-vec Extension Bundling

Create a packaging script that includes the sqlite-vec extension:

```bash
#!/bin/bash
# Package sqlite-vec for desktop distribution

PLATFORM=$(uname -s)
ARCH=$(uname -m)

case $PLATFORM in
    Darwin)
        EXTENSION_FILE="vec.dylib"
        ;;
    Linux)
        EXTENSION_FILE="vec.so" 
        ;;
    CYGWIN*|MINGW32*|MSYS*|MINGW*)
        EXTENSION_FILE="vec.dll"
        ;;
esac

# Download or compile sqlite-vec for target platform
mkdir -p storage/extensions
curl -L "https://github.com/asg017/sqlite-vec/releases/latest/download/sqlite-vec-${PLATFORM}-${ARCH}.tar.gz" \
    | tar -xz -C storage/extensions/

# Set extension path in production
echo "SQLITE_VEC_EXTENSION_PATH=storage/extensions/${EXTENSION_FILE}" >> .env.production
```

#### 2. Database Migration Strategy

Ensure migrations work across both database types:

```php
// In migrations, use VectorMigrationHelper for compatibility
if (VectorMigrationHelper::isPostgreSQL()) {
    DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
    VectorMigrationHelper::addVectorColumn('table_name', 'vector_column', 1536);
} elseif (VectorMigrationHelper::isSQLite()) {
    // SQLite vector support with fallback
    VectorMigrationHelper::addVectorColumn('table_name', 'vector_column', 1536);
}
```

#### 3. Configuration for Desktop

Desktop applications should use environment-specific configuration:

```env
# Desktop/NativePHP configuration
VECTOR_STORE_DRIVER=auto
DB_CONNECTION=sqlite
DB_DATABASE=database/fragments.sqlite

# Enable automatic extension loading
SQLITE_VEC_AUTO_LOAD=true
SQLITE_VEC_EXTENSION_PATH=storage/extensions/vec.dylib

# Optimize for desktop performance  
VECTOR_BATCH_SIZE=50
HYBRID_SEARCH_MAX_RESULTS=20
VECTOR_ENABLE_QUERY_CACHE=true
```

## Production Deployment Checklist

### PostgreSQL Production
- [ ] pgvector extension installed and version ≥ 0.5.0
- [ ] Database connection configured with appropriate pool size
- [ ] Vector indexes created for performance
- [ ] Monitoring configured for vector query performance

### SQLite Desktop
- [ ] sqlite-vec extension bundled with application
- [ ] FTS5 support enabled 
- [ ] Extension loading path configured correctly
- [ ] Fallback search methods tested
- [ ] Database file permissions configured

## Performance Optimization

### PostgreSQL Tuning
```sql
-- Optimize for vector operations
SET work_mem = '256MB';
SET maintenance_work_mem = '2GB';
SET effective_cache_size = '8GB';

-- Index tuning for HNSW
SET hnsw.ef_search = 64;  -- Higher = better recall, slower
```

### SQLite Tuning
```sql
-- Optimize SQLite for vector operations
PRAGMA journal_mode = WAL;
PRAGMA synchronous = NORMAL;
PRAGMA cache_size = -64000;  -- 64MB cache
PRAGMA temp_store = MEMORY;
```

## Monitoring and Diagnostics

### Health Checks
```bash
# Check vector capabilities
php artisan vector:status --detailed

# Validate configuration
php artisan vector:config validate

# Test search functionality
php artisan hybrid-search:test "sample query"
```

### Performance Monitoring
```bash
# Enable query logging for analysis
php artisan vector:config show --json | grep log_queries

# Benchmark search performance
php artisan vector:config show | grep benchmark
```

## Troubleshooting

### Common Issues

#### SQLite Extension Not Loading
```bash
# Check extension path
php -r "echo sqlite3_open(':memory:'); var_dump(sqlite3_loadExtension('path/to/vec.so'));"

# Verify architecture compatibility
file storage/extensions/vec.so
uname -m
```

#### PostgreSQL Extension Missing
```sql
-- Check installed extensions
SELECT * FROM pg_extension WHERE extname = 'vector';

-- Install if missing
CREATE EXTENSION vector;
```

#### Performance Issues
```bash
# Check indexes
php artisan vector:status --detailed | grep -A5 "Index Optimization"

# Verify query patterns
php artisan vector:config show | grep -A5 "performance"
```

### Getting Help

1. Check system capabilities: `php artisan vector:status --detailed`
2. Validate configuration: `php artisan vector:config validate`
3. Review logs for vector-related errors
4. Test with minimal dataset to isolate issues

## Migration Path

### From PostgreSQL to SQLite (Desktop)
1. Export vector embeddings: `php artisan vector:export --format=sqlite`
2. Configure SQLite environment
3. Import data: `php artisan vector:import --from=postgresql.dump`
4. Verify: `php artisan vector:status --detailed`

### From SQLite to PostgreSQL (Scale Up)
1. Set up PostgreSQL with pgvector
2. Export SQLite data: `php artisan vector:export --format=postgresql`
3. Configure PostgreSQL connection
4. Import and optimize: `php artisan vector:import --optimize-indexes`

## Security Considerations

- Ensure vector data doesn't contain sensitive information
- Use encrypted storage for desktop deployments
- Implement proper access controls for vector search endpoints
- Validate search inputs to prevent injection attacks
- Monitor for unusual search patterns that might indicate data mining