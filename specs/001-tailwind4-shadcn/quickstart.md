# Phase 1 — Guide de validation

**Feature**: Tailwind 4 et le kit shadcn · **Date**: 2026-08-19

Aucun contrôle automatisé ne couvre le rendu. Ce guide **est** l'instrument de validation.

## Prérequis

```bash
make up
```

La base doit contenir les trois mini-apps avec leurs colonnes JSON remplies, sans quoi le panneau
« Behind the scenes » s'affiche vide et ne prouve rien. L'API Cookbook doit être joignable, ou la
page `/app/cookbook` renverra une 500 sans rapport avec ce lot (constat **S8**).

## Étape 0 — Le relevé de référence, à faire AVANT de toucher au moteur

Sans lui, SC-001 n'est pas vérifiable et le lot ne peut pas être validé.

```bash
npm run build
```

Puis capturer les **huit pages publiques** aux **trois largeurs** (375, 768, 1280) :
`/`, `/app/taquin`, `/app/motus`, `/app/cookbook`, `/app/cookbook/recipe/<id>`, `/contact`,
`/mentions-legales`, `/login`. Soit **24 captures**, rangées sous
`specs/001-tailwind4-shadcn/baseline/`.

`/admin` **n'est pas une page publique** : anonymement il renvoie 302 vers `/login`. Le capturer
séparément, connecté — c'est le contrôle du contrat C5, pas de la non-régression du site.

Relever aussi le poids de la feuille livrée, **brut et compressé** — c'est le second qui fait foi :

```bash
ls -l public/build/app.*.css && gzip -9 -c public/build/app.*.css | wc -c
```

## Étape 1 — Après la bascule du moteur (US1)

```bash
npm run build && make ci
```

Recapturer les 24 écrans et comparer à la référence. Puis exercer ce qu'une capture ne montre pas :

- **Motus** — jouer un mot et vérifier les trois états, couleur **et** relief non chromatique. C'est
  le contrôle de C4 : ces classes viennent du JavaScript.
- **Cookbook** — faire défiler jusqu'au chargement d'une nouvelle page de recettes ; les cartes
  ajoutées à l'exécution doivent être stylées comme les initiales.
- **Taquin** — naviguer aux flèches, vérifier que le focus reste visible. C'est le contrôle des
  4 `outline-none` renommés (research.md R2).
- **Contact** — les champs gardent une bordure visible. C'est le contrôle des 18 bordures sans
  couleur explicite.
- **`/admin`** — inchangé (contrat C5).

## Étape 2 — Après les tokens et le thème clair (US2)

Rejouer l'étape 1 **dans les deux thèmes**, en basculant la préférence système. Soit 48 écrans,
plus `/admin` connecté dans les deux thèmes.

Le thème clair n'a pas de référence à comparer : il se contrôle **au contraste**, pas à l'œil.

```bash
# Vérifier chaque couple texte/fond du thème clair contre les seuils AA
# 4.5:1 texte normal · 3:1 grand texte et éléments d'interface
```

Le point dur est connu et mesuré : l'accent `#faa734` donne **1,97:1 sur blanc**. Il ne peut pas
servir de couleur de texte en thème clair (research.md R5).

Vérifier enfin SC-005 : changer la valeur du token d'accent en un seul endroit, reconstruire,
constater que tout le site suit. Puis annuler.

## Étape 3 — Après la reprise du panneau (US3)

Sur **chacune des trois** mini-apps, au clavier seul, en reprenant ligne à ligne le contrat C2 :

1. Atteindre le bouton d'ouverture à la tabulation, l'activer.
2. Le focus est entré dans le panneau et n'en sort pas.
3. Passer d'un onglet à l'autre aux flèches ; l'onglet actif est annoncé.
4. Échap ferme ; le focus est revenu sur le bouton d'ouverture.
5. Panneau fermé, tabuler d'un bout à l'autre de la page : **aucun** élément du panneau n'est
   atteignable.

Puis une passe automatisée d'accessibilité (axe ou Lighthouse), **dans les deux thèmes**, sur les
trois mini-apps.

## Étape 4 — Avant d'ouvrir la livraison

```bash
make ci
```

Et le contrôle qui n'a pas d'équivalent local : la construction des assets vient de l'intégration
continue, pas du poste de développement. Un rendu correct en local ne prouve rien sur ce qui sera
livré — c'est déjà ce qui avait fait échouer deux fois la CI sur ce projet.

## Critères de sortie

| Critère | Comment il est prouvé |
|---|---|
| SC-001 | 24 écrans comparés à la référence |
| SC-002 | Motus joué, captures converties en niveaux de gris |
| SC-003 | parcours clavier des trois mini-apps et du formulaire |
| SC-004 | passe axe/Lighthouse, deux thèmes |
| SC-005 | token d'accent modifié puis rétabli |
| SC-006 | `gzip -9 -c public/build/app.*.css | wc -c` sous 15 360 octets |
| SC-007 | tabulation panneau fermé, trois mini-apps |
| SC-008 | 48 écrans contrôlés, plus `/admin` connecté |
