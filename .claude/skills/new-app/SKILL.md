---
name: new-app
description: Scaffold a complete Stream Deck mini-app for Radiant — the App row, the route and controller action, the service, the template, the mandatory icon partial, the Stimulus controller with its manual registration, and the "Behind the scenes" drawer wiring. Use when the user asks to add a mini-app, a new tile on the Stream Deck, a new /app/<something> page, or "/new-app".
---

# /new-app — Scaffold a Stream Deck mini-app

Taquin, Motus and Cookbook all share one anatomy. This skill reproduces it completely, including the
two pieces that fail **silently** when forgotten: the icon partial and the Stimulus registration.

## Step 0 — Read the rules first

`CLAUDE.md` plus, at minimum:
- [radiant.md](../../rules/business/radiant.md) — the Stream Deck contract and the `App` entity
- [ux-stimulus-turbo.md](../../rules/technical/ux-stimulus-turbo.md) — manual registration
- [frontend-twig.md](../../rules/technical/frontend-twig.md) — partials and accessibility
- [backend-php.md](../../rules/technical/backend-php.md) — typing, thin controllers
- [frontend-styling.md](../../rules/technical/frontend-styling.md) — Tailwind 3 and the purge traps

## Step 1 — Gather inputs

Ask with **AskUserQuestion** for anything not stated (don't invent):

| Input | Notes |
|---|---|
| **slug** | lowercase, unique, no spaces — drives the route, the template folder **and the icon filename** |
| **label** | short, shown under the tile (French, it is visitor-facing) |
| **route path** | conventionally `/app/<slug>` |
| **description** | one or two sentences for the drawer |
| **position** | ordering in the grid; default = last |
| **needs a backend service?** | pure client-side (Taquin) vs server logic (Motus) vs external API (Cookbook) |
| **needs a JSON endpoint?** | e.g. validating a guess, paginating results |
| **drawer content** | `techStack`, `challenges`, `improvements`, `resources` — JSON, shapes in `AppCrudController::configureFields()` |

Confirm the plan before writing anything.

## Step 2 — Backend

**Controller action** — add to `src/Controller/ApplicationController.php` (all mini-apps live there):

```php
#[Route('/app/<slug>', name: '<slug>')]
public function <slug>(AppRepository $appRepository): Response
{
    return $this->render('app/<slug>/index.html.twig', [
        'app_detail' => $appRepository->findBySlug('<slug>'),
    ]);
}
```

- `app_detail` is **required** — it is what the shared drawer renders.
- Route name is bare `snake_case`, **no prefix** (`taquin`, `motus`, `cookbook`).
- Keep the action thin. Any real logic goes to `src/Service/<Name>/`, stateless, modelled on
  `MotusService` (pure functions, a private const dataset, no state).
- A JSON endpoint is a second action returning `JsonResponse`. **Clamp and cast** every request
  parameter, as the existing actions do (`max(1, (int) $request->query->get('page', 1))`) — see
  [security.md](../../rules/technical/security.md).
- `declare(strict_types=1)` on every new file.

## Step 3 — The `App` row

The tile only appears once a row exists. Two routes — **ask which**:
- **Migration** (reproducible, ships with the code): `make:migration`, then hand-write the INSERT.
  Remember migrations run unattended in production — see
  [deployment.md](../../rules/technical/deployment.md).
- **EasyAdmin** (`/admin` → Apps): fine for a one-off, but the row then exists only where it was
  typed — it will be missing in every other environment.

Fields: `slug`, `label`, `route` (the **route name**, not the path), `position`, `description`, and
the four JSON columns.

## Step 4 — Template

`templates/app/<slug>/index.html.twig` — extends `base.html.twig`, sets a unique `<title>`, and ends
with the shared drawer:

```twig
{{ include('components/_app_detail_drawer.html.twig', { app_detail: app_detail }) }}
```

Tailwind utilities only; the visual language is the dark slate gradient with the amber accent.
**Name the file `*.html.twig`** — a bare `.twig` is invisible to Tailwind's purge and renders
unstyled. Sub-partials go in the same folder, underscore-prefixed (`_legend.html.twig`).

## Step 5 — The icon partial (mandatory)

`templates/components/_icon_<slug>.html.twig` — **the homepage throws without it**, because
`streamdeck.html.twig` resolves the filename from the slug at render time.

- Inline SVG, `aria-hidden="true"` (the adjacent text label names the link).
- No fixed width/height: the parent `<span class="h-6 w-6">` sizes it. Use
  `viewBox` + `fill="currentColor"` / `stroke="currentColor"` so it inherits the hover colour.

## Step 6 — Stimulus controller (two edits, always)

1. `assets/controllers/<slug>_controller.js` — `static targets` / `static values`, ES private fields
   for internal state, and **cleanup in `disconnect()`** (observers, timers, listeners) since Turbo
   keeps the runtime alive across navigations.
2. **Register it** in `assets/bootstrap.js`:
   ```js
   import <Name>Controller from './controllers/<slug>_controller.js';
   app.register('<slug>', <Name>Controller);
   ```

Skipping (2) leaves a dead controller with **no console error** — that is how
`recipe_chat_controller.js` ended up unused.

Then reference it in the template: `<div data-controller="<slug>">`.

## Step 7 — Accessibility pass

Before calling it done (see [frontend-twig.md](../../rules/technical/frontend-twig.md)):
keyboard-operable controls (`<button>`, not clickable `<div>`s), visible focus, accessible names on
icon-only buttons, AA contrast, no information conveyed by colour alone, `prefers-reduced-motion`
respected.

## Step 8 — Verify

```bash
make cc
make php-cs-fixer-fix
make phpstan
make watch
```

Then **load two pages**, not one:
1. `/app/<slug>` — the mini-app itself.
2. **`/` — the homepage**, which is where a missing icon partial or a bad route name blows up.

Tab through the interface with the keyboard.

## Report
List every file created or modified, state whether the `App` row went in via migration or admin, and
confirm the icon partial and the `bootstrap.js` registration are both in place.
