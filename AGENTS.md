# Repository Guidelines

## Project Structure & Module Organization
- `app/`: Core PHP application code (controllers, services, core helpers).
- `routes/`: Route definitions (`routes/web.php`).
- `resources/views/`: PHP view templates.
- `public/`: Web root, assets, and entry point.
- `database/`: Migrations and one-off fix scripts.
- `tests/`: PHPUnit tests.
- `docs/`: Architecture notes and workflow docs.

## Build, Test, and Development Commands
- `composer install`: Install PHP dependencies.
- `php setup_db.php`: Initialize SQLite database.
- `php -S localhost:8000 -t public`: Run the app locally.
- `php vendor/bin/phpunit`: Run tests (if configured).

## Coding Style & Naming Conventions
- Indentation: 4 spaces in PHP; keep consistent in views.
- Classes: `StudlyCaps` (e.g., `CourseProgressService`).
- Methods/variables: `camelCase`.
- Files: match class names when applicable.
- Favor explicit guards and early returns for security checks.

## Testing Guidelines
- Framework: PHPUnit (see `tests/`).
- Name tests descriptively (e.g., `CourseProgressTest`).
- Run the full suite with `php vendor/bin/phpunit` before pushing.

## Commit & Pull Request Guidelines
- Commit messages: short, imperative, and specific (e.g., "Add README", "Fix CSRF header").
- PRs should include: summary, testing notes, and screenshots for UI changes.
- Link relevant GitHub issues (see project board).

## Security & Configuration Tips
- CSRF is enforced on all POST requests; ensure JS requests include `X-CSRF-TOKEN`.
- SQLite foreign keys matter; use migration/fix scripts in `database/` when needed.

## Agent-Specific Instructions
- Codex role: backend logic, security, data integrity, refactors, bug fixes, and reviews.
- Task routing:
  - Bugs, broken flows, data validation, security: Codex.
  - UI screens, layout, animations: Gemini.
  - Mixed tasks: Gemini designs first, Codex wires logic and validates.
- Handoff to Gemini: summary of behavior changes and any UI points needing polish.
- Change log: append to `docs/ai/change-log.md` after completing changes (timestamp, goal, files, rationale, security, DB notes, tests, follow-ups).
- Update `docs/ai/context.md` when flows/paths/conventions change.
- ADRs: add/update in `docs/adr/` for architectural decisions.
- End-of-session: submit daily report in `docs/reports/daily/` using `docs/daily-report-template.md` and push changes to GitHub.
- See `docs/workflow.md` and `GEMINI.md` for the full collaboration rules.
