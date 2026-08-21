import { execFileSync } from 'node:child_process';

/**
 * The suite runs against its own SQLite file, separate from the PHPUnit one so a run never
 * disturbs the other. The project has no fixtures, so the schema is built here and a single
 * Stream Deck tile is seeded: without a row in `app`, the homepage renders an empty deck and
 * the dynamic icon include — the contract most likely to break — is never exercised.
 */
const env = {
    ...process.env,
    APP_ENV: 'test',
    APP_DEBUG: '0',
    DATABASE_URL: 'sqlite:///%kernel.project_dir%/var/e2e.db',
};

function console_(...args) {
    return execFileSync('php', ['bin/console', ...args], { env, stdio: 'pipe' }).toString();
}

export default function globalSetup() {
    console_('doctrine:schema:drop', '--force', '--full-database', '--no-interaction');
    console_('doctrine:schema:create', '--no-interaction');
    console_(
        'doctrine:query:sql',
        "INSERT INTO app (slug, label, route, position) VALUES ('taquin', 'Taquin', 'taquin', 1)",
    );
}
