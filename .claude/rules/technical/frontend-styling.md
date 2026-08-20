---
description: Styling standard — Tailwind 4, tokens en deux niveaux, jamais de couleur littérale dans un gabarit, contrastes AA mesurés dans les deux thèmes.
paths:
  - "**/*.twig"
  - "**/assets/styles/**"
  - "**/assets/controllers/**/*.js"
  - "**/postcss.config.js"
---

# Styling — Tailwind CSS 4

## Règle d'or
**Des utilitaires dans le balisage ; la réutilisation passe par un composant, pas par une couche
CSS.** Quand la même grappe de classes revient une seconde fois, extraire un composant — voir
[components-shadcn.md](components-shadcn.md).

Configuration : **Tailwind 4** via PostCSS, `@tailwindcss/postcss` pour unique plugin (il intègre le
préfixage : ne pas rajouter `autoprefixer`). **Il n'y a pas de `tailwind.config.js`** — tout se
configure en CSS, dans `assets/styles/app.css`.

## Déclarer les sources, toujours

```css
@import 'tailwindcss';
@source '../../templates';
@source '../../assets';
```

`@source '../../assets'` n'est pas facultatif : trois contrôleurs Stimulus posent des utilitaires à
l'exécution. Une classe qu'aucune source ne montre n'est **pas générée**, l'élément s'affiche sans
style, et **aucune erreur n'est levée**. Ajouter un répertoire de gabarits ou de scripts, c'est
ajouter son `@source` dans le même changement.

Nommer les gabarits **`*.html.twig`**.

## Les tokens — deux niveaux, et rien d'autre

1. **Les couleurs brutes** (`--amber`, `--night`…) dans `:root`, nommées par ce qu'elles sont.
2. **Les rôles** (`--surface-raised`, `--content-low`, `--brand-fg`, `--line-control`…), nommés par
   ce à quoi ils servent, définis à partir des couleurs brutes.
3. **`@theme inline`** expose les rôles aux utilitaires, et y adosse le vocabulaire du kit shadcn
   (`--color-background`, `--color-foreground`, `--color-primary`, `--color-ring`…).

**Rebrander, c'est éditer `:root`.** Ne jamais écrire de valeur dans `@theme inline` : ces noms
pointent vers des rôles, les rôles vers des couleurs.

- **Aucune couleur littérale dans un gabarit ni dans un contrôleur Stimulus.** Pas de `bg-slate-800`,
  pas de `text-amber-500`, pas de `#faa734` : `bg-surface-raised`, `text-brand-fg`. Une classe posée
  depuis le JavaScript suit la même règle.
- **`accent` appartient au kit** — il y désigne une surface de survol discrète. L'ambre du portfolio,
  c'est `brand-*`. Ne pas confondre les deux.
- **Ne pas surcharger la palette standard de Tailwind.** Redéfinir `amber-500` fait mentir un
  composant du kit qui le référencerait.
- **Un aplat de marque porte `text-brand-on`**, jamais `text-white` : blanc sur ambre donne 1,97:1.

## Les deux thèmes

Le sombre est la définition par défaut dans `:root` ; le clair redéfinit les rôles sous
`@media (prefers-color-scheme: light)`. Ne rien déclarer en variantes `dark:` — le thème passe par
les tokens, et une variante `dark:` bascule seule pendant que le reste du site ne suit pas.

**Les deux thèmes doivent satisfaire AA** (4,5:1 pour le texte, 3:1 pour les graphiques, les éléments
d'interface et les indicateurs de focus). Une teinte se **mesure**, elle ne s'estime pas : calculer
le rapport pour chaque couple avant de le retenir, et le consigner. Le relevé existant est dans
[`specs/001-tailwind4-shadcn/light-theme.md`](../../../specs/001-tailwind4-shadcn/light-theme.md) —
l'étendre plutôt que d'en ouvrir un autre.

## Quand du CSS écrit à la main est acceptable
- Surcharger du DOM tiers qu'on ne contrôle pas.
- Les keyframes et les animations complexes.
- Un bloc de composant dense appliqué depuis le JavaScript — le bloc Motus d'`app.css` est le cas.

Ce bloc reste **hors `@layer`** et n'a pas à être « rangé » : Tailwind 4 n'élague pas le CSS de
l'auteur. Il consomme les tokens (`var(--motus-cell)`), comme le reste.

Partout ailleurs : des utilitaires. Quand du CSS s'impose dans `assets/styles/**`, **préférer
`@apply`** là où les utilitaires expriment la règle. Les tokens eux-mêmes restent des propriétés
personnalisées — ils ne sont pas `@apply`-ables.

Préférer l'échelle aux valeurs arbitraires : `p-4`, pas `p-[17px]`. `[...]` pour un vrai cas isolé
(un `aspect-ratio`, une taille d'icône précise).

## Poids
La feuille livrée tient sous **15 Ko une fois compressée**. Mesurer après un `npm run build` :
`gzip -9 -c public/build/app.*.css | wc -c`. Dépasser ce plafond est un sujet à soulever, pas à
absorber.

## Maquettes
Le dimensionnement d'une maquette est **indicatif**. Respecter le rythme des espacements, la
hiérarchie et les couleurs ; caler les dimensions sur l'échelle Tailwind plutôt que reproduire des
pixels.

## Vérifier
JS et CSS n'ont **aucun linter** ici. Relire à la main, **charger la page**, et contrôler les deux
thèmes — pas seulement celui du système. Voir [linting.md](linting.md).

## Voir aussi
- Composants et kit : [components-shadcn.md](components-shadcn.md)
- Gabarits et accessibilité : [frontend-twig.md](frontend-twig.md)
- Interactivité : [ux-stimulus-turbo.md](ux-stimulus-turbo.md)
