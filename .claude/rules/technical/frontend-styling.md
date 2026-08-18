---
description: Styling conventions — Tailwind CSS 3 utility-first, the purge traps, design tokens, and the Tailwind 4 target.
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

## Mockups
Mockup sizing is **indicative, not pixel-perfect**. Match spacing rhythm, hierarchy and colour
faithfully; snap dimensions to the Tailwind scale rather than reproducing exact pixel values.

## Target — Tailwind 4

Moving to Tailwind 4 is the **prerequisite for adopting the shadcn kit**
([components-shadcn.md](components-shadcn.md)). It is a change of its own, not a side effect, and it
involves at minimum:

1. `npm install tailwindcss@^4 @tailwindcss/postcss` and drop `autoprefixer` from
   `postcss.config.js` in favour of `@tailwindcss/postcss`.
2. Replace the `@tailwind base/components/utilities` directives with `@import 'tailwindcss'`.
3. Delete `tailwind.config.js`, porting the colour overrides into a CSS `@theme` block.
4. Replace the content globs with `@source` directives — **and re-solve the Motus purge problem**,
   which is the risky part (Tailwind 4 offers `@source inline(...)` for exactly this).
5. Re-check every screen: overriding `amber-*` behaves differently under CSS-first theming.

Don't start it inside a feature branch. Spec it with `/speckit-specify`.

## See also
- Templates & accessibility: [frontend-twig.md](frontend-twig.md)
- Component standard & the shadcn target: [components-shadcn.md](components-shadcn.md)
