const { chromium } = require('playwright');

const TARGET_URL = 'http://localhost:8080/app/cookbook';
const AXE_PATH = '/private/tmp/claude-501/-Users-clement-code-korhy-PHP-Symfony-radiant/3ff03657-138e-464a-9fa6-59fe80b3953b/scratchpad/node_modules/axe-core/axe.min.js';

async function runAxe(page) {
  await page.addScriptTag({ path: AXE_PATH });
  return page.evaluate(async () => {
    // The Symfony dev toolbar is not part of the page under test.
    const context = { exclude: [['.sf-toolbar'], ['.sf-minitoolbar']] };
    const wcag = await window.axe.run(context, {
      runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
    });
    const practice = await window.axe.run(context, { runOnly: { type: 'tag', values: ['best-practice'] } });
    const shape = v => ({
      id: v.id,
      impact: v.impact,
      help: v.help,
      nodes: v.nodes.slice(0, 3).map(n => n.target.join(' ')),
      count: v.nodes.length,
    });

    return { wcag: wcag.violations.map(shape), practice: practice.violations.map(shape) };
  });
}

async function scrollToEnd(page) {
  let previous = -1;
  for (let i = 0; i < 8; i++) {
    const count = await page.locator('[data-slot="recipe-card"]').count();
    if (count === previous) break;
    previous = count;
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(900);
  }
  return page.locator('[data-slot="recipe-card"]').count();
}

(async () => {
  const browser = await chromium.launch({ headless: false });
  const report = {};

  for (const colorScheme of ['light', 'dark']) {
    const context = await browser.newContext({ colorScheme, viewport: { width: 1280, height: 800 } });
    const page = await context.newPage();

    await page.goto(TARGET_URL, { waitUntil: 'networkidle' });
    const initialCards = await page.locator('[data-slot="recipe-card"]').count();
    const initial = await runAxe(page);

    const scrolledCards = await scrollToEnd(page);
    const afterScroll = await runAxe(page);

    report[colorScheme] = {
      'chargement initial': { cartes: initialCards, violations: initial },
      'après défilement': { cartes: scrolledCards, violations: afterScroll },
    };

    await page.screenshot({ path: `/tmp/axe-cookbook-${colorScheme}.png`, fullPage: false });
    await context.close();
  }

  console.log('\n================ AXE-CORE — /app/cookbook (hors barre de debug) ================\n');
  let totalWcag = 0;
  for (const [theme, states] of Object.entries(report)) {
    console.log(`Thème ${theme} :`);
    for (const [state, data] of Object.entries(states)) {
      const v = data.violations;
      totalWcag += v.wcag.length;
      console.log(`  ${state} — ${data.cartes} cartes — WCAG : ${v.wcag.length} · bonnes pratiques : ${v.practice.length}`);
      for (const x of v.wcag) console.log(`    ✗ WCAG [${x.impact}] ${x.id} — ${x.count} nœud(s) : ${x.nodes.join(' | ')}`);
      for (const x of v.practice) console.log(`    · pratique [${x.impact}] ${x.id} — ${x.count} nœud(s) : ${x.nodes.join(' | ')}`);
    }
  }
  console.log(`\nTOTAL WCAG 2.1 A/AA : ${totalWcag} violation(s)\n`);

  await browser.close();
})();
