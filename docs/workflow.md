# UI + Engineering Workflow (Codex + Gemini)

## Roles
- Gemini: UI/UX design, layout, styling, motion, and visual polish.
- Codex: backend logic, security, data integrity, refactors, bug fixes, and reviews.

## Task Routing
- UI screens, layout, animations: assign to Gemini.
- Bugs, broken flows, data validation, security: assign to Codex.
- Mixed tasks: Gemini designs first, Codex wires logic and validates.

## GitHub Workflow
1) Create a GitHub issue for each task.
2) Add labels:
   - UI tasks: `ui/ux`.
   - Logic tasks: `backend`, `bug`, `refactor`.
3) One branch per issue:
   - `feat/<short-name>` or `fix/<short-name>`.
4) Open a PR for each task and link the issue.

## Handoff Format
When Gemini finishes a task, send Codex:
- Summary of changes.
- Files changed and why.
- Any follow-ups or logic gaps.

When Codex finishes a task, send Gemini:
- Summary of behavior changes.
- Any UI points that need polish.

## Definition of Done
- UI tasks: layout, spacing, responsiveness, and transitions verified.
- Backend tasks: inputs validated, errors handled, and no regressions.
- GitHub: issue closed, PR merged, milestone updated.

