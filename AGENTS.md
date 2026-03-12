# AI Agent Guidelines

> **Purpose:** Unified guidelines for AI agents working on ScratchLMS  
> **Last Updated:** 2026-01-19

---

## 1. Project Overview

ScratchLMS is a learning management system built with PHP, supporting SQLite, MySQL, and PostgreSQL.

| Component | Technology |
|-----------|------------|
| Backend | PHP 8.1+ |
| Frontend | Tailwind CSS, Alpine.js |
| Database | SQLite / MySQL / PostgreSQL |
| Icons | FontAwesome 6 |

---

## 2. Directory Structure

```
LMS/
├── app/
│   ├── Core/              # Framework core (Router, Database, Csrf)
│   ├── Domain/            # Domain models
│   ├── Http/Controllers/  # Request handlers
│   ├── Services/          # Business logic
│   └── helpers.php        # Global helpers
├── config/                # Configuration
├── database/migrations/   # Schema migrations
├── public/                # Web root
├── resources/views/       # View templates
├── routes/web.php         # Route definitions
└── docs/                  # Documentation
    ├── ai/                # Context and logs
    ├── adr/               # Architectural decisions
    └── security/          # Security guidelines
```

---

## 3. Essential Commands

```bash
# Development
php -S localhost:8000 -t public   # Run dev server
php migrate.php                    # Run migrations

# Git
git add . && git commit -m "..."   # Commit changes
git push origin main               # Push to remote

# GitHub CLI
gh issue create --title "..."      # Create issue
gh issue close <number>            # Close issue
```

---

## 4. Coding Standards

### Style

- **Indentation:** 4 spaces (PHP), 2 spaces (JS)
- **Classes:** `StudlyCaps` (e.g., `CourseProgressService`)
- **Methods/Variables:** `camelCase`

### Security Rules (MANDATORY)

| Rule | How |
|------|-----|
| XSS Protection | Use `e($variable)` in all views |
| CSRF | Include `<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">` |
| SQL Injection | Use parameterized queries only |
| File Uploads | Validate with `validateFileUpload()` |
| Redirects | Use `sanitizeRedirect()` |

### Input Validation

Always validate before database operations:

```php
$errors = [];
if (empty(trim($title))) {
    $errors[] = 'Title is required.';
}
if (!in_array($type, $validTypes, true)) {
    $errors[] = 'Invalid type.';
}
if (!empty($errors)) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    header('Location: /back');
    exit;
}
```

---

## 5. PostgreSQL Compatibility

**Critical:** This project supports multiple databases. Follow these rules:

| Pattern | ❌ Wrong | ✅ Correct |
|---------|---------|-----------|
| Boolean comparison | `= 1` / `= 0` | `= true` / `= false` |
| Boolean binding | `[':active' => true]` | Inline: `SET active = true` |
| Aggregation | `GROUP_CONCAT()` | Separate queries |
| Upsert | `INSERT OR IGNORE` | `$db->insertOrIgnore()` |
| Date functions | `date('now', '-7 days')` | Calculate in PHP |

---

## 6. UI Design Standards

### Color Palette

| Purpose | Value/Class |
|---------|-------------|
| Background | `#0a0a0a` |
| Surface | `bg-zinc-900` |
| Card | `bg-zinc-800` |
| Primary | `from-indigo-500 to-violet-600` |
| Text | `text-zinc-200` |
| Muted | `text-zinc-400` |

### Components

- Buttons: `bg-gradient-to-r from-indigo-500 to-violet-600 rounded-xl`
- Cards: `bg-zinc-900 border border-zinc-800 rounded-2xl`
- Inputs: `bg-zinc-900 border border-zinc-700 rounded-xl`

---

## 7. Documentation Workflow

### After Making Changes

1. **Change Log** (`docs/ai/change-log.md`)
   - Timestamp (Africa/Cairo), goal, files changed, tests run

2. **Context** (`docs/ai/context.md`)
   - Update if flows or conventions changed

3. **ADR** (`docs/adr/`)
   - Create for architectural decisions

### End of Session

- Create report: `docs/reports/YYYY-MM-DD.md`
- Push all changes to GitHub

---

## 8. Key Files Reference

| File | Purpose |
|------|---------|
| `docs/developer-guide.md` | Full development standards |
| `docs/design_context.md` | UI design system |
| `docs/ai/context.md` | Project architecture & flows |
| `docs/ai/change-log.md` | Change history |
| `docs/security/xss-protection-guidelines.md` | XSS rules |

---

## 9. Quick Checklist

### Before Committing

- [ ] User output escaped with `e()` in views
- [ ] Forms include CSRF token
- [ ] SQL uses parameterized queries
- [ ] Booleans use `true`/`false` literals (not `1`/`0`)
- [ ] File uploads validated

### After Session

- [ ] Change log updated
- [ ] Changes pushed to GitHub

---

*Keep work focused and actionable. Reference `docs/developer-guide.md` for complete standards.*
