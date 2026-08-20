# Phase 0 — Recherche : carte recette unique

**Feature**: [spec.md](spec.md) · **Date**: 2026-08-21

Trois inconnues à lever avant de planifier : **d'où vient la carte** (kit ou composant du projet),
**par quel canal** le défilement la reçoit, et **comment** l'ajout est annoncé aux technologies
d'assistance. Une quatrième question, apparue en cours de recherche, s'est révélée être le vrai
point dur : l'état « aucun résultat » est lui aussi composé par le navigateur.

---

## D1 — La carte : composant du projet, adossé au kit

**Décision** : écrire `templates/components/RecipeCard.html.twig` comme **composant du projet aux
conventions du kit**, et lui faire **réutiliser `<twig:Badge>`** (déjà installé au lot DU2) pour la
pastille de catégorie.

**Rationale** : le kit *a* une recette `card`, et la règle impose de partir du kit — mais son
`Card` est un `<div class="rounded-lg border bg-card text-card-foreground shadow-sm">`, dont les
**quatre classes déterminantes sont toutes écrasées** ici : la carte recette est en `rounded-2xl`,
`shadow-lg`, `bg-surface-raised`, sans bordure. Elle est en outre **un lien de bout en bout**, avec
une vignette pleine largeur en tête, quand le `Card` du kit est un conteneur neutre à remplir de
`Card:Header` / `Card:Content`. L'installer reviendrait à ajouter un niveau de DOM et une feuille de
classes à neutraliser, pour ne garder que le nom. C'est le cas prévu par la règle : *prendre un
composant pour ce qu'il fait, pas pour son nom*.

La pastille de catégorie, elle, **est** un Badge, et le Badge du kit est déjà dans le dépôt : la
carte le réutilise au lieu de recomposer `bg-brand/20 text-brand-fg-em … rounded-full`.

**Alternatives considérées** :

- **`ux:install card --kit=shadcn`** — rejetée pour les raisons ci-dessus. À rouvrir si une seconde
  surface en carte apparaît dans le site : la forme publique de `<twig:RecipeCard>` ne changerait
  pas, seule son intérieur.
- **Partiel `_recipe_card.html.twig`** (ce que proposait l'audit) — rejetée : le tiret bas est
  réservé à ce qui ne prend **aucune variation**, et cette carte en prend quatre (vignette,
  catégorie, durée, titre). Un composant documente ses props ; un partiel inclus à la main, non.

---

## D2 — Le canal : le point d'accès renvoie le HTML déjà rendu

**Décision** : `/app/cookbook/recipes` continue de répondre en **JSON**, mais transporte le
**balisage déjà rendu par le serveur** au lieu des données brutes :

```json
{ "html": "<a …></a><a …></a>", "empty": false, "hasNextPage": true, "nextPage": 3 }
```

Le contrôleur Stimulus injecte `html` tel quel. Il ne construit plus aucun balisage.

**Rationale** : c'est la seule forme qui satisfait FR-002 **sans toucher au contrat de dégradation**
acquis au lot précédent. La réponse garde sa forme JSON, donc le `503 { unavailable: true }` et les
deux tests qui le figent restent valides tels quels. Le HTML étant produit par Twig, l'échappement
est acquis par construction — y compris dans les attributs, ce qui referme **S3** sans qu'aucune
règle d'échappement ne soit à écrire ni à maintenir.

`insertAdjacentHTML` reste employé, et reste sûr : la chaîne vient du serveur, où Twig a échappé
chaque champ. L'API du navigateur n'exécute pas les `<script>` qu'on lui passe ; le risque venait de
l'interpolation, pas de la méthode.

**Alternatives considérées** :

- **Fragment `text/html` pur** (`Content-Type: text/html`, pagination en en-têtes) — plus proche du
  « HTML over the wire » canonique, mais déplace `hasNextPage`, `nextPage` et l'indisponibilité dans
  des en-têtes maison, et oblige à inventer un corps d'erreur HTML pour le 503. Deux contrats à la
  place d'un, pour un gain nul ici.
- **`<template>` cloné côté client** — garderait le JSON de données, mais le remplissage
  (quel champ dans quel nœud, quelles parties masquées quand la donnée manque) redeviendrait une
  seconde définition de la carte, en JavaScript. C'est exactement le défaut à supprimer.
- **Turbo Frames** — hors périmètre par décision explicite de la spec : Turbo a été retiré à
  l'Étape 4, le réintroduire est un arbitrage à part entière.

---

## D3 — L'état « aucun résultat » suit la carte

**Décision** : l'état vide est rendu par le serveur lui aussi. Quand la recherche ne ramène rien, la
réponse porte `empty: true` et `html` contient **le bloc d'état vide**, rendu depuis le même endroit
que celui du premier écran. Le contrôleur Stimulus n'écrit plus de chaîne HTML, jamais.

**Rationale** : découvert en lisant le contrôleur — l'état vide est aujourd'hui une **troisième**
définition de balisage écrite en JavaScript (`grid.innerHTML = '<div class="col-span-full …'`), et
elle a déjà divergé de celle du gabarit (message différent, habillage différent). Corriger la carte
en laissant celle-là reproduirait le défaut à l'identique, dans le même fichier. C'est FR-010.

**Alternative considérée** : laisser l'état vide en JavaScript, hors périmètre — rejetée : la
correction coûte le même geste que la carte, et son absence rendrait FR-002 (« le navigateur
n'assemble pas de balisage ») faux à la lettre dès la première recherche infructueuse.

---

## D4 — L'annonce : région persistante, texte rendu par Twig

**Décision** : une région `role="status"` **présente dès le premier rendu**, vide, dans le gabarit de
la liste. La réponse du point d'accès porte un champ `announcement` — une phrase française rendue
côté serveur — que le contrôleur Stimulus recopie dans la région.

**Rationale** : deux contraintes se croisent. D'abord, une région d'annonce doit **exister avant**
que son contenu change, sinon l'annonce est inégalement restituée selon les technologies. Ensuite,
le texte visible est français et, dans ce projet, le français vit **dans les gabarits** — pas dans un
contrôleur PHP, pas dans un fichier JavaScript. Faire rendre la phrase par le serveur satisfait les
deux : la région est statique, la phrase reste une ligne de Twig.

**Alternative considérée** : composer la phrase en JavaScript à partir d'un compte (`${n} recettes
ajoutées`) — rejetée : elle réintroduirait du texte visible hors des gabarits, ce que la règle de
nommage interdit, et il faudrait y gérer l'accord du pluriel à la main.

---

## Ce que la recherche a déplacé par rapport à la spec

- **Le périmètre est plus large d'un cran** : le balisage composé en JavaScript ne se limite pas à
  la carte, l'état vide en fait partie (D3). La spec l'avait anticipé en FR-010 ; la recherche
  confirme que c'est bien du même fichier et du même geste qu'il s'agit.
- **L'échappement n'est pas une exigence à implémenter, c'est une conséquence.** Une fois le rendu
  déplacé côté serveur, S3 disparaît sans code dédié. Aucune tâche ne portera « échapper les
  champs » : la tâche est « ne plus rendre côté client », et le test le prouve.
- **Le risque de régression est concentré sur un seul fichier**, `cookbook_controller.js`, qui perd
  environ un tiers de ses lignes. Les filtres, le tri et l'observateur de défilement ne changent pas.
