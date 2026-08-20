# Thème clair — décisions et mesures

**Statut** : arrêté le 2026-08-20 · tranche US2 (T024, T030) · FR-014, FR-015

Le site est nativement sombre. Le sombre reste donc la **définition par défaut** dans `:root` et le
clair est déclaré sous `@media (prefers-color-scheme: light)` — ce qui évite un flash clair au
chargement et garde le rendu existant comme référence.

Toutes les valeurs de ce document sont **mesurées**, pas estimées : la formule de contraste WCAG 2.1
est appliquée aux tokens tels qu'ils figurent dans `assets/styles/app.css`, `var()` résolus.

---

## T024 — La teinte d'accent

**Décidé : deux rôles distincts pour l'ambre, et non une teinte unique.**

L'ambre de marque `#faa734` donne **1,97:1 sur blanc**. Il est inutilisable en avant-plan sur fond
clair, y compris pour du grand texte. La variante la plus sombre que la palette possédait déjà,
`#b76a14`, plafonne à **4,13:1** — encore sous le seuil du texte courant.

Mais le même ambre donne **6,73:1 lorsqu'il sert de fond** et qu'on pose l'ardoise du site par
dessus. C'est l'avant-plan qui échoue, pas la couleur.

| Rôle | Token | Sombre | Clair | Emploi |
|---|---|---|---|---|
| Fond accentué | `--accent` | `#faa734` | `#faa734` | Boutons pleins, aplats, indicateurs |
| Fond accentué survolé | `--accent-strong` | `#e68a1a` | `#e68a1a` | État survolé des mêmes |
| Texte sur fond accentué | `--accent-on` | `#23313e` | `#23313e` | Le libellé du bouton |
| Accent en avant-plan | `--accent-fg` | `#faa734` | `#a35c12` | Liens, texte accentué, bordure au focus |
| Accent en avant-plan, emphase | `--accent-fg-em` | `#fcc175` | `#8a4f0f` | Second niveau d'accent |
| Accent graphique clair | `--accent-light` | `#fcc175` | `#b76a14` | Puces, bordures de pastilles |

**Conséquence** : l'identité visuelle est conservée telle quelle. Les surfaces ambre restent l'ambre
de marque dans les deux thèmes ; seuls les *textes* ambre s'assombrissent en thème clair. Le
basculement d'un thème à l'autre ne change qu'`--accent-fg`, `--accent-fg-em` et `--accent-light`.

**Écartées** : une teinte unique `#a35c12` partout (5,13:1, mais les aplats perdent leur éclat et le
clair s'éloigne visiblement du sombre) ; `#8a4f0f` partout (6,55:1, mais lit comme un brun — c'est un
changement d'identité, pas une adaptation).

---

## T030 — Contrastes mesurés

Seuils WCAG 2.1 AA : **4,5:1** pour le texte courant, **3:1** pour les graphiques et les éléments
d'interface (1.4.11) ainsi que pour les indicateurs de focus (2.4.11).

Le fond du site est un dégradé : `--canvas` sur 50 % de la surface, puis `--canvas-end` vers le coin.
Les deux extrémités sont mesurées séparément quand l'écart compte, le pire cas faisant foi.

| Paire | Tokens | Seuil | Sombre | Clair |
|---|---|---|---|---|
| Titre sur carte | `--content-max` sur `--surface-raised` | 4.5:1 | **14.63:1** ✅ | **17.85:1** ✅ |
| Titre sur le fond | `--content-max` sur `--canvas-end` | 4.5:1 | **12.12:1** ✅ | **15.88:1** ✅ |
| Titre de section sur le fond | `--content-high` sur `--canvas-end` | 4.5:1 | **11.06:1** ✅ | **13.01:1** ✅ |
| Texte appuyé sur le fond | `--content` sur `--canvas-end` | 4.5:1 | **9.83:1** ✅ | **9.21:1** ✅ |
| Texte secondaire sur le fond | `--content-mid` sur `--canvas-end` | 4.5:1 | **8.16:1** ✅ | **6.74:1** ✅ |
| Corps de texte sur le fond (début du dégradé) | `--content-low` sur `--canvas` | 4.5:1 | **5.18:1** ✅ | **7.24:1** ✅ |
| Corps de texte sur le fond (fin du dégradé) | `--content-low` sur `--canvas-end` | 4.5:1 | **4.73:1** ✅ | **6.74:1** ✅ |
| Corps de texte sur carte | `--content-low` sur `--surface-raised` | 4.5:1 | **5.71:1** ✅ | **7.58:1** ✅ |
| Mentions discrètes sur le fond | `--content-min` sur `--canvas-end` | 4.5:1 | **4.73:1** ✅ | **5.19:1** ✅ |
| Mentions discrètes sur carte | `--content-min` sur `--surface-raised` | 4.5:1 | **5.71:1** ✅ | **5.83:1** ✅ |
| Texte sur pastille | `--content-max` sur `--surface-inset` | 4.5:1 | **10.35:1** ✅ | **14.48:1** ✅ |
| Icône sur pastille (graphique, 1.4.11) | `--content-low` sur `--surface-inset` | 3.0:1 | **4.04:1** ✅ | **6.15:1** ✅ |
| Accent en avant-plan sur le fond | `--accent-fg` sur `--canvas-end` | 4.5:1 | **6.14:1** ✅ | **4.56:1** ✅ |
| Accent en avant-plan sur le fond (début du dégradé) | `--accent-fg` sur `--canvas` | 4.5:1 | **6.73:1** ✅ | **4.90:1** ✅ |
| Accent en avant-plan sur carte | `--accent-fg` sur `--surface-raised` | 4.5:1 | **7.41:1** ✅ | **5.13:1** ✅ |
| Accent emphase sur carte | `--accent-fg-em` sur `--surface-raised` | 4.5:1 | **9.07:1** ✅ | **6.55:1** ✅ |
| Accent emphase sur pastille | `--accent-fg-em` sur `--surface-inset` | 4.5:1 | **6.42:1** ✅ | **5.31:1** ✅ |
| Texte sur bouton accentué | `--accent-on` sur `--accent` | 4.5:1 | **6.73:1** ✅ | **6.73:1** ✅ |
| Texte sur bouton accentué survolé | `--accent-on` sur `--accent-strong` | 4.5:1 | **5.07:1** ✅ | **5.07:1** ✅ |
| Saisie dans un champ | `--field-content` sur `--field` | 4.5:1 | **10.31:1** ✅ | **16.98:1** ✅ |
| Indication de saisie | `--field-hint` sur `--field` | 4.5:1 | **4.98:1** ✅ | **4.63:1** ✅ |
| Libellé sur le panneau de formulaire | `--content-max` sur `--panel` | 4.5:1 | **14.68:1** ✅ | **17.85:1** ✅ |
| Texte du bouton neutre | `--neutral-on` sur `--neutral` | 4.5:1 | **7.56:1** ✅ | **7.56:1** ✅ |
| Texte du bouton neutre survolé | `--neutral-on` sur `--neutral-strong` | 4.5:1 | **10.31:1** ✅ | **14.68:1** ✅ |
| Message flash | `--notice-content` sur `--notice-raised` | 4.5:1 | **8.06:1** ✅ | **10.72:1** ✅ |
| Étiquette du message flash | `--notice-on` sur `--notice-accent` | 4.5:1 | **6.29:1** ✅ | **7.90:1** ✅ |
| Chiffre de tuile du Taquin | `--tile-content` sur `--tile-face` | 4.5:1 | **13.35:1** ✅ | **14.63:1** ✅ |
| Chiffre de tuile survolée | `--tile-content` sur `--tile-face-hover` | 4.5:1 | **9.85:1** ✅ | **11.87:1** ✅ |
| Lettre dans une case vide | `--motus-cell-content` sur `--motus-cell` | 4.5:1 | **10.35:1** ✅ | **14.48:1** ✅ |
| Lettre bien placée | `--motus-correct-content` sur `--motus-correct` | 4.5:1 | **4.83:1** ✅ | **4.83:1** ✅ |
| Lettre mal placée | `--motus-present-content` sur `--motus-present` | 4.5:1 | **11.66:1** ✅ | **11.66:1** ✅ |
| Lettre absente | `--motus-absent-content` sur `--motus-absent` | 4.5:1 | **7.58:1** ✅ | **6.96:1** ✅ |
| Touche du clavier | `--motus-key-content` sur `--motus-key` | 4.5:1 | **7.58:1** ✅ | **12.02:1** ✅ |
| Panneau d'aide de Motus | `--motus-surface-content` sur `--motus-surface` | 4.5:1 | **6.97:1** ✅ | **9.85:1** ✅ |
| Titre de Motus | `--motus-title` sur `--motus-keyboard` | 4.5:1 | **14.63:1** ✅ | **14.48:1** ✅ |
| Message de Motus | `--motus-message` sur `--motus-keyboard` | 4.5:1 | **5.71:1** ✅ | **6.15:1** ✅ |
| Bordure de champ | `--line-control` sur `--field` | 3.0:1 | **4.02:1** ✅ | **3.73:1** ✅ |
| Bordure de contrôle sur carte | `--line-control` sur `--surface-raised` | 3.0:1 | **5.71:1** ✅ | **3.90:1** ✅ |
| Bordure de contrôle sur le fond | `--line-control` sur `--canvas-end` | 3.0:1 | **4.73:1** ✅ | **3.47:1** ✅ |
| Halo de focus sur le panneau de formulaire | `--accent-fg` sur `--panel` | 3.0:1 | **7.44:1** ✅ | **5.13:1** ✅ |
| Bordure de champ au focus | `--accent-fg` sur `--surface-raised` | 3.0:1 | **7.41:1** ✅ | **5.13:1** ✅ |
| Bordure de pastille de tag | `--accent-light` sur `--surface-raised` | 3.0:1 | **9.07:1** ✅ | **4.13:1** ✅ |

**42 paires, aucun échec.**

Le relevé est reproductible : la mesure lit les tokens dans la feuille de style, elle ne recopie pas
des valeurs saisies à la main. Réviser une couleur oblige donc à refaire tourner la mesure.

---

## Écarts délibérés par rapport au rendu antérieur

Le thème sombre devait rester la référence de non-régression. Quatre valeurs s'en écartent malgré
tout, parce que le rendu antérieur **ne satisfaisait pas AA** et que FR-014 l'exige des deux thèmes.
Chacune est une valeur de token, donc réversible en une ligne.

| Ce qui change | Avant | Après | Pourquoi |
|---|---|---|---|
| `--content-min` (mentions discrètes, 23 emplois) | `#64748b` slate-500 | `#94a3b8` | 2,79:1 sur le fond. Le cran le plus discret de la rampe passait sous le seuil ; il est fusionné avec le cran au-dessus. |
| `--canvas-end` (fin du dégradé de page) | `#334155` slate-700 | `#2a3746` | Au coin clair du dégradé, le corps de texte tombait à 4,04:1. |
| `--motus-correct` (lettre bien placée) | `#ef4444` red-500 | `#dc2626` red-600 | Blanc sur rouge donnait 3,76:1. |
| Texte des boutons accentués | `text-white` | `--accent-on` `#23313e` | Blanc sur ambre : **1,97:1**. Trois boutons concernés (Taquin ×2, ouverture du panneau). |

Deux corrections supplémentaires ne changent aucune valeur mais réparent un défaut réel :

- **Les halos de focus des formulaires étaient inopérants.** `focus:ring-amber-300` donnait 1,44:1,
  `focus:ring-amber-800` 2,07:1, et `dark:focus:ring-gray-800` posait un halo gris-800 sur un fond
  gris-800 — **1,00:1, invisible**. Tous passent sur `--accent-fg`, décollé du bouton par
  `ring-offset-panel` : 7,44:1 en sombre, 5,13:1 en clair.
- **Les bordures de champ ne délimitaient rien.** `border-gray-300` sur `bg-gray-50` donnait 1,41:1,
  sous les 3:1 de la règle 1.4.11 — un champ vide n'était identifiable que par son fond. Le token
  `--line-control` est tenu à 3:1 dans les deux thèmes.

## Ce qui n'est pas soumis à un seuil, et pourquoi

- **Le liseré des cases de Motus** (`--motus-cell-line` sur `--motus-cell`) sépare les cases entre
  elles ; il ne porte aucune information. L'état d'une lettre est donné par la couleur **et** par un
  relief non chromatique — contrat C4, vérifié en T016.
- **Les aplats `--accent`** portent toujours un libellé, mesuré séparément (`--accent-on`, 6,73:1).
  Un bouton plein est identifié par son texte, pas par le contraste de son fond avec la page.
- **Les icônes décoratives** posées sur `--surface-inset` relèvent du seuil graphique de 3:1, pas de
  celui du texte : ce sont des `<svg aria-hidden>` doublées d'un libellé.

## Où vivent ces valeurs

`assets/styles/app.css`, en tête de fichier : les couleurs de marque puis les rôles dans `:root`, la
redéfinition claire sous `@media (prefers-color-scheme: light)`, et le bloc `@theme inline` qui seul
les expose aux utilitaires. **Aucun gabarit ne contient de couleur littérale** (contrat C3) —
vérifié par SC-005 en T029 : basculer `--brand-amber` fait suivre l'intégralité du site.

---

## T033 — Passe automatisée (axe-core, WCAG 2.0/2.1 niveaux A et AA)

Six pages × deux thèmes, bandeau de debug Symfony retiré du document avant analyse — il n'appartient
pas au site et fausserait le relevé.

| | accueil | taquin | motus | contact | mentions légales | login |
|---|---|---|---|---|---|---|
| **Clair** | 0 | 0 | 0 | 0 | 0 | 0 |
| **Sombre** | 0 | 0 | 0 | 0 | 0 | 0 |

**12 combinaisons, 0 violation.**

`/app/cookbook` n'y figure pas : la page dépend d'une API externe, injoignable depuis le conteneur
avec la configuration locale en vigueur. Elle a été vérifiée à l'œil et à la console dans les deux
thèmes — cartes, filtres, tri, et re-rendu par JavaScript.

### Deux défauts trouvés par cette passe, et corrigés

Aucun des deux n'est causé par la mise en tokens ; tous deux étaient présents avant.

- **La touche ENTRER de Motus** posait du blanc sur l'ambre — **1,97:1**. La règle ne surchargeait
  que le fond et héritait la couleur de texte des autres touches. Elle prend `--accent-on` : 6,73:1.
  Le thème clair passait par accident, sa couleur de touche étant déjà sombre.
- **Les cases de la grille de Motus** portaient un `aria-label` sur un `div` sans rôle, où
  l'attribut est **interdit** — le libellé n'était donc pas exposé. La grille passe en
  `table` / `row` / `cell`, rôles qui l'autorisent, et qui n'impliquent pas la navigation au clavier
  cellule à cellule qu'aurait supposée `grid`.
