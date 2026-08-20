import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

/**
 * The automated accessibility pass the rules ask for, run in both themes.
 *
 * Only WCAG 2.0/2.1 levels A and AA are gated: that is the project's standard (RGAA/EAA).
 * axe's `best-practice` tag is deliberately left out — it reports structural advice such as
 * "the page should have a level-one heading", which is worth acting on but is not a
 * conformance failure and would turn this suite into a style opinion.
 *
 * The Symfony debug toolbar is excluded: it is not part of the site, and it carries contrast
 * failures of its own. It is absent from the suite's own server (APP_DEBUG=0) but appears as
 * soon as someone points BASE_URL at the Docker dev stack.
 */

const WCAG_AA = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'];

const PAGES = [
    ['the homepage', '/'],
    ['Taquin', '/app/taquin'],
    ['Motus', '/app/motus'],
    ['the recipe list', '/app/cookbook'],
    ['the contact form', '/contact'],
    ['the legal notice', '/mentions-legales'],
    ['the sign-in form', '/login'],
];

/** Violations as readable lines, so a failure names the rule and the offending nodes. */
async function violationsOf(page) {
    const { violations } = await new AxeBuilder({ page })
        .withTags(WCAG_AA)
        .exclude('.sf-toolbar')
        .analyze();

    return violations.map(
        (v) =>
            `${v.id} [${v.impact}] ${v.help} — ${v.nodes.length} node(s): ` +
            v.nodes
                .slice(0, 5)
                .map((n) => n.target.join(' '))
                .join(' | '),
    );
}

/** Scrolls until the recipe count stops growing, and returns it. */
async function loadEveryRecipe(page) {
    const cards = page.locator('[data-slot="recipe-card"]');
    let previous = -1;

    for (let i = 0; i < 8; i++) {
        const count = await cards.count();
        if (count === previous) break;
        previous = count;
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
        await page.waitForTimeout(600);
    }

    return cards.count();
}

for (const colorScheme of ['light', 'dark']) {
    test.describe(`${colorScheme} theme`, () => {
        test.use({ colorScheme });

        for (const [name, path] of PAGES) {
            test(`${name} has no WCAG 2.1 AA violation`, async ({ page }) => {
                await page.goto(path);

                expect(await violationsOf(page)).toEqual([]);
            });
        }

        /**
         * The cards appended by the infinite scroll are the same markup as the first screen
         * (see specs/005-cookbook-card-component), but only an audit run after loading proves
         * that what JavaScript inserts is still conformant.
         */
        test('the recipe list stays conformant once scrolled', async ({ page }) => {
            await page.goto('/app/cookbook');

            expect(await loadEveryRecipe(page)).toBeGreaterThan(6);
            expect(await violationsOf(page)).toEqual([]);
        });
    });
}
