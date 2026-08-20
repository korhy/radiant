# Contrat — `GET /app/cookbook/recipes`

Point d'accès interne, consommé par le seul contrôleur Stimulus `cookbook`. Il sert le défilement
infini et le rechargement de la grille après une recherche, un filtre ou un tri.

## Requête

Inchangée. Tous les paramètres restent facultatifs.

| Paramètre | Type | Défaut | Rôle |
|---|---|---|---|
| `page` | entier ≥ 1 | `1` | page demandée |
| `itemsPerPage` | entier ≥ 1 | `10` | taille de page |
| `query` | chaîne | — | recherche sur le titre |
| `category` | identifiant | — | filtre par catégorie |
| `order[title]` · `order[duration]` · `order[createdAt]` | `asc` \| `desc` | — | tri |

## Réponse — 200

```json
{
  "html": "<a href=\"/app/cookbook/recipe/12\" …>…</a><a …>…</a>",
  "empty": false,
  "hasNextPage": true,
  "nextPage": 3,
  "announcement": "10 recettes ajoutées."
}
```

| Champ | Type | Contrat |
|---|---|---|
| `html` | chaîne | Le balisage **déjà rendu par le serveur** à insérer dans la grille. Cartes concaténées, ou le bloc d'état vide quand `empty` est vrai. Jamais de données brutes. |
| `empty` | booléen | Vrai quand la requête ne ramène aucune recette **et** qu'il s'agit de la première page : `html` porte alors l'état vide et doit **remplacer** le contenu de la grille, pas s'y ajouter. |
| `hasNextPage` | booléen | Vrai s'il reste une page après celle-ci. |
| `nextPage` | entier \| `null` | Numéro de la page suivante, `null` si `hasNextPage` est faux. |
| `announcement` | chaîne | Phrase française rendue par le serveur, destinée à la région `role="status"`. Vide si rien n'est à annoncer. |

**Ce que le client ne fait pas** : composer du balisage, construire une URL de fiche, interpoler un
champ de recette dans une chaîne. Il insère `html`, lit les trois drapeaux, recopie `announcement`.

## Réponse — 503

Contrat acquis au lot S4/S8, **inchangé** :

```json
{ "html": "", "empty": false, "hasNextPage": false, "nextPage": null, "unavailable": true }
```

Le client affiche l'indisponibilité et **n'affiche jamais** l'état « aucun résultat » dans ce cas.
Deux tests figent déjà ce comportement, ils doivent rester verts sans être modifiés.

## Invariant vérifiable

Pour un même jeu de recettes, le balisage d'une carte servie ici et celui de la même carte au premier
écran sont **identiques**. C'est ce que prouve le test de non-divergence (voir
[quickstart.md](../quickstart.md)).
