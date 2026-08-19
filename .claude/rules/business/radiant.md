---
description: Project-specific business rules — domain model, Stream Deck mini-apps, roles, naming & folder conventions.
paths:
  - "**/*"
---

# Radiant — business rules

**Radiant** is a personal portfolio built with Symfony 7.3. It serves a database-driven CV
(experiences, personal projects) and a **"Stream Deck"** — a grid of self-contained mini-apps
(Taquin, Motus, Cookbook) reachable from the homepage. A back-office built on **EasyAdmin 4** is the
only way content is edited. The site is a **showcase of the author's development skills**: code
quality on display matters as much as the feature itself.

## Domain model (entities under `src/Entity`)

- **Admin** (`username`, `roles`, `password`, `email`) — the single login account.
  Implements `UserInterface` + `PasswordAuthenticatedUserInterface`; the provider in
  `security.yaml` resolves on **`username`**, not email.
- **App** (`slug`, `label`, `route`, `position`, `description`, + 4 JSON columns `techStack`,
  `challenges`, `improvements`, `resources`) — one row per mini-app. Drives both the Stream Deck
  grid and the "Behind the scenes" drawer. `slug` is unique; `position` orders the grid
  (`AppRepository::findAllOrderedByPosition()`); `route` holds a **Symfony route name**, resolved
  with `path()` in Twig.
- **Experience** (`company`, `position`, `description`, `url`, `startDate`, `endDate`, `tags`) —
  a CV entry.
- **PersonalProject** (`name`, `description`, `url`, `file`/`fileName`, `tags`, `updatedAt`) —
  `#[Vich\Uploadable]`, mapping `personal_projects`; the binary lives on the server filesystem
  under `public/images/personal_projects/`.

> **JSON-column accessors are deliberate.** `App` and `Experience` expose `getJsonX()`/`setJsonX()`
> string accessors purely so EasyAdmin can edit the raw JSON in a textarea. Don't "clean them up" —
> see [easyadmin.md](../technical/easyadmin.md).

## The Stream Deck contract (read before adding an App)

`templates/portfolio/header/streamdeck.html.twig` resolves each tile's icon **dynamically**:

```twig
{{ include('components/_icon_' ~ appEntry.slug ~ '.html.twig') }}
```

So **every `App` row requires a matching `templates/components/_icon_<slug>.html.twig`**. A row
without its partial throws on the homepage — not on the mini-app page, on the *homepage*. Likewise
`route` must name an existing route or `path()` fails there too.

Each mini-app page passes `app_detail` to its template and includes
`components/_app_detail_drawer.html.twig`; that shared drawer renders the four JSON columns.
Use `/new-app` to scaffold the whole set consistently.

## Cookbook — Radiant is a client, not the host

`src/Service/Cookbook/CookbookApiService.php` consumes an **external API Platform application**.
It authenticates against `POST {apiUrl}/api/login_check`, caches the JWT under `cookbook_api_token`
for 3500 s, retries **once** after dropping the cached token on a 401, and reads Hydra shapes
(`$data['member']`, `$data['view']['next']`). Credentials arrive through
`#[Autowire(env: 'COOKBOOK_API_*')]`.

Never reimplement recipe logic locally, never persist recipes in Radiant's database, and never log
the token. If the API contract changes, the fix belongs in that service.

## Roles & access

- `ROLE_ADMIN` — the only meaningful role; `access_control` gates `^/admin`.
- Authentication is `form_login` with CSRF enabled, routes `app_login` / `app_logout`.
- There are **no voters yet**. The day authorization depends on the object, write a voter rather
  than inlining a check — see [security.md](../technical/security.md).

## Conventions in this repo

- **Route names carry no prefix**: `homepage`, `taquin`, `cookbook`, `cookbook_recipes_json`,
  `motus`, `motus_guess`, `contact`, `legal`. Only `app_login`/`app_logout` keep the `app_` prefix
  inherited from `make:auth`. Follow the surrounding controller rather than inventing a scheme.
- **Controllers** live flat in `src/Controller`, except the EasyAdmin ones under
  `src/Controller/Admin`. Un contrôleur par mini-app depuis le 2026-08-19 : `TaquinController`,
  `MotusController`, `CookbookController` — `ApplicationController` les agrégeait tous.
- **Services** go under `src/Service/<Domain>/`, stateless where possible (`MotusService` is a
  good model: a private word list, pure functions, no state).
- **Templates**: `templates/app/<slug>/` per mini-app, `templates/portfolio/**` for the homepage
  sections, shared partials in `templates/components/` prefixed with `_`.
- **JSON endpoints** are plain controller actions returning `JsonResponse` (`cookbook_recipes_json`,
  `motus_guess`) — there is no API Platform here.
- **Language**: code identifiers are **English** (see [naming.md](../technical/naming.md)); the UI is
  **French, hardcoded in the templates**. `translations/` holds no catalogue even though
  `default_locale` is `en`. Don't introduce translation keys as a side effect of another change —
  that's a deliberate decision to take on its own.
- **Commits follow Conventional Commits** and are load-bearing: `release.yml` parses the subject to
  auto-tag semver. See [deployment.md](../technical/deployment.md).

## Notes / known rough edges (improve, don't propagate)

L'audit du 2026-08-18 (`docs/audit/audit-2026-08-18.md`) a traité les étapes 0 à 4. Ce qui reste :

- **`README.md` est périmé** : son tableau des routes et sa liste de fonctionnalités datent d'avant
  Cookbook et Motus, et son arborescence omet `src/Service/`, `src/DTO/` et `templates/components/`.
- **La carte recette est écrite deux fois** — en Twig et en littéral JS dans
  `cookbook_controller.js` —, et cette version JS interpole les champs de l'API sans échappement.
- **`/app/cookbook` renvoie une 500 si l'API Cookbook est injoignable** au lieu d'un état dégradé.
- **Le formulaire de contact** part avec l'adresse du visiteur en `From` (SPF/DKIM), et n'a ni
  rate limiting ni anti-spam.
- **Les messages de validation s'affichent en anglais** (`default_locale: en`) sur un site français.
- **Les colonnes JSON `tags`** emballent le tableau dans une clé `tags` redondante.
- **Migration Tailwind 3 → 4 *et* adoption du kit shadcn**, traitées comme un seul lot (Étape 5).

Ce qui a été corrigé et ne doit pas être re-signalé : `declare(strict_types=1)` (imposé par
php-cs-fixer), la casse des propriétés d'entité, la typo `$projetcs`, le code mort (AssetMapper,
Platform.sh, contrôleurs Stimulus non enregistrés), l'accessibilité des mini-apps, et l'absence de
tests.
