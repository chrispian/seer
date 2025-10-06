# /help include — Cheat Sheet

**Command**
```
/include [uid:<fe:…> | "search terms" | query:"…"] [mode:ref|copy] [layout:checklist|table|cards] [@ws:… @proj:…]
```

**Examples**
- `/include uid:fe:note/7Q2M9K` — include a single item (live reference)
- `/include "laravel queue" type:bookmark mode:ref` — search & include
- `/include query:"type:todo where:done=false sort:due" layout:checklist` — live list
- `/include uid:fe:todo/AB12 mode:copy` — copy content into host

**Notes**
- `mode:ref` edits the canonical target; `mode:copy` edits only the host.
- `cards` layout supports bento/gallery/post via `template` overrides.
- Use `![[fe:UID]]` in Markdown, or fenced `fragments` blocks for lists.
