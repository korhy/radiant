---
description: Component standard — current partial-based convention, and the shadcn (Symfony UX Toolkit) target with its Tailwind 4 prerequisite.
paths:
  - "**/*.twig"
  - "**/src/Twig/Components/**/*.php"
  - "**/assets/controllers/**/*.js"
---

# Components — current state and shadcn target

> ## ⚠️ shadcn is NOT installed in this project (yet)
>
> `symfony/ux-toolkit` is **not** a dependency, there is no `templates/components/ui/`, and nothing
> renders as `<twig:Button>`. **Do not write code that assumes shadcn components exist.** Until the
> prerequisites below are done, follow "Today's convention".

## Today's convention

UI is composed from **underscore-prefixed Twig partials** in `templates/components/`, included
explicitly:

```twig
{{ include('components/_app_detail_drawer.html.twig', { app_detail: app_detail }) }}
```

Rules that already apply, and that carry over unchanged to shadcn:

- **Reuse over duplication.** Check `templates/components/` before writing markup. Extract a partial
  the second time a block repeats.
- **One partial, one responsibility.**
- **Pass data explicitly** through `include()`'s second argument.
- **No Doctrine in templates** — the controller supplies the data.
- Accessibility is part of "done" — see [frontend-twig.md](frontend-twig.md).

`config/packages/twig_component.yaml` already declares the Twig Component namespaces
(`App\Twig\Components\ → components/`, `anonymous_template_directory: components/`), so a
class-backed component works the day one is needed — `src/Twig/Components/` simply doesn't exist yet.

## Target state — the shadcn kit

The intent is to build UI from the **Symfony UX Toolkit shadcn kit**, as the sibling
*gestion-bachelor* project does:

- Install a component: `php bin/console ux:install <component> --kit shadcn`, which **copies** the
  source into the project (you own it afterwards — it is not a vendored dependency).
- Render with the namespaced syntax: `<twig:Button>`, `<twig:Card>`, `<twig:Dialog>`.
- **Modals use `Dialog`** — no hand-rolled overlay markup.
- Reuse and compose kit primitives instead of duplicating markup.

### Blocking prerequisites — in order

1. **Migrate Tailwind 3 → 4.** The kit is Tailwind 4-native: it ships `@import 'tailwindcss'`,
   `@custom-variant dark`, a large `@theme inline` token block and `tw-animate-css`. None of that
   works under Tailwind 3's `tailwind.config.js`. See the migration outline in
   [frontend-styling.md](frontend-styling.md) — the Motus purge block is the hard part.
2. **Install the toolkit**: `composer require --dev symfony/ux-toolkit`.
3. **Install the kit's CSS packages**: `npm install shadcn tw-animate-css`, then import
   `tw-animate-css` and `shadcn/tailwind.css` from `assets/styles/app.css` (Webpack Encore variant).
4. **Reconcile the palette.** The project overrides `amber-400/500/600/700` and adds `bg-blue-950`;
   shadcn drives everything from `--primary`, `--background`, `--accent`… Decide how the dark-slate +
   amber identity maps onto those tokens **before** installing components, or the first component
   will look foreign.

### Migration strategy, when the time comes

Use the **Legacy move**: if a kit component's name collides with an existing partial, rename the
existing one to `Legacy*` first, install the kit component, then migrate usages one screen at a time
— never a big-bang rewrite. Verify each migrated screen in the browser (keyboard included) before
deleting the legacy partial.

This is a spec-sized change: run it through `/speckit-specify` rather than folding it into a feature.

## See also
- Partial conventions & accessibility: [frontend-twig.md](frontend-twig.md)
- Tailwind setup, purge traps and the v4 outline: [frontend-styling.md](frontend-styling.md)
- Interactivity: [ux-stimulus-turbo.md](ux-stimulus-turbo.md)
