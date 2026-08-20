![CI](https://github.com/korhy/radiant/actions/workflows/ci.yml/badge.svg)
![Deploy](https://github.com/korhy/radiant/actions/workflows/deploy.yml/badge.svg)
![Version](https://img.shields.io/github/v/tag/korhy/radiant?label=version)

# Radiant — Personal Portfolio

A database-driven CV and a **Stream Deck** of self-contained mini-apps, built with Symfony 7.4 and
administered through EasyAdmin. Live at [clementboudinel.fr](https://clementboudinel.fr).

The site is a professional showcase, so the quality of the code and of the delivered experience —
**accessibility included** — is part of the deliverable, not an afterthought.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2, Symfony 7.4 |
| ORM | Doctrine ORM 3 / DBAL 3 + migrations, PostgreSQL 16 |
| Templating | Twig 3, Twig Components |
| UI | Tailwind CSS 4 (CSS-first, no JS config), Symfony UX Toolkit — shadcn kit |
| Interactivity | Stimulus (no Turbo, no Live Components) |
| Build | Webpack Encore, PostCSS |
| Admin | EasyAdmin 4, VichUploader for file uploads |
| Mail | Mailjet (Mailpit locally) |
| Quality | PHP-CS-Fixer, twig-cs-fixer, PHPStan level 5, PHPUnit — all gated in CI |
| Local | Docker Compose (app, PostgreSQL, Mailpit) |
| Production | OVH VPS, deployed by GitHub Actions over SSH |

## Features

- **Portfolio** — experiences and personal projects read from the database and rendered server-side.
- **Stream Deck** — the homepage grid linking to the mini-apps. Each tile resolves its icon partial
  from the app's slug at render time.
- **Taquin** — a 4×4 sliding puzzle, fully keyboard-playable.
- **Motus** — the word game, with server-side guess validation. Letter states are conveyed by colour
  **and** by non-chromatic relief, so the game is playable without colour perception.
- **Cookbook** — a recipe browser consuming an external API Platform application, with filtering,
  sorting and infinite scroll. Radiant is a **client** here: it stores no recipe of its own.
- **"Behind the scenes"** — a panel on every mini-app exposing its stack, its challenges, what's
  planned and its resources. Built on the kit's `Dialog` component.
- **Contact form** — validated through a DTO, delivered by Mailjet.
- **Light and dark themes** — driven by the visitor's system preference. Both meet WCAG 2.1 AA
  contrast; every pair is measured and recorded in
  [`specs/001-tailwind4-shadcn/light-theme.md`](specs/001-tailwind4-shadcn/light-theme.md).
- **Admin panel** — EasyAdmin back-office, the only way content is edited.

## Routes

| Route | Method | URL | Description |
|---|---|---|---|
| `homepage` | GET | `/` | Portfolio home and Stream Deck |
| `taquin` | GET | `/app/taquin` | Sliding puzzle |
| `motus` | GET | `/app/motus` | Word game |
| `motus_guess` | POST | `/app/motus/guess` | Guess validation (JSON) |
| `cookbook` | GET | `/app/cookbook` | Recipe browser |
| `cookbook_recipes_json` | GET | `/app/cookbook/recipes` | Paginated recipes (JSON) |
| `cookbook_recipe` | GET | `/app/cookbook/recipe/{id}` | Recipe detail |
| `contact` | GET/POST | `/contact` | Contact form |
| `legal` | GET | `/mentions-legales` | Legal notice |
| `app_login` | GET/POST | `/login` | Admin sign-in |
| `app_logout` | GET | `/logout` | Sign-out |
| `admin` | GET | `/admin` | EasyAdmin dashboard (`ROLE_ADMIN`) |

Only `^/admin` is gated. Every other route is public.

## Getting Started

### Prerequisites

- Docker & Docker Compose
- Node.js (assets build on the host — see below)
- Git

### Installation

```bash
git clone git@github.com:korhy/radiant.git
cd radiant

cp .env .env.local
```

> `.env` holds **non-secret defaults only** and is committed on purpose. Real values — database
> credentials, the Mailjet DSN, the Cookbook API credentials — go in `.env.local`, which is
> git-ignored. Never put a secret in `.env`.

```bash
# Start the stack (app :8080, PostgreSQL, Mailpit :8025)
make up

# PHP dependencies, inside the container
make install

# Database schema
make db-migrate

# JS dependencies and assets, on the HOST
npm install
npm run dev
```

The app is then available at [http://localhost:8080](http://localhost:8080), and Mailpit catches
outgoing mail at [http://localhost:8025](http://localhost:8025).

### Two traps worth knowing

> **Assets build on the host, not in the container.** An in-container `npm install` rewrites
> `package-lock.json`'s `name` field to the container workdir and produces a bogus lockfile diff.

> **The container has its own `vendor/`.** `compose.yaml` mounts an anonymous volume over
> `/var/www/html/vendor` for performance, so a `composer require` run inside the container does not
> reach the host — while `composer.json` and `config/bundles.php` are shared. Running the app with
> `symfony serve` after that fails on a missing class. Run `composer install` on the host to
> reconcile the two.

### Development

```bash
make up                  # start the stack
make watch               # rebuild assets on change
make console C="<cmd>"   # any Symfony console command
make sh                  # a shell in the app container
```

### Useful commands

`make help` lists them all. Targets that touch PHP run inside the container for you.

```bash
make cc                  # clear the Symfony cache
make db-migration        # generate a migration — review the SQL before applying it
make db-migrate          # apply pending migrations
make psql                # psql shell on the dev database
make lint                # php-cs-fixer + twig-cs-fixer + PHPStan
make phpunit             # the PHPUnit suite (TEST=path for a subset)
make ci                  # exactly what .github/workflows/ci.yml runs
```

## Quality gate

`make ci` runs the same four checks as CI, in the same order:

| Check | Scope |
|---|---|
| PHP-CS-Fixer | `@Symfony` ruleset over the PHP sources |
| twig-cs-fixer | the standard ruleset over `templates/` |
| PHPStan | level 5 over `bin/ config/ public/ src/ tests/` |
| PHPUnit | 37 tests — Motus, the Cookbook client, public routes, accessibility |

**JS and CSS have no automated gate yet**: review them by hand and load the page.

> CI runs **PHP 8.2** while the Docker image ships 8.3. Write 8.2-compatible code.
> Tests run on **SQLite** while production runs **PostgreSQL** — a test leaning on dialect-specific
> SQL is misleading.

## Deployment

Every green CI run on `main` triggers `deploy.yml`, which:

1. builds the assets on the runner (`npm ci && npm run build`) and uploads `public/build/` over SCP —
   the directory is git-ignored, so it never lives in the repository;
2. connects over SSH and runs `composer install --no-dev --optimize-autoloader`;
3. runs `doctrine:migrations:migrate --no-interaction`.

**Migrations therefore run themselves in production.** They must be backward-safe and
non-interactive. Deploy credentials live in GitHub Actions secrets, never in the workflow file.

`release.yml` parses the commit subject to auto-tag semver — `feat:` bumps the minor, `fix:` the
patch, `type!:` the major. Commit subjects are load-bearing; write them deliberately.

## Project Structure

```
├── assets/
│   ├── app.js                  # JS entry point
│   ├── bootstrap.js            # Stimulus controllers, registered BY HAND
│   ├── controllers/            # Stimulus controllers
│   └── styles/app.css          # Tailwind 4 entry + design tokens + the Motus block
├── config/
│   └── packages/               # bundle configuration
├── docs/audit/                 # code audits and their remediation plans
├── migrations/                 # Doctrine migrations
├── public/build/               # compiled assets (git-ignored, shipped by CI)
├── specs/                      # spec-kit artifacts, one directory per feature
├── src/
│   ├── Controller/             # thin controllers; Admin/ holds the EasyAdmin ones
│   ├── DTO/                    # data transfer objects (ContactDTO)
│   ├── Entity/                 # Doctrine entities
│   ├── Form/Type/              # form types
│   ├── Repository/             # every Doctrine query lives here
│   └── Service/                # business logic, by domain (Cookbook, Motus)
├── templates/
│   ├── app/                    # one directory per mini-app
│   ├── components/             # shadcn kit components + underscore-prefixed partials
│   ├── contact/ email/ legal/ security/
│   └── portfolio/              # homepage layout, header, sections, footer
├── tests/
│   ├── Service/                # unit tests
│   └── Smoke/                  # public routes and accessibility
├── compose.yaml                # Docker services
└── webpack.config.js           # Webpack Encore config
```

## Conventions

Code identifiers are **English**; visitor-facing text is **French**, written inline in the
templates. A Stimulus controller must be registered by hand in `assets/bootstrap.js` or it silently
never runs. Every `App` row needs a matching `templates/components/_icon_<slug>.html.twig`, or the
homepage throws.

The full rule set lives in [`.claude/rules/`](.claude/rules/), scoped by file glob.
