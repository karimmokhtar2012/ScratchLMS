# ADR 0001: Centralized Authorization Layer

## Status
Accepted

## Context
Authorization logic (checking if a student is enrolled or if an instructor owns a course) was previously scattered across controllers using manual DB queries. This led to inconsistent enforcement and made the code hard to maintain.

## Decision
We have introduced a centralized `App\Core\Authorization` class. Controllers and Services should use this class to enforce permissions.

## Consequences
- **Pros**: Consistent security enforcement, cleaner controller methods, single point of change for auth rules.
- **Cons**: Small increase in method call overhead.
