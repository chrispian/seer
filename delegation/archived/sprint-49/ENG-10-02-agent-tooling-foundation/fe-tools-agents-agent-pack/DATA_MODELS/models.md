# Models & Fields (Guidance)
- Use `$guarded = [];` and `$hidden` for sensitive fields as needed.
- Prefer `jsonb` for `state` and `metadata` to allow flexible extension.
- Index typical query fields (status, type, project_id, created_at).
