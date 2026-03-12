# Development Workflow

> **Last Updated:** 2026-01-19

---

## Task Flow

1. **Understand** the request fully
2. **Research** existing code patterns
3. **Implement** following project standards
4. **Validate** with browser testing when applicable
5. **Document** changes in change log

---

## GitHub Workflow

### Branch Naming

```
feat/<short-name>   # New features
fix/<short-name>    # Bug fixes
refactor/<name>     # Code refactoring
docs/<name>         # Documentation
```

### Commit Messages

- Short, imperative, specific
- Examples: `Add README`, `Fix CSRF header`, `Update quiz validation`

### Issue Management

1. Create issues for discovered bugs
2. Link issues in commits/PRs
3. Close issues with explanatory comment after resolution

---

## Documentation Updates

| When | Update |
|------|--------|
| After any code change | `docs/ai/change-log.md` |
| When flows change | `docs/ai/context.md` |
| Architectural decisions | `docs/adr/` |
| End of session | `docs/reports/YYYY-MM-DD.md` |

---

## Definition of Done

- [ ] Feature working as expected
- [ ] Inputs validated
- [ ] Errors handled gracefully
- [ ] Security rules followed
- [ ] Browser tested (if UI change)
- [ ] Documentation updated
- [ ] Changes committed and pushed

---

*Reference `AGENTS.md` for complete project guidelines.*
