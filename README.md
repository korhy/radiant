![CI](https://github.com/korhy/radiant/actions/workflows/ci.yml/badge.svg)
![Deploy](https://github.com/korhy/radiant/actions/workflows/deploy.yml/badge.svg)
![Version](https://img.shields.io/github/v/tag/korhy/radiant?label=version)

# Radiant — Personal Portfolio

Personal portfolio and web application built with Symfony 7, featuring a dynamic portfolio, and interactive mini-apps.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2, Symfony 7.4 |
| ORM | Doctrine ORM + PostgreSQL |
| Frontend | Twig, Tailwind CSS v3, Stimulus (Hotwire) |
| Build | Webpack Encore, PostCSS |
| Admin | EasyAdmin 4 |
| Infrastructure | Docker, Supervisor |

## Features

- **Portfolio** — Experience and personal projects fetched from database, rendered server-side
- **Stream Deck** — Quick-access panel linking to built-in mini-apps
- **Taquin** — Sliding puzzle mini-app (Stimulus controller)
- **Admin panel** — EasyAdmin back-office for managing content

## Routes

| Route | URL | Description |
|---|---|---|
| `homepage` | `/` | Portfolio home |
| `taquin` | `/app/taquin` | Sliding puzzle app |

## Getting Started

### Prerequisites

- Docker & Docker Compose
- Git

### Installation

```bash
git clone git@github.com:korhy/radiant.git
cd radiant

# Copy environment file
cp .env .env.local
# Edit .env.local with your values (database, mailer, etc.)

# Start containers (app :8080, PostgreSQL, Mailpit :8025)
make up

# Install PHP dependencies
make install

# Run database migrations
make db-migrate

# Install JS dependencies and build assets — on the host, not in the container
npm install
npm run dev
```

> `npm` runs on the **host**. Installing inside the container rewrites `package-lock.json`'s `name`
> field to the container workdir, producing a bogus lockfile diff.

The app is available at [http://localhost:8080](http://localhost:8080).

### Development

```bash
# Start containers
make up

# Watch assets (auto-rebuild on change)
make watch

# Symfony console
make console C="<command>"
```

### Useful commands

Run `make help` for the full list.

```bash
# Clear cache
make cc

# Create and apply a migration after entity changes
make db-migration     # review the generated SQL before applying it
make db-migrate

# Access PostgreSQL
make psql

# Quality gate — exactly what CI runs (php-cs-fixer, PHPStan, PHPUnit)
make ci
```

## Project Structure

```
├── assets/
│   ├── app.js                  # JS entry point
│   ├── controllers/            # Stimulus controllers
│   └── styles/                 # CSS (Tailwind + custom)
├── config/                     # Symfony configuration
├── migrations/                 # Doctrine migrations
├── public/
│   └── build/                  # Compiled assets (git-ignored)
├── src/
│   ├── Controller/             # Symfony controllers
│   ├── Entity/                 # Doctrine entities
│   └── Form/                   # Symfony forms
├── templates/
│   ├── portfolio/              # Portfolio layout & sections
│   ├── contact/                # Contact page
│   ├── app/                    # Mini-apps (Taquin...)
│   └── email/                  # Email templates
├── compose.yaml                # Docker services
└── webpack.config.js           # Webpack Encore config
```
