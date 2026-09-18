---
description: Project-specific business rules — domain model, Stream Deck mini-apps, roles, naming & folder conventions.
paths:
  - "**/*"
---

# Radiant — business rules

**Radiant** is a personal portfolio built with Symfony 7.4. It serves a database-driven CV
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
- **SiteContent** (`aboutText`, `cvFile`/`cvFileName`/`cvOriginalName`, `updatedAt`) — **une seule
  ligne**, amorcée par migration. Porte le paragraphe « À propos » de la page d'accueil, marqueurs
  `**…**` compris, et le CV proposé au téléchargement. `#[Vich\Uploadable]`, mapping `cv` ; le binaire
  vit sous `public/documents/CV/`, **ignoré par git** — c'est ce qui le fait survivre au
  `reset --hard` du déploiement. Lire la ligne par `SiteContentRepository::findCurrent()`, jamais
  autrement ; `NEW` et `DELETE` restent désactivées dans le CRUD.
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

**How the API is reached in local dev.** Cookbook runs in its own Docker stack. Both stacks join an
external network, **`korhy_net`**, so `COOKBOOK_API_URL` is `http://cookbook_app` — the container
name, on port 80. It is **not** `127.0.0.1:8001`: from inside this container that address resolves
to *this* container, and the call fails. `make net` (a prerequisite of `make up`) creates the
network. Cookbook does not have to be running — when it is down the client raises
`CookbookUnavailableException`.

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

- **La carte recette est écrite deux fois** — en Twig et en littéral JS dans
  `cookbook_controller.js` —, et cette version JS interpole les champs de l'API sans échappement.
- **Le formulaire de contact** part avec l'adresse du visiteur en `From` (SPF/DKIM), et n'a ni
  rate limiting ni anti-spam.
- **Les messages de validation s'affichent en anglais** (`default_locale: en`) sur un site français.
- **Les colonnes JSON `tags`** emballent le tableau dans une clé `tags` redondante.
- **Le CV hérité est encore versionné.** `public/documents/CV/CV_Clement_BOUDINEL_Fullstack_PHP-Symfony.pdf`
  amorce `SiteContent.cvFileName` et doit le rester tant qu'aucun CV n'a été déposé depuis
  l'administration en production : le déploiement remet l'arbre à l'état de `main` **avant** de jouer
  les migrations, donc le retirer plus tôt laisserait la ligne pointer sur un fichier absent. Une
  fois un dépôt fait, **le retirer du dépôt par un commit dédié** — sinon le `reset --hard` le
  restaure et il reste accessible à son ancienne URL. Contexte : `specs/008-editable-about-cv/research.md`, R4.
- **`public/documents/CV/CV_champs_formulaires_en_ligne.md` est versionné et servi publiquement.**
  Document de travail, accessible à qui devine l'URL. À trancher.

Ce qui a été corrigé et ne doit pas être re-signalé : `declare(strict_types=1)` (imposé par
php-cs-fixer), la casse des propriétés d'entité, la typo `$projetcs`, le code mort (AssetMapper,
Platform.sh, contrôleurs Stimulus non enregistrés), l'accessibilité des mini-apps, l'absence de
tests, l'absence de gate Twig (twig-cs-fixer depuis le 2026-08-19), et les failles de dépendances
(constat **S9**, `composer audit` à 0 depuis la montée Symfony 7.4). Étapes 5 à 7 closes
le 2026-08-20 : Tailwind 4, tokens, thème clair, kit shadcn, le README refait à partir du dépôt,
puis la composantisation de l'en-tête de section, de la pastille de tag et du ⓘ d'aide
(`SectionHeader`, `Badge`, `InfoDisclosure`). **DU1** reste seul ouvert.
