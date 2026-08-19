---
description: Testing strategy — PHPUnit, critical paths only, no coverage target. Assert HTTP status, never dialect-specific SQL: CI runs SQLite, production runs PostgreSQL.
paths:
  - "**/tests/**"
  - "**/*Test.php"
  - "**/phpunit.xml*"
  - "**/.env.test"
---

# Testing

## Current state

**37 tests depuis le 2026-08-19** — auparavant `tests/` ne contenait que `bootstrap.php` et la CI
passait à vide. Ce qui existe :

- `tests/Service/Motus/MotusServiceTest.php` — `checkGuess()`, lettres doublées comprises ;
- `tests/Service/Cookbook/CookbookApiServiceTest.php` — retry 401 via `MockHttpClient`, et un
  garde-fou vérifiant qu'aucun identifiant ni JWT n'atterrit dans les logs ;
- `tests/Smoke/PublicRoutesTest.php` — routes publiques, contrat `_icon_<slug>`, endpoints JSON ;
- `tests/Smoke/AccessibilityTest.php` — invariants d'accessibilité (clavier, `alt`, labels, erreurs
  de formulaire).

`.env.test` fixe `DATABASE_URL` (SQLite) et `MAILER_DSN` : **`.env.local` n'est pas chargé en env
`test`**, sans quoi les tests viseraient la base de dev et le vrai mailer.

What *is* in place:
- **PHPUnit 9.5** (`phpunit.xml.dist` uses the legacy PHPUnit 9 schema — `<listeners>`,
  `processUncoveredFiles`; don't copy PHPUnit 10/11 syntax into it).
- `symfony/browser-kit` + `symfony/css-selector` are installed, so **`WebTestCase` works today** —
  a functional test needs no new dependency.
- `.env.test` mentions Panther variables, but **`symfony/panther` is not installed**. Ignore them.

## Stance — opt-in, critical paths only

Tests are **not** required for every change. Write one when the code carries real risk:

1. **Authorization** — a route that must stay admin-only.
2. **Business logic with rules** — `MotusService::checkGuess()` (the two-pass correct/present/absent
   algorithm) is exactly the kind of pure, branch-heavy function a unit test pays for.
3. **Data integrity** — anything that writes, or a migration with a non-trivial transformation.
4. **Contract with an external service** — `CookbookApiService`'s 401-retry and Hydra parsing, tested
   against a mocked `HttpClient`, never against the live API.

Cosmetic template changes and one-line copy edits don't need a test. Say so rather than writing a
hollow one.

## ⚠️ CI tests SQLite; production runs PostgreSQL

`ci.yml` sets `DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"` while the app runs on
**PostgreSQL 16**. Any test touching Postgres-specific SQL, JSON operators, sequences or identity
columns will either pass misleadingly or fail for the wrong reason. Prefer:

- **unit tests** with no database for service logic (the highest-value coverage here anyway);
- for functional tests, stick to behaviour the ORM abstracts, and be suspicious of anything that
  depends on the dialect.

Aligning CI onto Postgres is a worthwhile change — propose it separately.

## Layout & naming
- `tests/` mirrors `src/`: `tests/Service/Motus/MotusServiceTest.php` for
  `src/Service/Motus/MotusService.php`.
- Class `XxxTest` extending `TestCase` (unit) or `WebTestCase` (functional); methods
  `testItDoesSomething()`.
- Namespace `App\Tests\` (already mapped in `composer.json` autoload-dev).

## Running

```bash
make phpunit
make phpunit TEST=tests/Service/Motus/MotusServiceTest.php
```

## Browser checks
**Playwright is not installed** and there is no JS test runner. For exploratory UI verification —
does the Stream Deck render, does the Motus grid colour correctly, does the Cookbook infinite scroll
fire — use the `playwright-skill`, which writes throwaway scripts outside the repo. Those are checks,
not committed tests; don't leave artefacts behind.

## See also
- Linting gate: [linting.md](linting.md)
- Deployment & migration safety: [deployment.md](deployment.md)
