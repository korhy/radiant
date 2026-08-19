---
description: Linting/formatting gate — run the matching linter before considering any change done. php-cs-fixer, twig-cs-fixer and PHPStan are gated; JS/CSS are not.
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

Commands come from the **`Makefile`** (`make help` for the list); the PHP targets run inside the app
container for you, so bring the stack up with `make up` first.

## Rule — run the linter that covers what you touched

| You touched… | Check | Autofix |
|---|---|---|
| any `.php` | `make php-cs-fixer` | `make php-cs-fixer-fix` |
| `templates/**` `.twig` | `make twig-cs-fixer` | `make twig-cs-fixer-fix` |
| `src/`, `config/`, `bin/`, `public/`, `tests/` | `make phpstan` | — (fix the code) |
| all three at once | `make lint` | — |
| before a commit | `make ci` | — |

**`make ci` runs exactly what `.github/workflows/ci.yml` runs** — php-cs-fixer, twig-cs-fixer,
PHPStan, PHPUnit. If it passes locally, CI passes.

> **Why `make phpstan` isn't just `vendor/bin/phpstan analyse`.** The container's PHP is capped at
> **128M** and PHPStan's parallel workers exhaust it, failing with `Child process error (exit code
> 255): Allowed memory size exhausted` — a false alarm that says nothing about your code. The target
> passes `-d memory_limit=-1` for you. CI doesn't hit this (the GitHub runner has no such cap), so
> the workflow file omits the flag. Run the target, not the raw binary.

- **PHP style** is `@Symfony` via **PHP-CS-Fixer** (`.php-cs-fixer.dist.php`). The finder excludes
  `var/`, `migrations/`, `config/bundles.php` and `config/reference.php`.
- **Twig style** is **twig-cs-fixer** (`.twig-cs-fixer.php`) — the standard ruleset over
  `templates/`. The templates are indented with **4 spaces**, already the `Indent` rule's default, so
  the ruleset overrides nothing. Keep it that way: an override exists to match a convention, and this
  project's convention is the default.
- **Static analysis** is **PHPStan level 5** (`phpstan.dist.neon`) over `bin/ config/ public/ src/ tests/`.

Autofix the mechanical issues instead of hand-editing whitespace.

## ⚠️ Remaining gap — JS and CSS have no automated gate

Twig is covered since twig-cs-fixer landed, but there is still **no ESLint, no Prettier, no
stylelint**, and `package.json` declares no lint script. Nothing checks `assets/**/*.js` or
`assets/styles/*.css` — CI will happily go green on sloppy JS.

Consequences for how you work:
- Changes to JS/CSS need **deliberate manual review**; you cannot lean on a linter.
- Match the surrounding file's style by hand (the Stimulus controllers use ES private fields and are
  inconsistent about semicolons — follow the file you're in, don't reformat it wholesale).
- Verify front-end changes by **loading the page**, not by trusting the build.

Closing this gap means adding **ESLint** + **Prettier** configs, `lint`/`format` npm scripts, and a
`javascript` job in `ci.yml`. Worthwhile, but a change of its own — propose it, don't smuggle it into
a feature branch.

## Notes
- **Config lives in code — don't loosen it casually.** Disabling a rule to make the linter green is a
  last resort and must be justified — prefer fixing the code.
- Every linter/plugin a config imports must be a **declared** dependency in `composer.json` /
  `package.json`, not a transitive one — a clean `composer install` / `npm ci` only resolves declared
  packages.
- `config/reference.php` is auto-generated and excluded from php-cs-fixer, PHPStan **and** git.
- Keep formatting changes out of feature diffs where possible: run the autofixer, review, commit
  style separately if it's noisy.
