---
description: Deployment, CI and release mechanics — FrankenPHP, GHCR image build, auto-running migrations, Conventional-Commit-driven versioning.
paths:
  - "**/migrations/**"
  - "**/.github/workflows/**"
  - "**/docker/**"
  - "**/compose*.yaml"
  - "**/.platform*"
---

# Deployment, CI & releases

Three workflows in `.github/workflows/`: **CI** (`ci.yml`, which also builds and pushes the
production image), **Deploy** (`deploy.yml`, SSH to the OVH dedicated server — the live production
path; see the note at the bottom), and **Release** (`release.yml`).

## CI (`ci.yml`)
Runs on push/PR to `main`: php-cs-fixer `--dry-run`, twig-cs-fixer, PHPStan, PHPUnit (job `ci`), and
the Playwright accessibility suite (job `e2e`). See [linting.md](linting.md) for running the same
checks locally.

### `build-images` — the production image (GHCR)

On push to `main`, **only once `ci` and `e2e` are both green**, a third job builds the FrankenPHP
production image from `docker/frankenphp/Dockerfile.prod` and pushes it to GHCR:

```
ghcr.io/korhy/radiant/frankenphp:latest
ghcr.io/korhy/radiant/frankenphp:<commit-sha>
```

A broken commit on `main` therefore never produces a new `latest`. **Coolify pulls this image** —
it does not build from the Dockerfile itself. See
[docs/deploy/coolify.md](../../docs/deploy/coolify.md).

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

## Docker — FrankenPHP (PHP 8.3 + Caddy)

Everything lives under `docker/frankenphp/`, no nginx/php-fpm/Supervisor: FrankenPHP bundles Caddy
and PHP in one process.

| File | Role |
|---|---|
| `Dockerfile` | Dev image, used by `compose.yaml`. Bootstraps on boot via `dev-entrypoint.sh`: installs Composer deps if missing, waits for Postgres, migrates a fresh DB only (`make db-migrate` otherwise). |
| `Dockerfile.prod` | Production image: Composer → Webpack Encore assets → FrankenPHP runtime, multi-stage. Built by CI, not by Coolify. |
| `Caddyfile` / `Caddyfile.prod` | Caddy config, dev (`:80`) and prod (`:8080`, `/healthz`). |
| `entrypoint.prod.sh` | Boot sequence for the prod image (below). |

`compose.yaml` runs the dev image on host port **8080** with PostgreSQL 16; `compose.override.yaml`
adds Mailpit (8025) and Blackfire. WORKDIR is **`/app`** (not `/var/www/html`).

### Production boot sequence (`entrypoint.prod.sh`)

Runs on **every** container start, before binding `:8080`:

1. Fix permissions on `var/` and the two Vich upload directories.
2. `cache:clear` + `cache:warmup` (env `prod`).
3. `ux:icons:warm-cache` — pre-fetches icons so they aren't fetched from Iconify on first request
   (non-fatal if it fails).
4. `doctrine:database:create --if-not-exists`, retried up to 30 times (2s apart) — doubles as the
   wait for the DB to accept connections.
5. `doctrine:migrations:migrate --no-interaction --allow-no-migration` (idempotent).

**Consequences you must design for:**

1. **Every migration executes unattended, right after the merge, on every container (re)start.**
   It must be **backward-safe** and **non-interactive**. A destructive `DROP COLUMN` in the same
   release as the code that stops using it will break requests in flight. Split it: ship the code
   first, drop the column in a later release.
2. **Assets are built as part of the image** (`Dockerfile.prod`'s Node stage, run in CI). A
   front-end change that isn't rebuilt into the image simply won't ship. Never commit
   `public/build/`.
3. **Uploaded files must be on a persistent volume** — `public/images/personal_projects/` (Vich)
   and `public/documents/CV/`. They are not in git and not in the image; a container is rebuilt on
   every deploy, so without a mounted volume a redeploy would wipe them. Never write code that
   assumes a clean rebuild, and never add a deploy step that touches those directories directly.

## Where the production image is not yet deployed
- Nothing pulls `ghcr.io/korhy/radiant/frankenphp` into production. **`deploy.yml`** (SSH, OVH
  dedicated server, no Docker) is the live deploy path — see
  [docs/deploy/coolify.md](../../docs/deploy/coolify.md) for the Coolify configuration to activate.
- **`.platform.app.yaml` / `.platform/`** (Platform.sh) are legacy and unused.

Touching either without a decision is wasted work; say so rather than "fixing" them silently.
