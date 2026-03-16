![CI](https://github.com/korhy/radiant/actions/workflows/ci.yml/badge.svg)

# Radiant — Personal Portfolio

Personal portfolio and web application built with Symfony 7, featuring a dynamic portfolio, and interactive mini-apps.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2, Symfony 7.3 |
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

# Start containers
docker compose up -d

# Install PHP dependencies
docker compose exec app composer install

# Run database migrations
docker compose exec app php bin/console doctrine:migrations:migrate

# Install JS dependencies and build assets
docker compose exec app npm install
docker compose exec app npm run build
```

The app is available at [http://localhost:8080](http://localhost:8080).

### Development

```bash
# Start containers
docker compose up -d

# Watch assets (auto-rebuild on change)
docker compose exec app npm run watch

# Symfony console
docker compose exec app php bin/console <command>
```

### Useful commands

```bash
# Clear cache
docker compose exec app php bin/console cache:clear

# Create a migration after entity changes
docker compose exec app php bin/console make:migration
docker compose exec app php bin/console doctrine:migrations:migrate

# Access PostgreSQL
docker compose exec database psql -U app
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
