---
description: Testing strategy — PHPUnit for the code, Playwright + axe for accessibility. Critical paths only, no coverage target. Assert HTTP status, never dialect-specific SQL: CI runs SQLite, production runs PostgreSQL.
paths:
  - "**/tests/**"
  - "**/*Test.php"
  - "**/*.spec.js"
  - "**/phpunit.xml*"
  - "**/playwright.config.js"
  - "**/.env.test"
---

# Testing

## Current state

**52 tests PHPUnit et 16 cas Playwright au 2026-08-21** (37 le 2026-08-19) — auparavant `tests/` ne contenait que `bootstrap.php` et la CI
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
- Browser specs live in `tests/e2e/`, named `*.spec.js`, written in English like the rest of the
  code — they are read by developers, not by visitors.
- Class `XxxTest` extending `TestCase` (unit) or `WebTestCase` (functional); methods
  `testItDoesSomething()`.
- Namespace `App\Tests\` (already mapped in `composer.json` autoload-dev).

## Running

```bash
make phpunit
make phpunit TEST=tests/Service/Motus/MotusServiceTest.php
make e2e        # Playwright + axe, starts its own servers
```

## Browser checks — Playwright + axe

The **accessibility pass is automated and gated in CI**: `tests/e2e/accessibility.spec.js` audits the
seven public pages with axe-core, in both themes, plus the recipe list after an infinite-scroll load.

```bash
make e2e                       # or: npx playwright test
npx playwright test --ui       # pick and replay a single case
BASE_URL=http://localhost:8080 npx playwright test   # audit an already-running server
```

- **Gate WCAG 2.0/2.1 levels A and AA only** — the project's standard. axe's `best-practice` tag is
  left out on purpose: it reports structural advice ("the page should have an `h1`") that is worth
  acting on but is not a conformance failure.
- **The suite starts its own servers**: the site through `symfony server:start`, and a stub Cookbook
  API (`tests/e2e/cookbook-api-stub.php`). Without the stub the recipe page renders its degraded
  state and no card is ever audited — the obstacle that kept `/app/cookbook` out of the Étape 5 pass.
- **Its database is its own** (`var/e2e.db`), built and seeded by `tests/e2e/global-setup.js`. One
  Stream Deck tile is inserted so the homepage exercises the dynamic `_icon_<slug>` include.
- **Add a page to `PAGES`** when a route becomes public. A screen that is not covered here has not
  had its automated pass.

For anything else — exploratory UI verification, a one-off screenshot, reproducing a JS bug — use
the `playwright-skill`, which writes throwaway scripts outside the repo. Those are checks, not
committed tests; don't leave artefacts behind.

## See also
- Linting gate: [linting.md](linting.md)
- Deployment & migration safety: [deployment.md](deployment.md)
