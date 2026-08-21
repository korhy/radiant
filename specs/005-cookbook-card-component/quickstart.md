# Quickstart — vérifier la carte recette unique

**Feature**: [spec.md](spec.md) · **Plan**: [plan.md](plan.md) · **Date**: 2026-08-21

La CI ne voit ni le JavaScript ni le CSS, et aucun test visuel n'existe. Ce document dit donc
exactement **ce qui est prouvé par un test** et **ce qui doit être regardé à la main**.

## Prérequis

```bash
make up
npm run dev
```

L'API Cookbook de développement doit tourner pour les parcours réels
(`COOKBOOK_API_URL`, `http://127.0.0.1:8001` en local). Les tests, eux, n'en ont pas besoin :
ils passent par `MockHttpClient`.

## 1. Le gate

```bash
make ci
```

Doit rester vert : php-cs-fixer, twig-cs-fixer, PHPStan level 5, PHPUnit.

## 2. Ce que les tests prouvent

| Ce qui est prouvé | Test |
|---|---|
| Les deux chemins rendent **le même balisage** à données égales (SC-003, FR-006) | `CookbookCardTest::testBothRenderPathsProduceTheSameCard` |
| Un titre piégé s'affiche littéralement (US2, SC-002) | `CookbookCardTest::testRecipeFieldsAreEscaped` |
| Le point d'accès renvoie du **HTML**, pas des données (FR-002) | `CookbookCardTest::testTheEndpointServesRenderedMarkup` |
| L'état vide vient du serveur (FR-010) | `CookbookCardTest::testAnEmptyResultSetServesTheEmptyState` |
| Le contrat de dégradation ne bouge pas (FR-007) | les deux tests existants de `PublicRoutesTest`, **sans modification** |

> Le premier test est le garde-fou du lot : il échoue si quelqu'un réintroduit un rendu de carte
> côté client. Les autres tests seraient encore verts dans ce cas, pas celui-là.

## 3. Ce qui se vérifie à la main

API Cookbook lancée, sur `/app/cookbook` :

| # | Geste | Attendu |
|---|---|---|
| 1 | Charger la page, faire défiler jusqu'à charger **deux pages** | Les cartes ajoutées sont indiscernables des premières |
| 2 | Repérer une recette **sans vignette** dans chaque groupe | Même repli : l'icône Cookbook, jamais un cercle plein |
| 3 | Chercher une chaîne sans correspondance | Message « aucun résultat », mise en page identique à celle du premier rendu |
| 4 | Trier par durée, puis par titre | La grille se recompose, le défilement repart de la page 1 |
| 5 | Cliquer une carte chargée par défilement | La fiche s'ouvre — l'adresse n'est plus recomposée à la main |
| 6 | Arrêter l'API, recharger, puis faire défiler | Message d'indisponibilité, **jamais** « aucun résultat » |

Aux trois largeurs (mobile 375, tablette 768, bureau 1280) et dans les **deux thèmes**.

## 4. Accessibilité

```bash
# axe-core sur la page, avant et après un chargement par défilement
```

- **0 violation** axe-core sur `/app/cookbook`, thème clair et thème sombre, avant et après
  défilement (SC-005).
- Au clavier : chaque carte est atteignable, son nom annoncé est le titre de la recette, l'anneau de
  focus est visible dans les deux thèmes.
- Au lecteur d'écran : le chargement d'une page supplémentaire est annoncé, **sans** que le focus se
  déplace (US4).

## 5. Le contrôle qui compte le plus

Modifier **une seule** classe visuelle de `templates/components/RecipeCard.html.twig` — par exemple
le rayon des angles — puis recharger et faire défiler.

**La modification doit se voir sur les cartes des deux groupes.** Si elle n'apparaît que sur les
premières, la duplication n'a pas été supprimée : elle a été déplacée. C'est SC-001, et aucun test
automatique ne le remplace.
