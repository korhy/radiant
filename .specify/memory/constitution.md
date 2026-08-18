# Radiant Constitution

**Version**: 1.0.0 · **Ratified**: 2026-08-18 · **Last amended**: 2026-08-18

## The constitution is not recreated here

Radiant's governing rules already exist, in the form Claude Code loads automatically:

- **[`CLAUDE.md`](../../CLAUDE.md)** — stack, global rules, commands.
- **[`.claude/rules/business/radiant.md`](../../.claude/rules/business/radiant.md)** — domain model,
  the Stream Deck contract, the Cookbook client, roles, repo conventions.
- **[`.claude/rules/technical/*.md`](../../.claude/rules/technical/)** — twelve path-scoped technical
  rules (backend, Symfony best practices, naming, security, linting, testing, EasyAdmin, Twig,
  styling, components, Stimulus/Turbo, deployment).

**Those files are the constitution.** This document exists so the `/speckit-*` workflow has the
anchor it expects; it must not restate or fork them. When a principle below and a rule file
disagree, **the rule file wins** — and the disagreement is a bug to fix in one place, not to
arbitrate per feature.

## Non-negotiables

Every spec, plan and task set is checked against these. They are the short form; the detail lives in
the rules.

1. **Strict typing.** `declare(strict_types=1)` in every PHP file; typed params, returns and
   properties. PHP **8.2** is the ceiling of what CI can compile.
2. **Security through Symfony, never around it.** `#[IsGranted]`, `access_control`, voters — never
   raw role-string comparisons. Only `^/admin` is gated today, so every new route's exposure is an
   explicit decision. No secrets in code; never log the Cookbook credentials or its cached JWT.
3. **Layering.** No business logic in controllers, no Doctrine queries outside repositories, no
   queries in templates.
4. **English identifiers, French UI.** Code, routes and admin labels in English; visitor-facing text
   in French, written inline in the templates (there is no i18n catalogue, and adding one is its own
   decision).
5. **Migrations are unattended and backward-safe.** They execute in production automatically on
   every green `main` deploy. Never interactive, never destructive in the same release as the code
   change that makes the column unused.
6. **Conventional Commits.** `release.yml` derives the semver tag from the commit subject — the
   message is machinery, not decoration.
7. **Accessibility is an acceptance criterion.** RGAA/EAA → WCAG 2.1 AA. A screen that is not
   keyboard-operable, or that conveys meaning by colour alone, is not done.
8. **A change is done when its gate passes**: php-cs-fixer and PHPStan, the same two CI runs. Twig,
   JS and CSS have **no** automated gate — they are reviewed by hand and verified by loading the
   page.

## Two standing constraints on planning

- **Tailwind 3 → 4 gates shadcn.** The Symfony UX Toolkit shadcn kit is Tailwind 4-native. Any plan
  proposing shadcn components must either sequence the migration first or drop the idea. See
  `.claude/rules/technical/components-shadcn.md`.
- **`tests/` is empty, so CI is green vacuously.** Never treat a passing pipeline as evidence of
  behaviour. When a plan touches risky logic, the plan says which test proves it.

## Governance

Amendments change the rule files first; this document is updated only when a *non-negotiable*
changes. Version bumps follow semver: MAJOR for removing or redefining a non-negotiable, MINOR for
adding one, PATCH for wording.
