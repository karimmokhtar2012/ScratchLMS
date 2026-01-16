Codex role: backend logic, security, data integrity, refactors, bug fixes, and reviews.

Task routing:
- Bugs, broken flows, data validation, security: Codex.
- UI screens, layout, animations: Gemini.
- Mixed tasks: Gemini designs first, Codex wires logic and validates.

Handoff format to Gemini:
- Summary of behavior changes.
- Any UI points that need polish.

After completing changes, append a new entry to docs/ai/change-log.md using the standard template:
- timestamp (Africa/Cairo), goal, list of file changes with rationale, security notes, DB notes, tests run + results, follow-ups.

Also update docs/ai/context.md if any flow/paths/conventions changed.

If the change includes an architectural decision, add/update an ADR under docs/adr/.

Keep entries concise and actionable.
