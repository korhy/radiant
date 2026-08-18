---
description: Deployment, CI and release mechanics — auto-running migrations, CI-built assets, Conventional-Commit-driven versioning.
paths:
  - "**/migrations/**"
  - "**/.github/workflows/**"
  - "**/Dockerfile"
  - "**/compose*.yaml"
  - "**/.platform*"
---

# Deployment, CI & releases

Three workflows in `.github/workflows/`, chained: **CI** → (on success, on `main`) **Deploy** and
**Release**.

## CI (`ci.yml`)
Runs on push/PR to `main`, PHP **8.2**: php-cs-fixer `--dry-run`, PHPStan, `php bin/phpunit`.
See [linting.md](linting.md) for running the same three locally.

## Deploy (`deploy.yml`) — migrations run themselves

On a green CI run on `main`: build assets with Node 20 (`npm ci && npm run build`), scp
`public/build/` to the server, then over SSH:

```
git pull origin main
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear && cache:warmup
```

**Consequences you must design for:**

1. **Every migration executes unattended in production, right after the merge.** It must be
   **backward-safe** — the old code is still serving traffic while it runs — and
   **non-interactive** (no `$this->abortIf()` that expects a human, no prompt). A destructive
   `DROP COLUMN` in the same release as the code that stops using it will break requests in flight.
   Split it: ship the code first, drop the column in a later release.
2. **Assets are built in CI, not on the server.** A front-end change that isn't in `public/build/`
   simply won't ship. Never commit `public/build/`.
3. **Uploaded files live on the server filesystem** — `public/images/personal_projects/` (Vich) and
   `public/documents/CV/`. They are not in git and not in the image. Never write code that assumes a
   clean rebuild, and never add a deploy step that wipes those directories.

## Release (`release.yml`) — commit subjects set the version

A bash step parses the **subject line** of the commits and pushes an annotated `vX.Y.Z` tag:

| Subject | Bump |
|---|---|
| `BREAKING CHANGE:` in the body, or `type!:` | **major** |
| `feat:` / `feat(scope):` | **minor** |
| `fix:` / `fix(scope):` | **patch** |
| anything else (`chore:`, `docs:`, `refactor:`…) | no release |

So **Conventional Commits are not a style preference here — they drive versioning.** A feature
committed as `chore:` silently ships untagged; a typo fix committed as `feat:` bumps the minor.
Write the subject deliberately.

## Docker

One multi-stage `Dockerfile`: `node-builder` → `php-base` → `production` / `development`. Nginx and
Supervisor configs are heredoc'd **inside** the Dockerfile — there is no `docker/` directory and no
entrypoint script. `compose.yaml` runs the `development` target on port **8080** with PostgreSQL 16;
`compose.override.yaml` adds Mailpit (8025) and Blackfire.

## Inactive paths — don't wire new work into them
- The Dockerfile's **`production` target** is not referenced by any workflow.
- **`.platform.app.yaml` / `.platform/`** (Platform.sh) are legacy and unused.

Touching either without a decision is wasted work; say so rather than "fixing" them silently.
