# DEV-02 Test Stabilization Notes

- Reproduced failing suite; remaining issue was foreign key constraint during `RecallPerformanceTest` when SQLite inserted `recall_decisions` pointing to non-existent fragments.
- Seeded a fragment pool inside the test and reuse real IDs for `selected_fragment_id`; retains dismiss/select mix while satisfying FK constraints.
- Parallel and sequential suites now fully green (117 passing, 1 intentional skip). Sequential `composer test` ≈4.4s; parallel `composer test:parallel` ≈1.2s.
- Confirmed main chat app loads; Filament admin can be ignored for now (scheduled removal). If re-enabled before removal, `resources/views/vendor/filament-panels/components/layout/*.blade.php` now target `Filament\Support\Enums\Width` to match v4.0.18 API.
