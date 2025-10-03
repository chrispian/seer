# ENG-02 Context

## Codebase Touchpoints
- `app/Actions/RouteToVault.php` — currently logs, strips `vault:` directive, then forces `debug`. Replace this with service-driven routing.
- `app/Services/VaultRoutingRuleService.php` — provides CRUD + list; extend with `resolveForFragment(Fragment $fragment): ?array` (or similar).
- `app/Models/VaultRoutingRule.php` — relationships to vault/project; ensure scopes available for service.
- `app/Livewire/RoutingRulesManager.php` + `resources/views/livewire/routing-rules-manager.blade.php` — UI already managing rules; no major changes expected besides reflecting new behaviour in toasts/messages.
- `database/factories/*` + `database/seeders` — update to seed sample rules.
- Tests: `tests/Feature/RoutingCommandTest.php` (extend) and add `tests/Unit/VaultRoutingRuleServiceTest.php` (new) or similar.

## Business Rules
- Apply only active rules (`is_active = true`).
- Rules should be evaluated by ascending `priority`, then fallback to created time/ID.
- Optional scoping: `scope_vault_id` / `scope_project_id` limit rules to a context; treat `null` as global.
- Match types currently: `keyword`, `tag`, `type`, `regex`. Keep implementation flexible; default to string contains for keyword.
- If rule sets both `target_vault_id` and `target_project_id`, assign both. If only vault, pick default project for that vault. Respect `match_value` being nullable (future condition JSON may apply additional checks).

## Open Questions
- Should rule evaluation inspect fragment tags/metadata vs. raw message? (For now, use message/type + simple contains; escalate if more context needed.)
- How to handle conflicting rules with the same priority? (Document tie-breaker decision in README/PLAN).
- Should routing update existing fragments when rules change? (Out of scope; note as follow-up.)

## References
- `PROJECT_PLAN.md` → ENG-02 section lines 47-53.
- ADR backlog: mode strategy (pending), AI fallback (ADR-002), UI stack (ADR-003) for context.

## Definition of Done Recap
See `PLAN.md` acceptance criteria. Confirm QA by creating sample fragments via UI or `tinker` to validate rule application.
