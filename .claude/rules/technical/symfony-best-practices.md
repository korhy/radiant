---
description: Official Symfony framework best practices, reconciled with this project's stack. Applies to controllers, services, config, forms, templates, security and tests.
paths:
  - "**/*.php"
  - "**/config/**/*.yaml"
  - "**/templates/**/*.twig"
  - "**/translations/**"
---

# Symfony best practices

> The official list: <https://symfony.com/doc/current/best_practices.html>. These are the framework
> authors' recommendations. This file restates them **as rules for this repo** and reconciles the
> points where a deliberate project choice overrides the default. Where an existing rule already
> covers a topic in more detail, follow that rule — links under each section.

## Project reconciliation (read first)

Two best practices are **intentionally overridden** — do **not** "fix" the code toward the Symfony
default here:

- **Web assets → Webpack Encore, not AssetMapper.** Symfony now recommends AssetMapper, and
  `symfony/asset-mapper` *is* installed alongside `importmap.php` and `assets/vendor/` — but those
  are **dormant leftovers**. The live build is **Webpack Encore** (`webpack.config.js`,
  `encore_entry_*_tags` in `base.html.twig`). Build against Encore; don't migrate to AssetMapper or
  propose it as cleanup.
- **No internationalization layer.** Symfony recommends XLIFF catalogues and translation keys. This
  project writes **French directly in the templates**; `translations/` is empty. Don't introduce
  `trans` filters or catalogues as a side effect of another change — see
  [../business/radiant.md](../business/radiant.md).

Interactivity uses **Stimulus + Turbo** only. **Live Components are not installed** — see
[ux-stimulus-turbo.md](ux-stimulus-turbo.md).

## Creating the project
- **Default directory structure.** Keep the flat, standard layout (`src/`, `config/`, `templates/`,
  `public/`…). Don't reorganize into custom top-level folders.

## Configuration
- **Environment variables for infrastructure config** (DB URL, mailer DSN, third-party API URLs) —
  values that change per machine. Real values in `.env.local`, never committed. See
  [security.md](security.md).
- **Parameters for application config** — values identical on every machine (feature toggles, a
  sender address) go in `config/services.yaml` under a **short, `app.`-prefixed** name
  (`app.some_option`) to avoid collisions.
- **Constants for options that rarely change** — class constants rather than parameters when they're
  effectively fixed (`MotusService::WORDS` is the existing example).

## Business logic
- **No bundles for your own application code.** Organize under the `App\` namespace in `src/`.
- **Autowiring + autoconfiguration.** Rely on the default `services.yaml`; inject by type-hint.
  See [backend-php.md](backend-php.md).
- **Services are private.** Don't fetch services via `$container->get()`; inject them.
- **Doctrine mapping via PHP attributes** (`#[ORM\*]`) on the entity — already the case; keep it.

## Controllers
- **Extend `AbstractController`.** Use its shortcuts (`render`, `redirectToRoute`, `isGranted`,
  `addFlash`, `createForm`, `isCsrfTokenValid`).
- **Attributes for routing / caching / security** — `#[Route]`, `#[IsGranted]`, `#[Cache]` on the
  action, not YAML/XML config files. See [security.md](security.md).
- **Dependency injection, not the container.** Type-hint services as action/constructor arguments;
  env values via `#[Autowire(env: …)]`.
- **Entity Value Resolver** — type-hint the entity to auto-fetch it and get a 404 when missing. When
  the lookup needs real query logic (like `findBySlug`), go through the repository instead.
- **Thin controllers.** Orchestration only — no business logic, no Doctrine queries in the
  controller. See [backend-php.md](backend-php.md).

## Templates
- **Snake_case template names and variables** (`app/cookbook/index.html.twig`, `{{ app_detail }}`).
  See [naming.md](naming.md).
- **Prefix partial/fragment templates with an underscore** (`_app_detail_drawer.html.twig`,
  `_legend.html.twig`). The project already follows this in `templates/components/`; keep it — and
  note the Stream Deck's dynamic `_icon_<slug>.html.twig` lookup depends on the exact name.
- Reuse partials over duplicated markup — see [frontend-twig.md](frontend-twig.md).

## Forms
- **Forms are PHP classes** (`src/Form/Type/**`, one per use case), not built inline in controllers.
- **Buttons live in the template**, not in the form type, so a form stays agnostic to where it's used.
- **Validation constraints on the underlying object** (`#[Assert\*]` on the entity / DTO), so the
  rules are reused everywhere the object is — `ContactDTO` is the model. For unmapped fields, put the
  constraints in the field's `constraints` option. Custom business rules become constraints under
  `src/Validator` (to be created on first need).
- **One action renders and processes the form** (`handleRequest` + `isSubmitted() && isValid()`), not
  separate new/create actions.
- **Render fields with `{{ form_row(...) }}`**, not split `form_label` + `form_widget` +
  `form_errors`. `form_row` applies the configured form theme (`tailwind_2_layout.html.twig`);
  splitting the parts bypasses it. Wrap `form_row` in a layout `<div>` for spacing; pass field tweaks
  through the type or `form_row`'s variables.

## Security
- **A single (real) firewall.** The `main` firewall handles auth; the `dev` entry only disables
  security for profiler/assets. Don't add firewalls unless there are genuinely two auth systems.
- **`auto` password hasher** (already configured) — never store or compare plaintext.
- **Voters for fine-grained / object-level authorization.** When access depends on the object or the
  logic gets complex, write a Voter instead of inlining checks. See [security.md](security.md).

## Tests
- **Smoke-test your URLs.** A PHPUnit data-provider test asserting each key route returns a
  successful status is the cheapest possible safety net — and this project has **none**, so it is the
  highest-value test to add first. Cover at least `/`, `/app/taquin`, `/app/motus`, `/app/cookbook`,
  `/contact`, `/legal`.
- **Hard-code URLs in functional tests** (the literal path, not `generateUrl()`), so renaming a route
  makes the test fail and forces you to add a redirect. See [testing.md](testing.md).

## See also
- Backend / Symfony conventions: [backend-php.md](backend-php.md)
- Security: [security.md](security.md) · Naming: [naming.md](naming.md)
- Templates / components: [frontend-twig.md](frontend-twig.md) · [components-shadcn.md](components-shadcn.md)
- Assets & interactivity: [frontend-styling.md](frontend-styling.md) · [ux-stimulus-turbo.md](ux-stimulus-turbo.md)
- Testing: [testing.md](testing.md) · Deployment: [deployment.md](deployment.md)
