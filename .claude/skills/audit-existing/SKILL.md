---
name: audit-existing
description: Audit the existing Radiant codebase against the project standards (strict typing, English naming and casing drift, dead code, security, accessibility, test coverage, stale docs) and produce a prioritized report of what should be redone vs kept. Read-only — proposes work, does not implement. Use when the user asks to audit the code, find what doesn't follow the standards, or "/audit-existing".
---

# /audit-existing — Standards audit of the existing code

Produce a prioritized, evidence-based report of where the current code diverges from this project's
standards, and what to redo vs keep. **This skill does not implement anything** — it inventories and
recommends; the work is then done by `/new-app`, `/speckit-specify`, or by hand.

## Constitution = the standards to audit against

`CLAUDE.md` + `.claude/rules/**`. The audit dimensions:

1. **Typing** ([backend-php.md](../../rules/technical/backend-php.md)) — files missing
   `declare(strict_types=1)`, untyped params/returns/properties, classes that should be `final`.
   Also: any syntax above **PHP 8.2**, which is what CI runs.
2. **Naming & casing** ([naming.md](../../rules/technical/naming.md)) — French/franglais identifiers,
   typos (`$projetcs`), and above all the **entity property casing drift**: `App::$techStack`
   (camelCase) vs `Experience::$start_date`, `PersonalProject::$file_name`, `$updated_at`
   (snake_case). Flag which renames would require a Doctrine migration (column moves) versus which
   are property-only.
3. **Dead code** — Stimulus controllers not registered in `assets/bootstrap.js`
   (`hello_controller.js`, `recipe_chat_controller.js`), the dormant AssetMapper setup
   (`importmap.php`, `assets/vendor/`, `config/packages/asset_mapper.yaml`), `.platform*`, root SQL
   dumps, `supervisord.log`/`.pid`. Say what is safe to delete and what is merely unused.
4. **Security** ([security.md](../../rules/technical/security.md)) — routes that mutate state without
   `#[IsGranted]`; remember **only `^/admin` is gated**, so check what else is reachable anonymously.
   Unvalidated request parameters on the JSON endpoints. Any place credentials or the cached Cookbook
   JWT could leak into logs, exceptions or templates. Unsanitized `|raw`.
5. **Backend conventions** — business logic or Doctrine queries in controllers; repositories bypassed;
   fat actions.
6. **Duplication** — repeated markup that should be a partial, repeated logic that should be a service.
7. **Frontend traps** ([frontend-styling.md](../../rules/technical/frontend-styling.md)) — templates
   named `*.twig` instead of `*.html.twig` (invisible to Tailwind's purge → unstyled page), and any
   JS-applied class that would be purged. Check the Motus block is still outside `@layer`.
8. **Accessibility** ([frontend-twig.md](../../rules/technical/frontend-twig.md)) — clickable
   non-semantic elements, missing labels/`alt`, icon-only controls with no accessible name, focus
   handling, AA contrast, and information conveyed by colour alone (**Motus** is the prime suspect).
   The mini-apps are the highest-risk surface.
9. **Tests** ([testing.md](../../rules/technical/testing.md)) — `tests/` is empty, so CI is green
   vacuously. Identify the highest-value first tests (route smoke tests, `MotusService::checkGuess()`,
   the `CookbookApiService` 401-retry) rather than reporting "0% coverage" as one finding.
10. **Stale documentation** — `README.md`'s routes table and feature list omit Cookbook, Motus,
    contact, legal and admin; its structure tree omits `src/Service/`, `src/DTO/`,
    `templates/components/`.

## Method (read-only)

1. **Inventory** the surface: `src/Controller` (including `Admin/`), `src/Entity`, `src/Service`,
   `src/Repository`, `src/Form`, `templates/**`, `assets/controllers/**`, `migrations/`,
   `.github/workflows/`.
2. For each dimension, grep/scan and record **file:line evidence** — never a vague claim. Useful
   signals:
   - `grep -rL 'declare(strict_types=1)' src --include='*.php'`
   - controllers containing `createQueryBuilder` or `getRepository`
   - `<div` carrying `data-action` or `onclick` and acting as a button
   - controller files in `assets/controllers/` whose name never appears in `assets/bootstrap.js`
   - `find templates -name '*.twig' ! -name '*.html.twig'`
3. **Classify** each finding: `redo` (rewrite to standard) / `keep` (fine as-is) / `investigate`
   (needs a decision), with an **effort** (S/M/L) and **impact** (user-facing / risk / cosmetic).
4. **Don't fix anything.** If a quick win is obvious, note it as a recommendation.

## Output — `docs/audit/audit-<date>.md`

Write a committed report (create `docs/audit/` if missing):

- **Summary**: counts per dimension, top risks, top quick wins.
- **Findings table**: `# | dimension | file:line | issue | classification | effort | next step`
  (→ `/new-app`, `/speckit-specify`, or manual). Most impactful first.
- **Suggested order**: unblockers first. Note explicitly that the **Tailwind 3 → 4 migration gates
  shadcn adoption**, so anything component-related queues behind it.
- **Explicitly "keep"** list — including the deliberate oddities (the Motus CSS block outside
  `@layer`, the `getJsonX()` accessors for EasyAdmin), so nobody "fixes" them later.

## Report

Summarize the biggest divergences, the recommended sequencing, and which skill or workflow handles
each cluster. Offer to start the highest-value slice — but only on request.
