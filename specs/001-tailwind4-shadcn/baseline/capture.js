const { chromium } = require('playwright');
const path = require('path');

const OUT = process.argv[2];
const BASE = 'http://localhost:8080';
const PAGES = {
  'accueil': '/', 'taquin': '/app/taquin', 'motus': '/app/motus',
  'cookbook': '/app/cookbook', 'cookbook-recette': '/app/cookbook/recipe/1',
  'contact': '/contact', 'mentions-legales': '/mentions-legales', 'login': '/login',
};
const WIDTHS = [375, 768, 1280];

(async () => {
  const browser = await chromium.launch();
  let ok = 0, ko = [];
  for (const [name, route] of Object.entries(PAGES)) {
    for (const w of WIDTHS) {
      const ctx = await browser.newContext({ viewport: { width: w, height: 900 } });
      const page = await ctx.newPage();
      try {
        const r = await page.goto(BASE + route, { waitUntil: 'networkidle', timeout: 20000 });
        if (!r || r.status() >= 400) { ko.push(`${name}-${w} → HTTP ${r && r.status()}`); }
        else {
          await page.screenshot({ path: path.join(OUT, `${name}-${w}.png`), fullPage: true });
          ok++;
        }
      } catch (e) { ko.push(`${name}-${w} → ${e.message.split('\n')[0]}`); }
      await ctx.close();
    }
  }
  await browser.close();
  console.log(`captures réussies : ${ok}/${Object.keys(PAGES).length * WIDTHS.length}`);
  if (ko.length) { console.log('échecs :'); ko.forEach(k => console.log('  ' + k)); }
})();
