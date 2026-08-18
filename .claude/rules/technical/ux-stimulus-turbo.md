---
description: Decision tree — Stimulus vs Turbo, manual controller registration, and how to wrap JS libraries.
paths:
  - "**/assets/**/*.js"
  - "**/templates/**/*.twig"
  - "**/src/Twig/Components/**/*.php"
---

# Interactivity — Stimulus & Turbo

## ⚠️ Controllers are registered BY HAND

This project does **not** use Stimulus auto-discovery. `assets/controllers.json` enables only
`@symfony/ux-turbo`'s `turbo-core`. Every application controller is imported and registered
explicitly in `assets/bootstrap.js`:

```js
import MotusController from './controllers/motus_controller.js';
const app = Application.start();
app.register('motus', MotusController);
```

**A controller file that isn't registered there simply never runs — silently, with no console error.**
That is not hypothetical: `recipe_chat_controller.js` and `hello_controller.js` sit in
`assets/controllers/` unregistered and dead.

So creating a Stimulus controller is always **two** edits: the controller file, **and** the import +
`app.register()` line in `bootstrap.js`. Verify by loading the page, not by reading the file.

The `data-controller` identifier is `kebab-case` (`app-detail-drawer`) while the file is `snake_case`
(`app_detail_drawer_controller.js`) — the mapping is manual, so it must match what you register.

## Decision tree

**Two** layers are available here, not three.

### 1. Stimulus — client-side behaviour, no server round-trip
Use it for DOM interaction driven by the browser: puzzle state, keyboard input, drag, toggles,
observers, `fetch` against a JSON endpoint.

All four live controllers are this shape:
- `taquin` — board state and tile moves, entirely client-side.
- `motus` — grid rendering, key handling, `fetch` to `motus_guess` for validation.
- `cookbook` — filters, sorting and IntersectionObserver infinite scroll against
  `cookbook_recipes_json`.
- `app-detail-drawer` — open/close of the shared drawer.

Conventions to match: `static targets` / `static values`, ES **private fields** (`#observer`,
`#debounceTimer`, `#loading`) for internal state, and cleanup in `disconnect()` — an
IntersectionObserver or a timer left running leaks across Turbo navigations.

### 2. Turbo — navigation and page-level updates
`@symfony/ux-turbo` is enabled. Use Turbo for full-page navigation and progressive enhancement of
links/forms. Because Turbo keeps the JS runtime alive across navigations, controllers **must** clean
up in `disconnect()`.

### ❌ Live Components are not available
`symfony/ux-live-component` is **not installed**. Don't reach for `#[AsLiveComponent]`, `data-live-*`
or `LiveAction` — they will not work. If a feature genuinely needs server-driven reactivity, that is
a dependency decision to raise explicitly, not to slip into a branch.

## Wrapping a JS library
Any third-party JS library is **wrapped in a Stimulus controller** — never a bare `<script>` tag and
never inline JS in a template:

1. `npm install` the library (declared in `package.json`, not transitive).
2. Import it inside a controller under `assets/controllers/`.
3. Initialise in `connect()`, tear down in `disconnect()`.
4. Register it in `assets/bootstrap.js`.
5. Configure via `static values` / `data-*` attributes from Twig — no hardcoded config in the
   controller.

## See also
- Templates and asset loading: [frontend-twig.md](frontend-twig.md)
- Styling & the JS-applied-class purge trap: [frontend-styling.md](frontend-styling.md)
- The JSON endpoints these controllers call: [../business/radiant.md](../business/radiant.md)
