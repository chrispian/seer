# Type System — Proposal (Config-first, Strongly-Typed)

## Goals
- Strong types with flexible runtime.
- Deterministic artifacts (hash/version).
- Optional codegen for performance/ergonomics.

## Recommendations (Phased)

### Phase 1 — DB/Config-Driven Types (Runtime)
- Create `fe_types` + `fe_type_fields` + `fe_type_relations` (versioned JSON schema per type).
- Implement `TypeRegistry` + `TypeResolver` (cache schema, validate configs).
- Add a generic `TypesController` for list/detail/search against type aliases.
- Use **Sushi** or custom adapters for external/static sources (Movies API, Countries, etc.).

**Pros:** fastest to ship; supports API-backed and static datasets via adapters.  
**Cons:** Dynamic queries can be slower; careful indexing & caching needed.

### Phase 2 — Optional Codegen (Build Step)
- Job: `types:codegen <Type>` emits Eloquent model, form requests, policies, and typed resources:
  - Only for types marked `materialize: true`.
  - Generate migrations when persistence needed; otherwise Sushi/adapters.
- Keep the **config as the source of truth**; codegen derives from it.

**Pros:** strong compile-time hints, perf, IDE support.  
**Cons:** code drift risk → mitigate via generated file headers + re-gen checks.

### Phase 3 — Data Migration Engine (Later)
- Versioned transforms from schema vN→vN+1 with dry-run, sampling, and audit logs.

## Traits vs Inheritance
- Prefer a **Trait** (`HasTypeMeta`) for cross-cutting behaviors (validation, field mapping, capability flags).
- Provide an **optional base class** (`BaseTypeModel`) when it helps (shared guards, casts, query scopes).
- Keep domain models extend `Model`; opt-in to base class only if necessary.

## Runtime vs Build
- **Runtime:** default for flexibility; adapters for API/static; good for low-latency iteration.
- **Build (codegen):** opt-in for hot paths and developer ergonomics; uses the same config.

## APIs
- `/api/v2/types/{alias}/query` (list/search/filter/sort/paginate)
- `/api/v2/types/{alias}/{id}` (detail)
- Responses: `{ data, meta, schema }` where `schema` echoes the field config for the client.

## Caching
- Cache type schemas and query plans; invalidate on type version bump.
- For Sushi/adapters, respect their caching; add TTLs where appropriate.

## Security
- Type-level policies (view/list/update/delete).  
- Field-level guards (PII, secrets) with renderer hints.

## Minimal Schema Sketch
```json
{
  "key": "Invoice",
  "version": "1.0.0",
  "fields": [
    { "name": "number", "type": "string", "unique": true, "required": true },
    { "name": "amount", "type": "decimal:12,2", "required": true },
    { "name": "status", "type": "enum:pending,paid,void", "default": "pending" },
    { "name": "issued_at", "type": "datetime" }
  ],
  "relationships": [
    { "name": "company", "type": "belongsTo", "target": "Company" },
    { "name": "lines", "type": "hasMany", "target": "InvoiceLine" }
  ],
  "capabilities": ["search","filter","sort","export"],
  "validation": { "create": {}, "update": {} },
  "materialize": false
}
```
