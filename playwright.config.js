import { defineConfig, devices } from '@playwright/test';

/**
 * Accessibility and browser-level checks. See tests/e2e.
 *
 * Two servers are started for the suite: the site itself, and a stub standing in for the
 * Cookbook API — without it the recipe page renders its degraded state and the cards, the
 * part most worth auditing, never appear.
 *
 * Override the base URL with BASE_URL to audit an already-running server (the Docker stack
 * on :8080, or a deployed environment).
 */
const baseURL = process.env.BASE_URL || 'http://127.0.0.1:8000';
const cookbookApiUrl = 'http://127.0.0.1:8001';

export default defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/global-setup.js',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: process.env.CI ? 2 : undefined,
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    // Skipped entirely when BASE_URL is set: that means a server is already up.
    webServer: process.env.BASE_URL
        ? undefined
        : [
              {
                  command: `php -S 127.0.0.1:8001 ${process.cwd()}/tests/e2e/cookbook-api-stub.php`,
                  url: `${cookbookApiUrl}/api/v1/categories`,
                  reuseExistingServer: !process.env.CI,
                  stdout: 'ignore',
                  stderr: 'ignore',
              },
              {
                  command: 'symfony server:start --no-tls --port=8000',
                  url: `${baseURL}/mentions-legales`,
                  reuseExistingServer: !process.env.CI,
                  timeout: 120_000,
                  // The Symfony CLI streams an access log with no switch to silence it;
                  // failures stay diagnosable through traces and screenshots.
                  stdout: 'ignore',
                  stderr: 'ignore',
                  env: {
                      APP_ENV: 'test',
                      // Debug off: the profiler slows every response and injects a toolbar
                      // that axe would then report on.
                      APP_DEBUG: '0',
                      DATABASE_URL: 'sqlite:///%kernel.project_dir%/var/e2e.db',
                      COOKBOOK_API_URL: cookbookApiUrl,
                      COOKBOOK_API_VERSION: 'v1',
                      COOKBOOK_API_USERNAME: 'e2e',
                      COOKBOOK_API_PASSWORD: 'e2e',
                  },
              },
          ],
});
