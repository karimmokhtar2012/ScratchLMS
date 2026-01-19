# ScratchLMS - AI Guidelines

> **Welcome!** This file points you to the essential documentation for this project.

---

## 📚 Start Here

| Document | Purpose |
|----------|---------|
| **[AGENTS.md](./AGENTS.md)** | Main guidelines - coding standards, commands, security rules |
| **[docs/ai/context.md](./docs/ai/context.md)** | Project architecture, key flows, database patterns |
| **[docs/developer-guide.md](./docs/developer-guide.md)** | Complete development standards |

---

## 🎨 Design & UI

| Document | Purpose |
|----------|---------|
| **[docs/design_context.md](./docs/design_context.md)** | Color palette, typography, component patterns |
| **[docs/security/xss-protection-guidelines.md](./docs/security/xss-protection-guidelines.md)** | XSS protection in views |

---

## 📝 Documentation Workflow

| Document | Purpose |
|----------|---------|
| **[docs/ai/change-log.md](./docs/ai/change-log.md)** | Log changes after each task |
| **[docs/workflow.md](./docs/workflow.md)** | Git and development workflow |
| **[docs/daily-report-template.md](./docs/daily-report-template.md)** | End-of-session report template |

---

## ⚡ Quick Reference

### Essential Commands

```bash
php -S localhost:8000 -t public   # Dev server
php migrate.php                    # Run migrations
git push origin main               # Push changes
```

### Critical Rules

1. **XSS:** Use `e($variable)` in all views
2. **PostgreSQL:** Use `true`/`false` (not `1`/`0`) for booleans
3. **CSRF:** Include token in all forms
4. **Validation:** Validate inputs before database operations

---

*Read `AGENTS.md` first, then reference other docs as needed.*
