---
description: Frontend conventions — Twig templates, partials, Webpack Encore, accessibility (RGAA/EAA).
paths:
  - "**/*.twig"
  - "**/assets/**"
  - "**/*.css"
  - "**/*.js"
  - "**/webpack.config.js"
---

# Frontend Twig

## Principles
- Twig stays **declarative**: minimal logic, no heavy processing, no Doctrine queries.
- **Reuse partials instead of duplicating markup.** Before adding markup, check
  `templates/components/**` for an existing partial (`_app_detail_drawer`, `_icon_*`, `_legend`).
  Extract one as soon as markup repeats.
- **No inline `<script>`**: JS goes through Webpack Encore + Stimulus (see
  [ux-stimulus-turbo.md](ux-stimulus-turbo.md)).
- Load assets with the Encore helpers — `{{ encore_entry_link_tags('app') }}` /
  `{{ encore_entry_script_tags('app') }}` (already wired in `base.html.twig`). New entrypoints are
  declared in `webpack.config.js` via `addEntry`. There is a single `app` entry today; adding another
  is a decision, not a reflex.
- Styling is **Tailwind utility-first** — see [frontend-styling.md](frontend-styling.md).

## Components — the current shape

**Symfony UX Twig Components ne sont pas en usage.** `config/packages/twig_component.yaml` a été
supprimé le 2026-08-19 : il déclarait `App\Twig\Components\ → components/` alors que
`src/Twig/Components/` n'a jamais existé. Aucun gabarit n'est rendu en `<twig:Xxx>`.

What the project actually does — and what you should follow until that changes:

```twig
{{ include('components/_app_detail_drawer.html.twig', { app_detail: app_detail }) }}
```

- Partials live in `templates/components/`, **underscore-prefixed**, `snake_case`.
- They take explicit variables through `include()`'s second argument. Pass what the partial needs;
  don't rely on ambient context.
- One partial = one responsibility.
- **The `_icon_<slug>.html.twig` family is contractual**, not decorative: the Stream Deck resolves
  the filename from the `App` slug at render time. See [../business/radiant.md](../business/radiant.md).

When a partial starts needing **data from the database**, don't query in the template — the
controller passes it in. Passer à un Twig Component adossé à une classe suppose de réinstaller la
configuration supprimée : c'est une décision, pas un réflexe. Repositories only, never Doctrine in Twig.

The target is the shadcn kit, adopted **together with Tailwind 4** as a single migration (Étape 5
of the audit) — which also restores the Twig Components configuration removed on 2026-08-19,
since the kit requires it. See [components-shadcn.md](components-shadcn.md).

## Accessibility (mandatory)

> **Target: RGAA & EAA conformance** (both built on **WCAG 2.1 AA**). Accessibility is a legal
> obligation, not a polish step — it is treated as an acceptance criterion. This site is also a
> professional showcase: an inaccessible portfolio undercuts what it is meant to demonstrate.
> - **RGAA** — the French implementation of WCAG 2.1 AA.
> - **EAA** (directive 2019/882, applicable since June 2025) — makes accessibility of digital
>   services mandatory across the EU.
> - Primer: <https://www.w3.org/WAI/fundamentals/accessibility-intro/fr>
>
> Any new or migrated component that is not RGAA/EAA-conformant is **not done**.

- **Semantic HTML first.** Use the right element (`<button>`, `<a>`, `<nav>`, `<main>`, `<header>`,
  `<ul>/<ol>`, `<h1>`…`<h6>`). One `<h1>` per page; don't skip heading levels. ARIA is a last
  resort — a native element beats `role=`.
- **Landmarks & structure.** Wrap regions in landmarks (`<nav aria-label>`, `<main>`, `<footer>`).
- **Forms.** Every field has a programmatically-associated `<label>` (`for`/`id`). Errors are linked
  via `aria-describedby`; required fields are marked. Never rely on placeholder as the only label.
- **Interactive elements.** `<button>` for actions, `<a href>` for navigation — never a `<div>` with
  a click handler. Everything usable by keyboard (Tab/Enter/Space/Escape) in a logical order; no
  positive `tabindex`. **The mini-apps are the risk area here**: a Taquin tile or a Motus key that
  only responds to a mouse is a conformance failure.
- **Visible focus.** Keep a clear `focus-visible` style; never `outline: none` without a replacement.
- **ARIA state reflects reality.** Toggles/menus/disclosures update `aria-expanded`,
  `aria-controls`, `aria-current="page"` for the active nav item, `aria-hidden` on decorative icons.
  The "Behind the scenes" drawer must announce its open/closed state.
- **Images & media.** Meaningful images need a descriptive `alt`; decorative images use `alt=""`.
  Icon-only buttons/links need an accessible name (`aria-label` or visually-hidden text). The
  Stream Deck's inline SVGs are decorative and correctly carry `aria-hidden="true"` — the adjacent
  text label is what names the link. Keep that pairing.
- **Colour & contrast.** Meet AA contrast (4.5:1 body, 3:1 large text / UI). Never convey information
  by colour alone — **Motus is the obvious trap**: correct/present/absent must not be signalled by
  colour only, and the legend must stay reachable.
- **Motion.** Respect `prefers-reduced-motion`. No content flashing > 3×/s.
- **Language & titles.** `<html lang="fr">` is set in `base.html.twig`; each page has a unique,
  descriptive `<title>`.
- **Verify.** Check keyboard-only navigation and run an automated pass (axe / Lighthouse a11y)
  before considering a screen done — the `playwright-skill` can drive both.

## Style gate

`templates/**` is checked by **twig-cs-fixer** (`make twig-cs-fixer`, autofix with
`make twig-cs-fixer-fix`), and the same check runs in CI. Let the autofixer handle quoting,
whitespace and indentation instead of hand-editing them. JS and CSS still have **no** gate — see
[linting.md](linting.md).

## See also
- Tailwind utility-first styling: [frontend-styling.md](frontend-styling.md)
- Interactivity layer: [ux-stimulus-turbo.md](ux-stimulus-turbo.md)
- Backend conventions feeding the templates: [backend-php.md](backend-php.md)
- Output escaping / sanitized `|raw`: [security.md](security.md)
