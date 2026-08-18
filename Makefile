## Radiant — dev tasks. Run `make help` for the list.

DOCKER_COMPOSE = docker compose

# `docker compose exec` aborts with "the input device is not a TTY" when stdin isn't a terminal
# (scripts, CI, an editor's task runner). Detect it and pass -T in that case only, so interactive
# runs keep their colours and a piped run still works.
TTY := $(shell test -t 0 && echo 1)
EXEC = $(DOCKER_COMPOSE) exec $(if $(TTY),,-T)

APP = $(EXEC) app
CONSOLE = $(APP) php bin/console
COMPOSER = $(APP) composer
PHP_CS_FIXER = $(APP) vendor/bin/php-cs-fixer
# PHPStan's parallel workers exhaust the container's 128M cap and die with exit code 255, which says
# nothing about the code. CI has no such cap, hence the flag lives here and not in the workflow.
PHPSTAN = $(APP) php -d memory_limit=-1 vendor/bin/phpstan
PHPUNIT = $(APP) php bin/phpunit

# npm runs on the HOST, never in the container: an in-container install rewrites package-lock.json's
# "name" field to the container workdir, producing a bogus lockfile diff.
NPM = npm

.DEFAULT_GOAL := help

.PHONY: help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

## —— Docker (local dev) ————————————————————————————————————————————
.PHONY: up
up: ## Start the dev stack (app :8080, Postgres, Mailpit :8025)
	$(DOCKER_COMPOSE) up -d

.PHONY: down
down: ## Stop the dev stack
	$(DOCKER_COMPOSE) down

.PHONY: restart
restart: ## Recreate the app container (picks up compose.yaml changes)
	$(DOCKER_COMPOSE) up -d --force-recreate app

.PHONY: ps
ps: ## Show the stack status and published ports
	$(DOCKER_COMPOSE) ps

.PHONY: logs
logs: ## Tail the dev stack logs
	$(DOCKER_COMPOSE) logs -f

.PHONY: sh
sh: ## Open a shell in the app container
	$(DOCKER_COMPOSE) exec app sh

## —— Symfony ———————————————————————————————————————————————————————
.PHONY: install
install: ## Install PHP dependencies
	$(COMPOSER) install

.PHONY: cc
cc: ## Clear the Symfony cache
	$(CONSOLE) cache:clear

.PHONY: console
console: ## Run a console command (make console C="debug:router")
	$(CONSOLE) $(C)

## —— Database ——————————————————————————————————————————————————————
.PHONY: db-migration
db-migration: ## Generate a migration from the mapping (review the SQL!)
	$(CONSOLE) make:migration

.PHONY: db-migrate
db-migrate: ## Apply pending migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

.PHONY: db-validate
db-validate: ## Check the mapping matches the database schema
	$(CONSOLE) doctrine:schema:validate

.PHONY: psql
psql: ## Open a psql shell on the dev database
	$(EXEC) database psql -U $${POSTGRES_USER:-app} -d $${POSTGRES_DB:-app}

## —— Assets (Webpack Encore, run on the host) ——————————————————————
.PHONY: watch
watch: ## Rebuild front assets on change
	$(NPM) run watch

.PHONY: dev
dev: ## One-off dev build of the front assets
	$(NPM) run dev

.PHONY: assets
assets: ## Production build (normally CI's job — re-run `make dev` afterwards)
	$(NPM) run build

## —— Linting / static analysis —————————————————————————————————————
.PHONY: php-cs-fixer
php-cs-fixer: ## Check PHP code style (@Symfony)
	$(PHP_CS_FIXER) fix --dry-run --diff

.PHONY: php-cs-fixer-fix
php-cs-fixer-fix: ## Fix PHP code style
	$(PHP_CS_FIXER) fix

.PHONY: phpstan
phpstan: ## Run static analysis (level 5)
	$(PHPSTAN) analyse

.PHONY: lint
lint: php-cs-fixer phpstan ## Run every linter (Twig/JS/CSS have no gate — review them by hand)

## —— Tests —————————————————————————————————————————————————————————
.PHONY: phpunit
phpunit: ## Run the test suite (use TEST=path for a subset)
	$(PHPUNIT) $(if $(TEST),$(TEST),)

.PHONY: ci
ci: php-cs-fixer phpstan phpunit ## Run exactly what .github/workflows/ci.yml runs
