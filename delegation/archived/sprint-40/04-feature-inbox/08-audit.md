# Audit & Events

- Record an `inbox_actions` log (optional table) or append to fragment history:
  - `fragment_id`, `action` (`accept|archive|reopen|edit`), `by_user`, `payload_diff`, `ts`.
- Emit events:
  - `FragmentAccepted`, `FragmentArchived`, `FragmentReopened`, `FragmentEdited`.
- metrics:
  - `time_to_review = reviewed_at - inbox_at`
  - acceptance rate over time, by type, by source.
