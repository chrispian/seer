# Acceptance Scenarios

## 1) No tools path
- Router returns needs_tools=false.
- FinalComposer replies from context only.

## 2) Calendar list path
- Input: “What’s on my calendar next week?”
- Router → true; Candidates select calendar.listEvents; ToolRunner executes; Summary returns items.

## 3) JSON parse failure → retry
- Router first response invalid JSON → client retries once with “Respond valid JSON only.” → succeeds.

## 4) Permission-blocked tool
- Candidates propose gmail.send but allow-list blocks; system responds asking for permission or offers read-only alternative.
