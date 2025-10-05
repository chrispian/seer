# SQLite-First Vector Store Rollout

## Overview
- Prep a sprint plan so we can ship the NativePHP desktop build with embedded SQLite + vector search while keeping PostgreSQL/pgvector as an optional adapter.
- Deliverables: migration guards, abstraction layer for embeddings/search, and packaging steps for SQLite vector extensions.

## Findings
- Laravel already defaults to SQLite (`config/database.php:19-40`), but embeddings/search rely on Postgres-only features: pgvector column creation (`database/migrations/2025_08_30_045548_create_fragment_embeddings.php:22-23`), `?::vector` inserts in the job (`app/Jobs/EmbedFragment.php:69-74`), and hybrid SQL in the search command (`app/Actions/Commands/SearchCommand.php:158-180`).
- Other migrations add Postgres JSONB indexes yet guard on driver checks (e.g., `database/migrations/2025_09_28_220620_enhance_fragments_telemetry.php:10-24`), so remaining blockers are limited to the embedding schema/queries.
- SQLite ships with NativePHP; pgvector cannot—running it would require bundling a Postgres server. SQLite vector extensions (e.g., sqlite-vec/sqlite-vss) are MIT/Apache and can be distributed with the app.
- Agent memory tables already use UUID + JSON columns that work on both SQLite and Postgres (`database/migrations/20251004151942_create_agent_memory_tables.php:5-44`).

## Suggestions for the Sprint Planner
1. **Introduce EmbeddingStore abstraction**: define interfaces for vector writes/reads and create `SqliteVectorStore` (JSON/BLOB + sqlite-vec operations) and `PgVectorStore`. Update `EmbedFragmentAction`, `EmbedFragment` job, and `SearchCommand` to route through this layer based on `DB::connection()->getDriverName()`.
2. **Dual-path migrations**: wrap pgvector DDL in driver checks and add SQLite equivalents (plain JSON column + extension load logic). Provide seed scripts to load the sqlite vector extension during migration or bootstrap.
3. **Feature detection & fallbacks**: when vectors aren’t available, surface status to the UI (e.g., search mode = `text-only`) instead of quietly skipping; ensure tests cover both SQLite-with-vectors and Postgres+pgvector.
4. **Packaging tasks**: document or automate bundling of the chosen SQLite vector extension with NativePHP, including instructions for macOS/Windows/Linux packaging targets.
5. **Configuration story**: keep `.env` toggles for `DB_CONNECTION` and `EMBEDDINGS_DRIVER`, defaulting to SQLite/vector extension but allowing opt-in Postgres.
6. **Testing/CI**: add regression tests that run the embedding pipeline + hybrid search under SQLite (with mocked vector operations) and under Postgres (CI job with pgvector) to guard both adapters.

Use this brief to create sprint tickets that land the SQLite-first vector stack while preserving optional Postgres support.
