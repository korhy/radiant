---
description: Linting/formatting gate — run the matching linter before considering any change done. No Makefile; everything runs through Docker.
paths:
  - "**/*.php"
  - "**/*.js"
  - "**/*.css"
  - "**/*.twig"
  - "**/*.yml"
  - "**/*.yaml"
---

# Linting & formatting (pre-CI gate)

> Companion to [testing.md](testing.md). **A change is not "done" until its linter passes.** Run the
> matching linter locally on the files you touched and make sure it exits with **0 errors** before
> handing off or committing.

**There is no Makefile in this project.** Every command runs inside the app container.

## Rule — run the linter that covers what you touched

| You touched… | Check | Autofix |
|---|---|---|
| any `.php` | `docker compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff` | `docker compose exec app vendor/bin/php-cs-fixer fix` |
| `src/`, `config/`, `bin/`, `public/`, `tests/` | `docker compose exec app php -d memory_limit=-1 vendor/bin/phpstan analyse` | — (fix the code) |
| before a commit | both of the above + `docker compose exec app php bin/phpunit` | — |

These three are **exactly** what `.github/workflows/ci.yml` runs. If they pass locally, CI passes.

> **Why `-d memory_limit=-1` on PHPStan.** The container's PHP is capped at **128M** and PHPStan's
> parallel workers exhaust it, failing with `Child process error (exit code 255): Allowed memory size
> exhausted` — a false alarm that says nothing about your code. CI doesn't hit this (the GitHub
> runner has no such cap), so the workflow file omits the flag. Locally, always pass it.

- **PHP style** is `@Symfony` via **PHP-CS-Fixer** (`.php-cs-fixer.dist.php`). The finder excludes
  `var/`, `migrations/`, `config/bundles.php` and `config/reference.php`.
- **Static analysis** is **PHPStan level 5** (`phpstan.dist.neon`) over `bin/ config/ public/ src/ tests/`.

Autofix the mechanical issues instead of hand-editing whitespace.

## ⚠️ Known gap — Twig, JS and CSS have no automated gate

There is **no twig-cs-fixer, no ESLint, no Prettier, no stylelint** in this project, and
`package.json` declares no lint script. Nothing checks `templates/**`, `assets/**/*.js` or
`assets/styles/*.css` — CI will happily go green on malformed Twig or sloppy JS.

Consequences for how you work:
- Changes to Twig/JS/CSS need **deliberate manual review**; you cannot lean on a linter.
- Match the surrounding file's style by hand (the Stimulus controllers use ES private fields and are
  inconsistent about semicolons — follow the file you're in, don't reformat it wholesale).
- Verify front-end changes by **loading the page**, not by trusting the build.

Adding these tools is worthwhile but is a change of its own — propose it, don't smuggle it into a
feature branch.

## Notes
- **Config lives in code — don't loosen it casually.** Disabling a rule to make the linter green is a
  last resort and must be justified — prefer fixing the code.
- Every linter/plugin a config imports must be a **declared** dependency in `composer.json` /
  `package.json`, not a transitive one — a clean `composer install` / `npm ci` only resolves declared
  packages.
- `config/reference.php` is auto-generated and excluded from php-cs-fixer, PHPStan **and** git.
- Keep formatting changes out of feature diffs where possible: run the autofixer, review, commit
  style separately if it's noisy.
