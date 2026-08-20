# Phase 1 — Modèle de données

**Feature**: [spec.md](spec.md) · **Date**: 2026-08-21

**Aucune entité Doctrine, aucune migration.** Ce lot ne touche pas la base : les recettes vivent dans
l'API Cookbook, Radiant les affiche. Le « modèle » ici est la **forme du tableau** que le composant
reçoit, c'est-à-dire un élément de `member` dans la réponse Hydra de l'API.

## Recette, vue liste

| Champ | Type | Présence | Emploi dans la carte |
|---|---|---|---|
| `id` | entier | toujours | Alimente `path('cookbook_recipe', {id: …})`. |
| `title` | chaîne | toujours | Titre de la carte, et nom accessible du lien. |
| `thumbnail` | chaîne (URL) \| absent \| `null` | facultatif | Vignette. Absent ⇒ repli sur l'icône Cookbook. |
| `category` | objet `{ id, name }` \| absent \| `null` | facultatif | Pastille de catégorie, rendue par `<twig:Badge>`. Absent ⇒ pas de pastille. |
| `duration` | entier (minutes) \| absent \| `null` \| `0` | facultatif | Durée. Absent ou `0` ⇒ pas de mention de durée. |

**Règle de présence** : les trois champs facultatifs le sont **des deux côtés**. Le défaut corrigé
par ce lot vient précisément de ce que le rendu client et le rendu serveur ne traitaient pas leur
absence de la même façon.

**Aucune validation** n'est faite sur ces champs : ils viennent d'une API maîtrisée, et le composant
absorbe l'absence plutôt que de la refuser. Ce qui est garanti, ce n'est pas leur forme, c'est que
leur **contenu ne peut pas altérer la page** — parce qu'il est traité comme du texte.

## Page de résultats

Structure de travail du contrôleur, non persistée.

| Champ | Type | Rôle |
|---|---|---|
| `recipes` | liste de recettes | La tranche servie pour la page demandée (`member` côté API). |
| `hasNextPage` | booléen | Déduit de la présence de `view.next` dans la réponse Hydra. |
| `nextPage` | entier \| `null` | Page suivante à demander. |

## Ce qui n'entre pas dans le modèle

Les champs de la fiche détaillée — ingrédients, étapes, image pleine taille — ne sont pas servis par
la liste et ne concernent pas ce lot.
