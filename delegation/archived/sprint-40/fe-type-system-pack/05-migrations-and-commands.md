# Migrations & Commands (Stubs)

## Migration: `create_fragment_type_registry_table` (stub)
```php
Schema::create('fragment_type_registry', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->string('slug')->unique();
    $t->string('version')->nullable();
    $t->text('source_path');
    $t->string('schema_hash')->nullable();
    $t->json('hot_fields')->nullable();
    $t->json('policy')->nullable();
    $t->json('ui')->nullable();
    $t->json('prompts')->nullable();
    $t->timestamps();
});
```

## Command: `frag:type:make {slug}`
- Copies template pack to `storage/fragments/types/{slug}` with sensible defaults.

## Command: `frag:type:cache`
- Rebuilds registry cache and **prints SQL suggestions** from all `indexes.yaml`.

## Command: `frag:type:validate {slug} {sample}`
- Loads schema from registry ➜ validates the provided JSON file ➜ prints precise errors.
