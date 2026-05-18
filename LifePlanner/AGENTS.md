# AGENTS.md - LifePlanner SIM v1.0

> **TALL Stack**: Tailwind CSS v3.4+, Alpine.js v3, Laravel 11.x, Livewire v3
> **Single-user, private** Personal Information Management System (Finance, Productivity, Health)

---

## Project Overview

LifePlanner SIM replaces Excel-based personal tracking with a secure, responsive web platform integrating Finance, Productivity, and Health/Habits. Single-user (private), no multi-user, no external APIs, no email/Telegram notifications in v1.0.

**Source of Truth**: `SourceOfTruth.md` → `LP-US-AC-2026-001` (behavior) → `LP-DB-SCHEMA-2026-001` (data) → Design System (UI)

---

## Commands

### Setup
```bash
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec node npm install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

### Development
```bash
# Start (Terminal 1)
docker-compose up -d app nginx mysql redis scheduler

# Vite HMR (Terminal 2)
docker-compose up node

# Run artisan
docker-compose exec app php artisan <command>

# View logs
docker-compose logs -f app nginx
```

### Testing & Verification
```bash
# Run tests
docker-compose exec app php artisan test

# Lint & typecheck
docker-compose exec app php artisan lint

# Clear caches
docker-compose exec app php artisan cache:clear config:clear route:clear view:clear
```

---

## Code Conventions

### PHP/Laravel
- PSR-12, strict types, type-hinted properties/methods
- `snake_case` tables/columns, `BIGINT UNSIGNED` IDs, `DECIMAL(15,2)` money
- Never expose stack traces in production. Use `log()` + user-friendly messages

### Blade/Livewire
- `kebab-case` component names & routes
- Server-side validation via `$rules` always
- Use `$this->dispatch()` for cross-component events
- Target `< 500ms` Livewire response time

### Frontend
- Tailwind utility classes only. Avoid custom CSS unless necessary
- Design tokens: `ink (#0D0D0F)`, `paper (#F5F2ED)`, `forest (#2D5016)`, `gold (#C9962A)`, `violet (#3D2B8A)`, `blush (#C45C6A)`
- Fonts: `DM Serif Display` (headings), `Plus Jakarta Sans` (body), `DM Mono` (code)

---

## Critical Rules

1. **Always** reference `SourceOfTruth.md` hierarchy when generating code
2. **Always** validate against Acceptance Criteria before outputting features
3. **Never** skip auth middleware or server-side validation
4. **Never** trust client-side only validation
5. **Always** use `with()` or `load()` to avoid N+1 queries
6. **Never** commit `.env` files or secrets
7. **Always** test responsive at `375px` (mobile) & `1280px` (desktop)
8. **Always** add `// @see LP-US-AC-2026-001` comments to map code to requirements

---

## Security

- Laravel Sanctum (session-based), 8-hour timeout
- CSRF/XSS: Laravel defaults active, escape all `{{ }}` outputs
- Passwords: `bcrypt(cost≥12)`
- Run containers as non-root user
- Use environment variables for sensitive data (never hardcode)

---

## Definition of Done

- [ ] All Acceptance Criteria passed
- [ ] Tests pass (`docker-compose exec app php artisan test`)
- [ ] Responsive at `375px` & `1280px`
- [ ] Zero console errors
- [ ] Livewire response `< 500ms`
- [ ] No `.env` or secrets in commits
- [ ] CSRF active, XSS prevented
