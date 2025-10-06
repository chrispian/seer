# Implementation Plan: Dual-Path Database Migrations

## Overview
Create database migrations that support both PostgreSQL+pgvector and SQLite+sqlite-vec, preserving existing functionality while enabling new SQLite deployments.

## Technical Implementation

### **Step 1: Migration Foundation (2-3h)**

#### Create Dual-Path Migration Helper
**File**: `app/Database/MigrationHelpers/VectorMigrationHelper.php`
```php
<?php

namespace App\Database\MigrationHelpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VectorMigrationHelper
{
    public static function getDriver(): string
    {
        return DB::connection()->getDriverName();
    }
    
    public static function isPostgreSQL(): bool
    {
        return self::getDriver() === 'pgsql';
    }
    
    public static function isSQLite(): bool
    {
        return self::getDriver() === 'sqlite';
    }
    
    public static function hasPostgreSQLVectorSupport(): bool
    {
        if (!self::isPostgreSQL()) {
            return false;
        }
        
        try {
            $result = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector'");
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public static function hasSQLiteVectorSupport(): bool
    {
        if (!self::isSQLite()) {
            return false;
        }
        
        try {
            DB::select("SELECT vec_version()");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

### **Step 2: Update Existing Migration (2-3h)**

#### Modify Fragment Embeddings Migration
**File**: `database/migrations/2025_08_30_045548_create_fragment_embeddings.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Database\MigrationHelpers\VectorMigrationHelper;

return new class extends Migration
{
    public function up(): void
    {
        // Create base table structure (common to both drivers)
        Schema::create('fragment_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fragment_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('model')->nullable(); // Make nullable for existing data
            $table->unsignedInteger('dims');
            $table->string('content_hash')->nullable(); // Make nullable for existing data
            $table->timestamps();
        });
        
        // Add driver-specific vector column and indexes
        if (VectorMigrationHelper::isPostgreSQL()) {
            $this->createPostgreSQLVectorSupport();
        } elseif (VectorMigrationHelper::isSQLite()) {
            $this->createSQLiteVectorSupport();
        } else {
            throw new \InvalidArgumentException('Unsupported database driver: ' . VectorMigrationHelper::getDriver());
        }
    }
    
    protected function createPostgreSQLVectorSupport(): void
    {
        // Add vector column (Laravel has no native 'vector' type)
        DB::statement('ALTER TABLE fragment_embeddings ADD COLUMN embedding vector(1536)');
        
        // Create unique constraint
        Schema::table('fragment_embeddings', function (Blueprint $table) {
            $table->unique(['fragment_id', 'provider'], 'fragment_embeddings_unique_legacy');
        });
        
        // Optional ANN index (commented out by default)
        // DB::statement("CREATE INDEX fragment_embeddings_ivfflat
        //     ON fragment_embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)");
    }
    
    protected function createSQLiteVectorSupport(): void
    {
        // Add BLOB column for vector storage
        Schema::table('fragment_embeddings', function (Blueprint $table) {
            $table->binary('embedding')->nullable();
        });
        
        // Create unique constraint
        Schema::table('fragment_embeddings', function (Blueprint $table) {
            $table->unique(['fragment_id', 'provider'], 'fragment_embeddings_unique_legacy');
        });
        
        // Create vector index virtual table if extension available
        if (VectorMigrationHelper::hasSQLiteVectorSupport()) {
            try {
                DB::statement('CREATE VIRTUAL TABLE fragment_embeddings_idx USING vec0(embedding FLOAT[1536])');
            } catch (\Exception $e) {
                // Index creation failure shouldn't break migration
                \Illuminate\Support\Facades\Log::warning('SQLite vector index creation failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function down(): void
    {
        // Drop vector index if it exists (SQLite)
        if (VectorMigrationHelper::isSQLite()) {
            try {
                DB::statement('DROP TABLE IF EXISTS fragment_embeddings_idx');
            } catch (\Exception $e) {
                // Ignore errors during rollback
            }
        }
        
        Schema::dropIfExists('fragment_embeddings');
    }
};
```

### **Step 3: Update Model Enhancement Migration (2-3h)**

#### Modify Model/Hash Migration  
**File**: `database/migrations/2025_08_30_053234_add_model_hash_to_fragment_embeddings.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Database\MigrationHelpers\VectorMigrationHelper;

return new class extends Migration
{
    public function up(): void
    {
        // Add model and content_hash columns if they don't exist
        Schema::table('fragment_embeddings', function (Blueprint $table) {
            if (!Schema::hasColumn('fragment_embeddings', 'model')) {
                $table->string('model')->nullable();
            }
            if (!Schema::hasColumn('fragment_embeddings', 'content_hash')) {
                $table->string('content_hash')->nullable();
            }
        });

        // Make columns NOT NULL (driver-specific syntax)
        if (VectorMigrationHelper::isPostgreSQL()) {
            DB::statement('ALTER TABLE fragment_embeddings ALTER COLUMN model SET NOT NULL');
            DB::statement('ALTER TABLE fragment_embeddings ALTER COLUMN content_hash SET NOT NULL');
        } else {
            // SQLite doesn't support ALTER COLUMN, need to recreate table
            $this->recreateSQLiteTableWithNotNull();
        }

        // Create unique index (driver-specific)
        $this->createUniqueIndex();
        
        // Backfill existing data
        $this->backfillExistingData();
    }
    
    protected function recreateSQLiteTableWithNotNull(): void
    {
        if (!VectorMigrationHelper::isSQLite()) {
            return;
        }
        
        // SQLite approach: create new table, copy data, rename
        DB::statement('
            CREATE TABLE fragment_embeddings_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fragment_id INTEGER NOT NULL,
                provider TEXT NOT NULL,
                model TEXT NOT NULL,
                dims INTEGER NOT NULL,
                embedding BLOB,
                content_hash TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (fragment_id) REFERENCES fragments(id) ON DELETE CASCADE
            )
        ');
        
        // Copy existing data with default values for new columns
        DB::statement("
            INSERT INTO fragment_embeddings_new 
            (id, fragment_id, provider, model, dims, embedding, content_hash, created_at, updated_at)
            SELECT 
                id, 
                fragment_id, 
                provider, 
                COALESCE(model, 'text-embedding-3-small') as model,
                dims, 
                embedding, 
                COALESCE(content_hash, 'legacy') as content_hash,
                created_at, 
                updated_at
            FROM fragment_embeddings
        ");
        
        // Replace old table
        DB::statement('DROP TABLE fragment_embeddings');
        DB::statement('ALTER TABLE fragment_embeddings_new RENAME TO fragment_embeddings');
    }
    
    protected function createUniqueIndex(): void
    {
        try {
            if (VectorMigrationHelper::isPostgreSQL()) {
                DB::statement('
                    CREATE UNIQUE INDEX IF NOT EXISTS fragment_embeddings_unique
                    ON fragment_embeddings (fragment_id, provider, model, content_hash)
                ');
            } else {
                DB::statement('
                    CREATE UNIQUE INDEX IF NOT EXISTS fragment_embeddings_unique
                    ON fragment_embeddings (fragment_id, provider, model, content_hash)
                ');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Index creation failed', [
                'error' => $e->getMessage(),
                'driver' => VectorMigrationHelper::getDriver()
            ]);
        }
    }

    protected function backfillExistingData(): void
    {
        $version = (string) config('fragments.embeddings.version', '1');
        $embeddings = DB::table('fragment_embeddings')->whereNull('content_hash')->get();

        foreach ($embeddings as $embedding) {
            $fallbackModel = match ($embedding->provider) {
                'openai' => 'text-embedding-3-small',
                'ollama' => 'nomic-embed-text',
                default => 'text-embedding-3-small'
            };

            $model = $embedding->model ?? $fallbackModel;
            
            $fragment = DB::table('fragments')->find($embedding->fragment_id);
            if (!$fragment) {
                continue;
            }

            $content = trim($fragment->edited_message ?? $fragment->message ?? '');
            $hash = md5($content . '|' . $embedding->provider . '|' . $model . '|' . $version);

            DB::table('fragment_embeddings')
                ->where('id', $embedding->id)
                ->update([
                    'model' => $model,
                    'content_hash' => $hash,
                ]);
        }
    }

    public function down(): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS fragment_embeddings_unique');
        } catch (\Exception $e) {
            // Ignore errors during rollback
        }

        Schema::table('fragment_embeddings', function (Blueprint $table) {
            if (Schema::hasColumn('fragment_embeddings', 'content_hash')) {
                $table->dropColumn('content_hash');
            }
            if (Schema::hasColumn('fragment_embeddings', 'model')) {
                $table->dropColumn('model');
            }
        });
    }
};
```

### **Step 4: FTS5 Support Migration (2-3h)**

#### Create FTS5 Migration for SQLite
**File**: `database/migrations/2025_01_04_000001_create_sqlite_fts5_support.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Database\MigrationHelpers\VectorMigrationHelper;

return new class extends Migration
{
    public function up(): void
    {
        // Only create FTS5 support for SQLite
        if (!VectorMigrationHelper::isSQLite()) {
            return;
        }
        
        $this->createFtsTable();
        $this->populateFtsTable();
        $this->createFtsTriggers();
    }
    
    protected function createFtsTable(): void
    {
        // Create FTS5 virtual table for full-text search
        DB::statement('
            CREATE VIRTUAL TABLE fragments_fts USING fts5(
                title,
                content,
                content=fragments,
                content_rowid=id
            )
        ');
    }
    
    protected function populateFtsTable(): void
    {
        // Populate FTS table with existing fragments
        DB::statement("
            INSERT INTO fragments_fts(rowid, title, content)
            SELECT 
                id,
                COALESCE(title, '') as title,
                COALESCE(edited_message, message, '') as content
            FROM fragments
            WHERE COALESCE(edited_message, message, '') != ''
        ");
    }
    
    protected function createFtsTriggers(): void
    {
        // Trigger for INSERT
        DB::statement('
            CREATE TRIGGER fragments_fts_insert AFTER INSERT ON fragments
            BEGIN
                INSERT INTO fragments_fts(rowid, title, content)
                VALUES (NEW.id, COALESCE(NEW.title, ""), COALESCE(NEW.edited_message, NEW.message, ""));
            END
        ');
        
        // Trigger for UPDATE
        DB::statement('
            CREATE TRIGGER fragments_fts_update AFTER UPDATE ON fragments
            BEGIN
                UPDATE fragments_fts SET 
                    title = COALESCE(NEW.title, ""),
                    content = COALESCE(NEW.edited_message, NEW.message, "")
                WHERE rowid = NEW.id;
            END
        ');
        
        // Trigger for DELETE
        DB::statement('
            CREATE TRIGGER fragments_fts_delete AFTER DELETE ON fragments
            BEGIN
                DELETE FROM fragments_fts WHERE rowid = OLD.id;
            END
        ');
    }

    public function down(): void
    {
        if (!VectorMigrationHelper::isSQLite()) {
            return;
        }
        
        // Drop triggers
        DB::statement('DROP TRIGGER IF EXISTS fragments_fts_insert');
        DB::statement('DROP TRIGGER IF EXISTS fragments_fts_update');
        DB::statement('DROP TRIGGER IF EXISTS fragments_fts_delete');
        
        // Drop FTS table
        DB::statement('DROP TABLE IF EXISTS fragments_fts');
    }
};
```

### **Step 5: Migration Testing & Validation (1-2h)**

#### Create Migration Test
**File**: `tests/Feature/VectorMigrationTest.php`
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Database\MigrationHelpers\VectorMigrationHelper;

class VectorMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sqlite_migrations_create_proper_schema()
    {
        config(['database.default' => 'sqlite']);
        
        $this->assertTrue(VectorMigrationHelper::isSQLite());
        
        // Check that fragment_embeddings table exists with correct structure
        $this->assertTrue(\Schema::hasTable('fragment_embeddings'));
        $this->assertTrue(\Schema::hasColumn('fragment_embeddings', 'embedding'));
        $this->assertTrue(\Schema::hasColumn('fragment_embeddings', 'model'));
        $this->assertTrue(\Schema::hasColumn('fragment_embeddings', 'content_hash'));
        
        // Check unique index exists
        $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='fragment_embeddings'");
        $indexNames = array_column($indexes, 'name');
        $this->assertContains('fragment_embeddings_unique', $indexNames);
    }

    public function test_postgresql_migrations_preserve_existing_schema()
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL not available');
        }
        
        $this->assertTrue(VectorMigrationHelper::isPostgreSQL());
        
        // Test would verify PostgreSQL schema creation
        // This requires PostgreSQL with pgvector in test environment
    }
}
```

## Time Allocation

| Task | Estimated Time | Complexity |
|------|----------------|------------|
| Migration foundation & helpers | 2-3h | Medium |
| Update existing embeddings migration | 2-3h | Medium |
| Update model/hash migration | 2-3h | High |
| FTS5 support migration | 2-3h | Medium |
| Testing & validation | 1-2h | Low |
| **Total** | **8-12h** | **Medium** |

## Dependencies & Integration

### **Required from Previous Tasks**
- VECTOR-001: VectorMigrationHelper uses driver detection patterns
- VECTOR-002: Schema requirements from SQLite implementation

### **Enables Following Tasks**
- VECTOR-004: Search abstraction needs proper database schema
- VECTOR-005: Feature detection needs migration completion
- VECTOR-006: NativePHP packaging needs complete schema