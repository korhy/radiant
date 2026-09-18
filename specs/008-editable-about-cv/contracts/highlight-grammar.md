# Contrat — grammaire de mise en valeur

**Feature**: `008-editable-about-cv` · Couvre FR-003, FR-004, FR-005, FR-006

Ce document est la référence exécutable de la convention de saisie. Chaque ligne du tableau
*Comportement* est un cas de test de `HighlightParserTest`.

---

## Interface

```
App\Service\Content\HighlightParser::parse(?string $text): list<TextSegment>
```

- **Entrée** : le texte saisi dans l'administration, tel que stocké. `null` et `''` sont valides.
- **Sortie** : une liste ordonnée de segments. Concaténer les `text` dans l'ordre **restitue
  exactement l'entrée, moins les paires de marqueurs consommées**.
- **Ne lève jamais.** Aucune entrée, si mal formée soit-elle, ne produit d'exception.
- **Sans effet de bord.** Fonction pure, service sans état.

---

## Grammaire

Un **marqueur** est la séquence `**`. Une **paire** est constituée du premier marqueur rencontré et
du marqueur suivant, à condition qu'au moins un caractère les sépare. Le contenu d'une paire est mis
en valeur ; tout le reste est du texte courant.

L'analyse est **de gauche à droite, sans retour arrière**. Il n'y a pas d'imbrication : à
l'intérieur d'une paire, `**` n'a aucune signification particulière — c'est le marqueur fermant, ou
du texte.

---

## Comportement

| # | Entrée | Sortie attendue |
|---|---|---|
| 1 | `null` | `[]` |
| 2 | `''` | `[]` |
| 3 | `Bonjour` | `[("Bonjour", false)]` |
| 4 | `Un **mot** mis en valeur` | `[("Un ", false), ("mot", true), (" mis en valeur", false)]` |
| 5 | `**Début** et **fin**` | `[("Début", true), (" et ", false), ("fin", true)]` |
| 6 | `**Tout le texte**` | `[("Tout le texte", true)]` |
| 7 | `Marqueur **jamais refermé` | `[("Marqueur **jamais refermé", false)]` |
| 8 | `**a **b** c**` | `[("a ", true), ("b", false), (" c", true)]` |
| 9 | `Vide **** ici` | `[("Vide **** ici", false)]` |
| 10 | `Trois ***mots*** ici` | `[("Trois ", false), ("*mots", true), ("* ici", false)]` |
| 11 | `<script>alert(1)</script>` | `[("<script>alert(1)</script>", false)]` |
| 12 | `**<b>gras</b>**` | `[("<b>gras</b>", true)]` |
| 13 | `Ligne 1\nLigne 2` | `[("Ligne 1\nLigne 2", false)]` |

### Ce que disent les cas limites

- **7 — marqueur non refermé** : le marqueur orphelin est rendu **littéralement**. Rien n'est avalé,
  rien n'est deviné. Le propriétaire voit immédiatement son oubli dans la page (FR-005).
- **8 — apparence d'imbrication** : sans retour arrière, `**a **` forme la première paire et
  `** c**` la seconde. Le résultat est stable et explicable ; il n'est pas « ce que l'auteur
  voulait », mais il est prévisible, et c'est ce qu'exige FR-005.
- **9 — paire vide** : `****` ne contient aucun caractère, donc ne forme pas une paire. Rendu
  littéralement ; aucun segment vide n'est émis.
- **10 — trois astérisques** : le parseur consomme les deux premières astérisques à l'ouverture et
  les deux premières rencontrées à la fermeture ; les astérisques restantes appartiennent au texte,
  d'un côté comme de l'autre. Le résultat est asymétrique (`*mots` d'un côté, `* ici` de l'autre) —
  c'est la conséquence directe de l'analyse sans retour arrière, et c'est délibéré : aucune règle de
  rattrapage n'est ajoutée. Pas de gras, pas d'italique, pas de cumul (FR-004).

**Invariante vérifiable sur les treize cas** : concaténer les `text` dans l'ordre restitue l'entrée
moins les seuls marqueurs consommés — quatre caractères par paire formée, zéro sinon. C'est
l'assertion la plus utile du test unitaire, parce qu'elle attrape toute perte de caractère sans
qu'on ait à énumérer les découpages.
- **11 et 12 — balisage saisi** : le balisage reste dans le champ `text` d'un segment. Il n'est
  jamais interprété, quel que soit le niveau de mise en valeur. C'est la garantie de FR-006, et elle
  tient au **type de la sortie**, pas à une précaution du parseur.

---

## Contrat de rendu, côté gabarit

`templates/portfolio/section/about.html.twig` :

```twig
{% for segment in about_segments %}
    {%- if segment.highlighted -%}
        <span class="text-brand-fg">{{ segment.text }}</span>
    {%- else -%}
        {{ segment.text }}
    {%- endif -%}
{% endfor %}
```

**Deux règles non négociables** :

1. **Aucun `|raw`** dans ce gabarit, jamais. L'échappement automatique de Twig est le dernier
   rempart, et c'est celui qui avait sauté dans le constat S3.
2. **`text-brand-fg` est le seul habillage** appliqué à un segment. La classe existe déjà et porte
   le contraste mesuré du thème clair comme du thème sombre (voir
   `specs/001-tailwind4-shadcn/light-theme.md`) — aucune nouvelle couleur n'est introduite.

La section entière n'est rendue que si `about_segments` n'est pas vide (FR-009) ; l'entrée de
navigation `#about` de `templates/portfolio/header/nav.html.twig` suit la même condition, faute de
quoi elle pointerait sur une ancre absente.

---

## Aide à la saisie

`SiteContentCrudController` documente la convention via `setHelp()` sur le champ, comme le fait déjà
le motif des colonnes JSON :

> Entourez une expression de `**` pour la mettre en couleur : `un **mot** en valeur`. C'est le seul
> effet disponible.

C'est le seul endroit où la convention est exposée au propriétaire, et FR-007 en dépend
directement : si ce texte disparaît, l'exigence n'est plus tenue.
