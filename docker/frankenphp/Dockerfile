# ─── FrankenPHP (PHP 8.3 + Caddy) — LOCAL DEVELOPMENT image ───────────────────
# FrankenPHP bundles Caddy + PHP in a single container (no nginx / php-fpm).
# This image is for `docker compose up` only. Production uses Dockerfile.prod
# (see docs/deploy/coolify-migration.md).
FROM dunglas/frankenphp:php8.3.36

# unzip: Composer needs it (or the zip PHP extension, which the app itself does not require) to
# extract downloaded package archives — without it, `composer install` degrades silently per
# package and ultimately fails.
RUN apt-get update && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

# install-php-extensions ships with the FrankenPHP image and compiles the right
# extensions for the current architecture (amd64 / arm64). Matches what
# composer.lock + Doctrine's Postgres driver actually need — see Dockerfile.prod.
RUN install-php-extensions \
    pdo_pgsql \
    intl \
    opcache \
    mbstring \
    xsl

# Development OPcache: revalidate on every request so code edits are picked up
# immediately (the app source is bind-mounted).
RUN { \
      echo "opcache.enable=1"; \
      echo "opcache.enable_cli=0"; \
      echo "opcache.validate_timestamps=1"; \
      echo "opcache.revalidate_freq=0"; \
    } > /usr/local/etc/php/conf.d/opcache-dev.ini

RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/dev.ini

# Composer (latest stable), handy inside the container.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Bootstraps the app on boot (composer install, wait for DB, migrations) then
# hands off to FrankenPHP.
COPY docker/frankenphp/dev-entrypoint.sh /usr/local/bin/dev-entrypoint
RUN chmod +x /usr/local/bin/dev-entrypoint

ENTRYPOINT ["dev-entrypoint"]
