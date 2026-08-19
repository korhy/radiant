---
description: Styling conventions — Tailwind CSS 3 utility-first, the purge traps, design tokens, and the joint Tailwind 4 + shadcn target.
paths:
  - "**/*.twig"
  - "**/*.css"
  - "**/assets/styles/**"
  - "**/tailwind.config.js"
  - "**/postcss.config.js"
---

# Styling — Tailwind CSS 3

## Golden rule
**Utilities in the markup; reuse through Twig partials — not through a CSS component layer.**
When the same cluster of classes appears twice, extract a **partial**, not a `.my-card` class.

Current setup: **Tailwind 3.4** via PostCSS (`postcss.config.js` → `tailwindcss` + `autoprefixer`),
wired into Webpack Encore with `enablePostCssLoader()`. Configuration is in
**`tailwind.config.js`** (Tailwind 3 style — a JS config, not CSS `@theme`).

## ⚠️ Two purge traps — read before touching styles

**1. The content glob is narrower than Twig's loader.**

```js
content: ["./assets/**/*.js", "./templates/**/*.html.twig"]
```

But `config/packages/twig.yaml` sets `file_name_pattern: '*.twig'`. So a template named
`foo.twig` (without `.html`) is invisible to Tailwind and **every utility class in it gets
purged** — the page renders unstyled, with no error anywhere. **Always name templates
`*.html.twig`.** If you ever add a `.twig`-only file, widen the glob in the same change.

**2. The MOTUS block is deliberately outside `@layer`.**

`assets/styles/app.css` ends with a large hand-written Motus block placed **outside**
`@layer components`, with a French comment explaining why: those classes are applied by JavaScript
at runtime, so Tailwind's purge cannot see them in any source file. Inside a layer they would be
stripped. **Do not "tidy" that block into a layer**, and do not reformat it away. Any new
JS-applied class has the same problem and belongs in the same region.

## Design tokens

`tailwind.config.js` extends the palette — and note it **shadows Tailwind defaults**:

```js
colors: {
  'bg-blue-950': '#23313e',   // custom name, used as bg-bg-blue-950
  'amber-400': '#FCC175',     // overrides the default amber
  'amber-500': '#faa734ff',
  'amber-600': '#E68A1A',
  'amber-700': '#B76A14',
}
```

So `amber-500` in this project is **not** Tailwind's amber-500. Don't "correct" a colour toward the
default palette, and don't introduce a second accent — the visual language is a dark slate gradient
with an amber accent.

Prefer the scale over arbitrary values: `p-4`, not `p-[17px]`. Reach for `[...]` only for a genuine
one-off (an `aspect-ratio`, a precise icon size).

## When plain CSS is acceptable
- Overriding third-party DOM you don't control.
- Runtime-applied classes that purge cannot see (the Motus case above).
- Keyframes and complex animations.

Everything else: utilities.

**When you do write CSS in `assets/styles/**`, prefer Tailwind `@apply`** over raw property
declarations wherever the utilities can express the rule — it keeps the stylesheet in the design
system's vocabulary instead of forking a second one. Reach for raw CSS only where Tailwind genuinely
can't (some vendor overrides, properties with no utility). Design tokens themselves are the token
layer and stay as CSS custom properties — they are not `@apply`-able.

## Mockups
Mockup sizing is **indicative, not pixel-perfect**. Match spacing rhythm, hierarchy and colour
faithfully; snap dimensions to the Tailwind scale rather than reproducing exact pixel values.

## Target — Tailwind 4 **and** shadcn, as one migration

Tailwind 4 is the prerequisite for the shadcn kit ([components-shadcn.md](components-shadcn.md)), and
the two are done **in the same batch** rather than one after the other: the palette has to be mapped
onto CSS-first tokens either way, and doing Tailwind 4 alone would mean mapping it twice — once into
a plain `@theme`, then again onto shadcn's `--primary` / `--background` / `--accent` model. Étape 5
of [the audit](../../../docs/audit/audit-2026-08-18.md) covers both.

The Tailwind half involves at minimum:

1. `npm install tailwindcss@^4 @tailwindcss/postcss` and drop `autoprefixer` from
   `postcss.config.js` in favour of `@tailwindcss/postcss` — which becomes the file's only plugin.
2. Replace the `@tailwind base/components/utilities` directives with `@import 'tailwindcss'`.
3. Delete `tailwind.config.js`, porting the colour overrides into a CSS `@theme` block — decided
   jointly with the shadcn token mapping, not before it.
4. Replace the content globs with **explicit `@source` directives** (`@source '../../templates'`,
   `@source '../../src'`, `@source not '../../public'`). Declaring the sources makes the purge
   deterministic instead of leaning on Tailwind's auto-detection — and it removes purge trap #1
   above, since it no longer depends on a filename glob.
5. **Re-solve the Motus purge problem**, the risky part: those classes are applied by JavaScript and
   appear in no source file. Tailwind 4's `@source inline(...)` exists for exactly this and replaces
   the current "keep it outside `@layer`" workaround.
6. Re-check every screen: overriding `amber-*` behaves differently under CSS-first theming.

Don't start it inside a feature branch. Spec it with `/speckit-specify`.

## See also
- Templates & accessibility: [frontend-twig.md](frontend-twig.md)
- Component standard & the shadcn target: [components-shadcn.md](components-shadcn.md)
- Twig style gate: [linting.md](linting.md) — `make twig-cs-fixer`
