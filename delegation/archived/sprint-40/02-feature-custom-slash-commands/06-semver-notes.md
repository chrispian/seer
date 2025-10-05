# SemVer Notes for This Track

- Start at **0.0.1** for the first working backend MVP.
- Increment:
  - **PATCH (0.0.x)** for bugfixes and tiny internal improvements (no DSL/runner breaking changes).
  - **MINOR (0.x.0)** when you add step types, change DSL surface, or add significant command behavior that pack authors depend on.
  - **MAJOR (x.0.0)** once public API/DSL is stable and breaking changes are planned; not expected soon.
- Therefore:
  - **0.0.1** — MVP backend
  - **0.0.2** — enhancements (nice-to-haves)
  - **0.1.0** — first compatible UI exposure / additional stable step types
