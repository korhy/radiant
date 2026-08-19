---
description: Component standard — reuse before writing markup, one partial one responsibility, data passed explicitly. shadcn kit is the target but is NOT installed: never render <twig:X>. Legacy migration strategy.
paths:
  - "**/*.twig"
  - "**/src/Twig/Components/**/*.php"
  - "**/assets/controllers/**/*.js"
---

# Components — current state and the shadcn target

> ## ⚠️ shadcn is NOT installed in this project (yet)
>
> `symfony/ux-toolkit` is **not** a dependency, `symfony/ux-twig-component` is **not** installed,
> there is no `templates/components/ui/`, and nothing renders as `<twig:Button>`. **Do not write code
> that assumes shadcn components exist.** Until the migration below is done, follow "Today's
> convention".

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

A class-backed component is not possible today: `config/packages/twig_component.yaml` does not
exist. Restoring it is part of the shadcn migration below — don't restore it on the side.

---

## Target state — the shadcn kit, adopted with Tailwind 4

The intent is to build UI from the **Symfony UX Toolkit shadcn kit**
(<https://ux.symfony.com/toolkit/kits/shadcn>). Once it lands, the standard becomes:

- **Every visual component starts from the kit.** Don't hand-write a component out of raw Tailwind
  when the kit ships one — install it and compose from it.
- **Install** a component into the project:
  ```bash
  php bin/console ux:install <component> --kit shadcn
  ```
  Files are **copied** into `templates/components/<Name>.html.twig` (plus
  `templates/components/<Name>/…` for compound components) and any
  `assets/controllers/*_controller.js`. You then **own** them — edit freely, like shadcn/ui. It is
  not a vendored dependency.
- **Render** with the namespaced syntax: `<twig:Button>`, `<twig:Card>`, `<twig:Dialog>`. Classes and
  attributes passed by the caller are merged via `tailwind_merge`, not overwritten.
- **Modals use `Dialog`** — never hand-rolled overlay markup. In this project that means
  `_app_detail_drawer.html.twig` and its Stimulus controller are the primary migration target: they
  currently re-implement by hand (`role="dialog"`, `aria-modal`, `inert`, focus return, Escape,
  roving tabs) exactly what `Dialog` provides.
- **Keep the kit's accessibility.** The components ship correct `aria-*`, focus management and
  keyboard wiring — never strip it. A migrated screen that regresses RGAA/EAA (WCAG 2.1 AA) is not
  shippable. Checklist in [frontend-twig.md](frontend-twig.md).
- **User-facing text stays French; component and prop names stay English** — see
  [naming.md](naming.md).

### Prerequisites — in order

The kit is Tailwind 4-native, so it ships **with** the Tailwind 3 → 4 migration, in one batch — the
reasoning is recorded in Étape 5 of [the audit](../../../docs/audit/audit-2026-08-18.md). Don't
split them.

1. **Migrate Tailwind 3 → 4.** See the outline in [frontend-styling.md](frontend-styling.md); the
   Motus purge block is the hard part.
2. **Restore Twig Components.** `composer require symfony/ux-twig-component` and re-create
   `config/packages/twig_component.yaml`:
   ```yaml
   twig_component:
       anonymous_template_directory: 'components/'
       defaults:
           App\Twig\Components\: 'components/'
   ```
   > This **deliberately reverses** the 2026-08-19 removal. That removal was right at the time (the
   > config pointed at a non-existent directory and nothing used it); the kit makes Twig Components a
   > hard requirement, so it comes back with an actual consumer this time. Turbo and Live Components
   > stay removed — the kit needs neither.
3. **Install the toolkit — pinned to the 2.x line**: `composer require --dev symfony/ux-toolkit:^2.36`.
   > **Version ceiling, verified 2026-08-19.** `symfony/ux-toolkit` **3.x requires PHP >= 8.4 and
   > Symfony ^7.4|^8.0**. This project is PHP 8.2 (CI runs 8.2) on Symfony 7.3, so only the 2.x line
   > (`php >=8.1`, `symfony/* ^6.4|^7.0|^8.0`) resolves. Moving to 3.x is a PHP/Symfony upgrade, not a
   > front-end change — re-check the constraint before assuming the ceiling still holds.
4. **Install the components' runtime dependencies.** They are dev-dependencies of the toolkit but
   **runtime** dependencies of the copied components, so they go in `require`, not `require-dev`:
   `twig/html-extra`, `tales-from-a-dev/twig-tailwind-extra` (provides `tailwind_merge`),
   `symfony/ux-icons`.
5. **Install the kit's CSS package**: `npm install tw-animate-css`, imported from
   `assets/styles/app.css` right after `@import 'tailwindcss'`.
   > There is **no `shadcn` npm package** to install — that is the React CLI, unrelated to the Symfony
   > kit. `tw-animate-css` is the only npm dependency the kit adds.
6. **Reconcile the palette.** The project overrides `amber-400/500/600/700` and adds `bg-blue-950`;
   shadcn drives everything from `--primary`, `--background`, `--accent`, `--ring`… Decide how the
   dark-slate + amber identity maps onto those tokens **before** installing any component, or the
   first one will look foreign. The shape to aim for: raw brand colours defined once in `:root`
   (`--primary`, `--secondary`…), and an `@theme inline` block mapping them to the Tailwind utility
   names. Rebrand by editing the tokens, never the components.

### Migration strategy — the "Legacy move"

Never big-bang overwrite a component that is in use: the API differs and templates break silently.
When a kit component's name collides with something that already exists:

1. **Move the old one aside** so the canonical name is free —
   `templates/components/X.html.twig` → `templates/components/Legacy/X.html.twig`
   (rendered `<twig:Legacy:X>`); class-backed equivalents move to `src/Twig/Components/Legacy/`.
2. **Repoint every current usage** to `Legacy:X`, then run `php bin/console lint:twig templates` —
   nothing should break.
3. **Install the shadcn component** under the canonical name.
4. **Migrate usages screen by screen**, adapting the API, and **verify each screen** in the browser
   including keyboard-only navigation plus an automated a11y pass (axe / Lighthouse) — the
   `playwright-skill` drives both. Migrating to shadcn must *improve* accessibility, never regress it.
5. **Delete the `Legacy/` component** once it has zero usages.

Radiant's partials are underscore-prefixed includes rather than `<twig:X>` components, so most of
them have **no name collision** — they are migrated by replacing the `include()` call, and the
Legacy move only applies where a partial is genuinely reused across screens.

This is a spec-sized change: run it through `/speckit-specify` rather than folding it into a feature
branch.

## See also
- Partial conventions & accessibility: [frontend-twig.md](frontend-twig.md)
- Tailwind setup, purge traps and the v4 outline: [frontend-styling.md](frontend-styling.md)
- Interactivity: [ux-stimulus-turbo.md](ux-stimulus-turbo.md)
