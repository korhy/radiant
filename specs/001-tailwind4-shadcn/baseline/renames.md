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

**Total : 6 occurrences.**

> **Le relevé initial annonçait 18 bordures sans couleur.** Vérification faite par élément et non
> par jeton, il y en a **zéro** : toutes portent déjà un `border-<couleur>` sur le même élément.
> La catégorie est supprimée de cet inventaire.
>
> Les 4 `focus:outline-none` sont le seul point sensible : sous Tailwind 4, `outline-none` supprime
> réellement le contour de focus. Le préfixe `focus:` doit être **conservé** au renommage — le
> perdre appliquerait la suppression en permanence.