# T005 — Inventaire des occurrences à renommer

Relevé le 2026-08-19, avant toute modification. Voir [research.md](../research.md) R2.

## outline-none → outline-hidden — 4 occurrence(s)

- `templates/app/cookbook/index.html.twig:24` — `focus:outline-none`
- `templates/app/cookbook/index.html.twig:28` — `focus:outline-none`
- `templates/contact/index.html.twig:30` — `focus:outline-none`
- `templates/security/login.html.twig:29` — `focus:outline-none`

## shadow → shadow-sm — 2 occurrence(s)

- `templates/contact/index.html.twig:8` — `shadow`
- `templates/security/login.html.twig:8` — `shadow`

## bordure sans couleur (gray-200 → currentColor) — 18 occurrence(s)

- `templates/app/cookbook/index.html.twig:24` — `border`
- `templates/app/cookbook/index.html.twig:28` — `border`
- `templates/app/cookbook/index.html.twig:45` — `border`
- `templates/app/cookbook/index.html.twig:49` — `border`
- `templates/app/cookbook/index.html.twig:53` — `border`
- `templates/base.html.twig:33` — `border-t`
- `templates/components/_app_detail_drawer.html.twig:36` — `border-b`
- `templates/components/_app_detail_drawer.html.twig:56` — `border-b`
- `templates/components/_app_detail_drawer.html.twig:108` — `border`
- `templates/components/_app_detail_drawer.html.twig:125` — `border-l-2`
- `templates/contact/index.html.twig:8` — `dark:border`
- `templates/contact/index.html.twig:22` — `border`
- `templates/portfolio/header/streamdeck.html.twig:7` — `border`
- `templates/portfolio/section/experience.html.twig:26` — `border-2`
- `templates/portfolio/section/projects.html.twig:30` — `border-2`
- `templates/security/login.html.twig:8` — `dark:border`
- `templates/security/login.html.twig:19` — `border`
- `templates/security/login.html.twig:24` — `border`

**Total : 24 occurrences.**

> La bordure sans couleur est la plus dangereuse des trois : elle ne change pas de nom,
> seulement de teinte par défaut. Aucun diff ne la montrera.