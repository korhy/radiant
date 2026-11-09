#!/bin/sh
# Production boot for the FrankenPHP container.
# Runs Symfony's deploy steps (cache rebuild + Doctrine migrations) as root so
# they can write, then binds :8080. See docs/deploy/coolify-migration.md.
set -eu

APP_DIR=/app
VAR_DIR="${APP_DIR}/var"

log() {
	printf '[entrypoint] %s\n' "$1"
}

ensure_directories() {
	mkdir -p "${VAR_DIR}/cache" "${VAR_DIR}/log" "${VAR_DIR}/sessions" \
		public/documents/CV public/images/personal_projects
}

fix_permissions() {
	[ "${APP_FIX_PERMISSIONS:-1}" = "1" ] || return 0
	chown -R www-data:www-data "${VAR_DIR}" public/documents public/images/personal_projects
	chmod -R ug+rwX "${VAR_DIR}"
}

run_cache_refresh() {
	[ "${APP_CLEAR_CACHE:-1}" = "1" ] || return 0
	log "Clearing Symfony cache"
	php bin/console cache:clear --env=prod --no-debug --no-warmup
	log "Warming Symfony cache"
	php bin/console cache:warmup --env=prod --no-debug
}

# Pre-fetch every icon used in the templates into the icon cache, so requests are
# served from cache instead of hitting the Iconify API on demand. Must run AFTER the
# cache refresh (which wipes the icon cache). Non-fatal: on a network hiccup icons
# just fall back to on-demand fetching.
warm_icons() {
	[ "${APP_WARM_ICONS:-1}" = "1" ] || return 0
	log "Warming icon cache (Iconify)"
	php bin/console ux:icons:warm-cache --env=prod --no-debug || log "Icon cache warmup failed — icons will be fetched on demand"
}

# Creates the database and waits for it in one move: the container may boot before the database
# server accepts connections, and `database:create --if-not-exists` is the cheapest call that
# proves the server is reachable. Bounded, so a genuine misconfiguration still fails the boot.
DB_ATTEMPTS=30
DB_RETRY_SECONDS=2

create_database() {
	attempt=1
	while [ "${attempt}" -le "${DB_ATTEMPTS}" ]; do
		if php bin/console doctrine:database:create --if-not-exists --env=prod --no-debug 2>/dev/null; then
			log "Database reachable"
			return 0
		fi

		log "Database unreachable (attempt ${attempt}/${DB_ATTEMPTS}) — retrying in ${DB_RETRY_SECONDS}s"
		attempt=$((attempt + 1))
		sleep "${DB_RETRY_SECONDS}"
	done

	log "Database still unreachable after ${DB_ATTEMPTS} attempts — check DATABASE_URL"
	return 1
}

run_migrations() {
	[ "${APP_RUN_MIGRATIONS:-1}" = "1" ] || return 0
	log "Running Doctrine migrations"
	php bin/console doctrine:migrations:migrate --env=prod --no-debug --no-interaction --allow-no-migration
}

cd "${APP_DIR}"

ensure_directories
fix_permissions
run_cache_refresh
warm_icons
create_database
run_migrations
fix_permissions

log "Starting FrankenPHP on :8080"
exec docker-php-entrypoint --config /etc/frankenphp/Caddyfile --adapter caddyfile
