# Implementation Plan: Carte recette unique pour le Cookbook

**Branch**: `005-cookbook-card-component` | **Date**: 2026-08-21 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/005-cookbook-card-component/spec.md`

## Summary

Déplacer le rendu de la carte recette **entièrement côté serveur**, dans un composant unique
(`<twig:RecipeCard>`), et faire du point d'accès du défilement infini un transporteur de **balisage
déjà rendu** au lieu de données brutes. Le contrôleur Stimulus cesse d'assembler du HTML.

La recherche a déplacé deux choses par rapport à la spec :

- **Le périmètre est plus large d'un cran.** Le balisage composé par le navigateur ne se limite pas
  à la carte : l'état « aucun résultat » est une **troisième** définition, écrite en JavaScript, et
  elle a déjà divergé du gabarit. La corriger coûte le même geste (D3).
- **L'échappement n'est pas une tâche.** Une fois le rendu déplacé côté serveur, **S3 disparaît sans
  code dédié** : Twig échappe, y compris dans `alt`. Aucune tâche ne portera « échapper les champs » ;
  la tâche est « ne plus rendre côté client », et un test le prouve.

Le kit shadcn a une recette `card`, et elle n'est **pas** retenue : ses quatre classes déterminantes
sont toutes écrasées ici, et la carte recette est un lien de bout en bout à vignette pleine largeur,
pas un conteneur à remplir. La pastille de catégorie, elle, passe au `<twig:Badge>` déjà installé
(D1).

Découpage en trois tranches livrables séparément : le composant et le premier écran (P1), le canal
du défilement avec l'état vide (P1), l'annonce aux technologies d'assistance (P3).

## Technical Context

**Language/Version**: PHP 8.2 (plafond de la CI), Twig 3, JavaScript ES2020

**Primary Dependencies**: `symfony/ux-twig-component` · `symfony/ux-toolkit:^2.36` (dev, kit shadcn) ·
`tales-from-a-dev/twig-tailwind-extra` (`tailwind_merge`) · Tailwind 4 via `@tailwindcss/postcss` ·
Webpack Encore · Stimulus. **Aucune dépendance ajoutée.**

**Storage**: PostgreSQL 16 — **non touché**. Les recettes viennent de l'API Cookbook ; aucune
migration, aucune entité.

**Testing**: PHPUnit (45 tests). Le lot en ajoute quatre, dont un test de non-divergence entre les
deux chemins de rendu. Le rendu visuel reste vérifié à la main ([quickstart.md](quickstart.md)).

**Target Platform**: navigateurs de bureau et mobiles ; serveur mutualisé OVH

**Project Type**: application web Symfony rendue côté serveur

**Performance Goals**: pas de régression perceptible sur le chargement d'une page supplémentaire
(SC-006). Le HTML rendu pèse plus que le JSON équivalent — ~10 cartes par page, effet attendu
négligeable, à constater une fois plutôt qu'à supposer.

**Constraints**: aucun contrôle automatisé du rendu, ni linter JS/CSS · les assets sont construits
par la CI · le déploiement part tout seul au merge sur `main` · le contrat de dégradation acquis au
lot S4/S8 ne doit pas bouger

**Scale/Scope**: 3 routes Cookbook · 1 gabarit de liste (133 lignes) · 1 contrôleur Stimulus
(172 lignes, dont ~50 à supprimer) · 1 contrôleur PHP (101 lignes) · **1 seul écran** à vérifier,
en 3 largeurs × 2 thèmes × 2 états (avant et après défilement)

## Constitution Check

*GATE — évalué avant la Phase 0, réévalué après la Phase 1.*

| Non-négociable | Applicable ? | Verdict |
|---|---|---|
| 1. Typage strict, PHP 8.2 | oui | ✅ Le contrôleur touché porte déjà `declare(strict_types=1)` ; aucune classe PHP nouvelle n'est prévue (le composant est anonyme, sans classe). |
| 2. Sécurité par Symfony | oui | ✅ Aucune route nouvelle, aucune exposition modifiée. Le lot **referme S3** par construction. Aucun secret, aucun journal touché. |
| 3. Couches | oui | ✅ Aucune requête Doctrine (données d'API), aucune logique métier ajoutée au contrôleur : il rend un fragment, ce qui est de l'orchestration. |
| 4. Identifiants anglais, UI française | oui | ✅ `RecipeCard`, prop `recipe`. Le texte visible — état vide, phrase d'annonce — reste **dans les gabarits**, y compris la phrase transportée par le JSON (D4). |
| 5. Migrations sûres | non | — Aucune migration. |
| 6. Conventional Commits | oui | ✅ `refactor(front)` pour le composant, `fix(front)` pour la tranche qui referme S3. |
| 7. Accessibilité, critère d'acceptation | oui | ✅ FR-008 et FR-009. La tranche P3 **corrige** un écart préexistant (chargement muet) plutôt que de le reconduire. |
| 8. Le gate passe | oui | ✅ `make ci` plus la vérification manuelle décrite dans le quickstart, JS et CSS n'ayant pas de gate. |

**Contraintes de planification** :

- *Tailwind 4 et le kit* — sans objet, les deux sont en place depuis l'Étape 5.
- *Suite de tests mince* — le lot dit lequel de ses tests prouve quoi ([quickstart.md](quickstart.md)).
  Le test qui compte est celui de **non-divergence** : les autres resteraient verts si la carte
  redevenait cliente, pas lui.
- *Régressions front invisibles à la CI* — un seul écran est touché ; les six gestes à refaire et les
  largeurs sont listés dans le quickstart.

**Verdict avant Phase 0 : passe, sans dérogation.**

## Project Structure

### Documentation (this feature)

```text
specs/005-cookbook-card-component/
├── plan.md              # Ce fichier
├── research.md          # Phase 0 — les quatre décisions
├── data-model.md        # Phase 1 — la forme d'une recette en vue liste
├── quickstart.md        # Phase 1 — ce que les tests prouvent, ce qui se regarde
├── contracts/
│   ├── recipes-endpoint.md      # GET /app/cookbook/recipes
│   └── recipe-card-component.md # <twig:RecipeCard>
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 — produit par /speckit-tasks, pas ici
```

### Source Code (repository root)

```text
templates/
├── components/
│   ├── RecipeCard.html.twig          # ← créé : l'unique définition de la carte
│   └── Badge.html.twig               # réutilisé tel quel (installé au lot DU2)
└── app/cookbook/
    ├── index.html.twig               # ← la boucle appelle le composant ; région role="status"
    ├── _recipe_grid_items.html.twig  # ← créé : le fragment rendu pour le défilement
    └── _recipe_grid_empty.html.twig  # ← créé : l'état « aucun résultat », un seul endroit

src/Controller/
└── CookbookController.php            # ← recipesJson() rend le fragment au lieu de sérialiser

assets/controllers/
└── cookbook_controller.js            # ← perd #cardHtml et l'état vide en chaîne (~50 lignes)

tests/
├── Controller/
│   └── CookbookCardTest.php          # ← créé : non-divergence, échappement, état vide
└── Smoke/PublicRoutesTest.php        # inchangé — les deux tests de dégradation restent tels quels
```

**Structure Decision** : application web Symfony, rendu serveur. Aucune classe PHP nouvelle : le
composant est **anonyme** (`templates/components/`), puisqu'il n'a pas de logique à porter — la
donnée arrive du contrôleur, le gabarit absorbe les champs absents. Les deux fragments sont des
partiels préfixés d'un tiret bas parce qu'ils ne prennent **aucune variation** : ils bouclent, ou
affichent un message.

## Tranches

### P1a — Le composant et le premier écran

Créer `RecipeCard`, l'appeler dans la boucle du gabarit de liste, supprimer le balisage de carte
qu'il remplace. La pastille de catégorie passe à `<twig:Badge>`.

Livrable seule et vérifiable seule : le premier écran doit être **pixel pour pixel** ce qu'il était.
À ce stade le défilement rend encore ses propres cartes — la divergence est visible, c'est attendu.

### P1b — Le canal, l'état vide, et S3

`recipesJson()` rend `_recipe_grid_items.html.twig` (ou `_recipe_grid_empty.html.twig`) et renvoie
`html`, `empty`, `hasNextPage`, `nextPage`. Le contrôleur Stimulus perd `#cardHtml` et sa chaîne
d'état vide. Le 503 ne bouge pas.

C'est la tranche qui referme **DU1** et **S3**. Les quatre tests arrivent ici, dont celui de
non-divergence.

### P3 — L'annonce

Région `role="status"` dans le gabarit, champ `announcement` dans la réponse, une ligne dans le
contrôleur Stimulus pour la recopier. Détachable : si la tranche saute, P1a et P1b restent complètes
et cohérentes.

## Complexity Tracking

Aucune dérogation à la constitution. Deux écarts de convention, assumés et justifiés :

| Écart | Pourquoi | Alternative rejetée parce que |
|---|---|---|
| Ne pas installer la recette `card` du kit | Ses quatre classes déterminantes sont écrasées ; la carte est un lien à vignette pleine largeur, pas un conteneur | Installer `Card` ajouterait un niveau de DOM et une feuille de classes à neutraliser, pour ne garder que le nom (règle : *prendre un composant pour ce qu'il fait, pas pour son nom*) |
| Du texte français transporté par une réponse JSON (`announcement`) | La phrase est **rendue par Twig** ; le JSON ne fait que la transporter jusqu'à une région qui doit préexister | La composer en JavaScript mettrait du texte visible hors des gabarits et imposerait de gérer l'accord du pluriel à la main |

## Constitution Check — réévaluation après Phase 1

Rien n'a bougé sur les huit non-négociables. Deux points confirmés par la conception :

- **Couches (3)** — le contrôleur rend un fragment et renvoie une charge ; aucune logique de
  présentation ne descend en PHP, la mise en forme reste dans les gabarits.
- **Accessibilité (7)** — le contrat du composant nomme explicitement ce qui doit être vrai : nom
  accessible du lien, vignette de repli non annoncée, contraste préservé dans les deux thèmes.

**Verdict après Phase 1 : passe.** Prêt pour `/speckit-tasks`.
