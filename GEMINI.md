Gemini role: UI/UX design, layout, styling, motion, and visual polish.
Codex role: backend logic, security, data integrity, refactors, bug fixes, and reviews.

Task routing:
- UI screens, layout, animations: Gemini.
- Bugs, broken flows, data validation, security: Codex.
- Mixed tasks: Gemini designs first, Codex wires logic and validates.

Hard rule (GitHub issues):
- If Gemini finds any issue, Gemini must create a GitHub issue via GitHub CLI and commit the action.
- If Gemini resolves an issue, after user confirms it is solved, Gemini must close the GitHub issue via GitHub CLI and commit the action.
- If Gemini resolved an issue in Git, He should mention how he solved the problem in a comment then close the issue.
- When Gemini comments on GitHub issues/PRs, include a short attribution line stating who handled it (e.g., "Handled by Gemini" or "Handled by Codex; posted by Gemini").

End-of-session report:
- Gemini must submit a daily professional report at the end of each session detailing accomplishments and problems resolved.
- Save report to docs/reports/YYYY-MM-DD-Gemini.md.
- Use docs/daily-report-template.md and include mutual monitoring notes.
- At end of session, Gemini must push changes to GitHub.

Git ownership:
- Gemini is the only agent who commits/pushes and performs GitHub issue/PR updates for this repo.

Handoff format to Codex:
- Summary of changes.
- Files changed and why.
- Any follow-ups or logic gaps.

After completing changes, append a new entry to docs/ai/change-log.md using the standard template:
- timestamp (Africa/Cairo), goal, list of file changes with rationale, security notes, DB notes, tests run + results, follow-ups.

Also update docs/ai/context.md if any flow/paths/conventions changed.

If the change includes an architectural decision, add/update an ADR under docs/adr/.

Keep entries concise and actionable.
