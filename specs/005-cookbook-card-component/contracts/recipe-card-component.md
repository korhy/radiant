# Contrat — `<twig:RecipeCard>`

Composant du projet, aux conventions du kit shadcn. Unique définition de la carte recette : le
premier écran et le défilement passent tous les deux par lui.

**Fichier** : `templates/components/RecipeCard.html.twig`

## Props

| Prop | Type | Requis | Rôle |
|---|---|---|---|
| `recipe` | tableau | oui | Une recette telle que servie par l'API Cookbook, forme décrite dans [data-model.md](../data-model.md). |

Le composant ne prend **que** la recette. Aucun drapeau d'apparence, aucune variante : les deux
chemins de rendu doivent produire exactement la même chose, et une prop de variation serait la
première porte par laquelle la divergence reviendrait.

## Ce que le composant garantit

- **Lien de bout en bout** vers la fiche, dont l'adresse vient de la **définition de la route**
  (`path('cookbook_recipe', {id: …})`), jamais d'une chaîne recomposée.
- **Champs facultatifs absorbés** : sans vignette, le repli est l'icône Cookbook du site ; sans
  catégorie et sans durée, la ligne de métadonnées ne laisse ni vide ni séparateur orphelin.
- **Catégorie rendue par `<twig:Badge>`**, déjà installé — pas une pastille recomposée.
- **Tout champ affiché est du texte.** L'échappement est celui de Twig, y compris dans `alt` ; aucune
  règle d'échappement n'est écrite à la main, donc aucune ne peut être oubliée.
- **Nom accessible** : le lien est nommé par le titre de la recette. La vignette de repli est
  décorative et n'est pas annoncée.

## Ce que le composant ne fait pas

- Aucune requête, aucun accès au client Cookbook : la donnée arrive du contrôleur.
- Aucune classe de mise en grille : c'est l'appelant qui place la carte dans sa grille.

## Appelants

| Chemin | Appelant |
|---|---|
| Premier écran | `templates/app/cookbook/index.html.twig`, dans la boucle de la grille |
| Défilement, recherche, filtre, tri | le gabarit de fragment rendu par `CookbookController::recipesJson()` |

Ces deux appelants doivent rester les **seuls**. Un troisième chemin de rendu est le signal que la
duplication est en train de revenir.
