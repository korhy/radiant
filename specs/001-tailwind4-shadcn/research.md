# Phase 0 — Recherche

**Feature**: Tailwind 4 et le kit shadcn · **Date**: 2026-08-19

Tout ce qui suit est mesuré sur le code de la branche, pas déduit de notes de version.

---

## R1 — Le bloc Motus n'a jamais été un problème de purge d'utilitaires

**Décision** : laisser le bloc Motus (`assets/styles/app.css`, lignes 11 à 347) **tel quel**, hors
`@layer`, au premier passage. Ne pas introduire `@source inline(...)` pour lui.

**Constat** : ce bloc est du **CSS écrit à la main** — `.motus-cell--correct { background-color:
#ef4444 }` et consorts —, pas des utilitaires Tailwind. Le contournement actuel s'explique par
Tailwind 3, qui élague le contenu de `@layer components` selon le balayage des sources. Tailwind 4
s'appuie sur les couches CSS natives et **n'élague pas le CSS de l'auteur** : la contrainte disparaît
d'elle-même.

**Ce qui, en revanche, est bien constitué d'utilitaires posés par JavaScript** — et doit donc être
couvert par les sources déclarées :

| Fichier | Classes concernées |
|---|---|
| `cookbook_controller.js` | tout le gabarit de carte recette (`group`, `rounded-2xl`, `shadow-lg`, `hover:shadow-amber-500/10`, `col-span-full`, `animate-pulse`…) |
| `app_detail_drawer_controller.js` | `translate-x-full`, `hidden`, `border-amber-400`, `border-b-2`, `border-slate-700`, `text-amber-400`, `text-slate-400`, `text-white` |
| `motus_controller.js` | uniquement `hidden` et les `motus-*` (ces derniers étant du CSS écrit à la main) |

**Conséquence** : `@source '../../assets'` suffit. Aucune liste manuelle à maintenir.

**Alternative écartée** : lister ces classes en `@source inline(...)`. Redondant, et une liste
manuelle dérive dès qu'un contrôleur change.

**Vérification imposée** : ce raisonnement doit être confirmé **empiriquement** avant de continuer —
une partie de Motus jouée et une carte recette chargée après bascule, sur le build de production, pas
en développement.

---

## R2 — Surface réelle des ruptures de Tailwind 4 : 24 occurrences

**Décision** : traiter les renommages par une passe mécanique, avant toute autre modification.

**Mesure** sur 420 classes distinctes relevées dans `templates/**` et `assets/**` :

| Utilitaire | Devient | Occurrences | Effet si oublié |
|---|---|---|---|
| `outline-none` | `outline-hidden` | 4 | Le contour de focus disparaît → **régression d'accessibilité** |
| `shadow` | `shadow-sm` | 2 | Ombre plus marquée qu'avant |
| Bordures **sans couleur** (`border`, `border-b`, `border-2`, `border-t`, `border-l-2`) | inchangées, mais la couleur par défaut passe de `gray-200` à `currentColor` | 18 | Bordures qui prennent la couleur du texte → visuellement fausses |

**Aucune** occurrence de `bg-opacity-*`, `text-opacity-*`, `flex-shrink-*`, `flex-grow-*`,
`overflow-ellipsis`, ni de `rounded`/`blur`/`ring` nus. La surface est **beaucoup plus étroite** que
ce que laissait craindre l'ampleur du lot.

**Le plus dangereux est le troisième** : il ne se voit pas dans un diff, seulement à l'écran, et les
18 emplacements sont dispersés.

---

## R3 — Chaîne de construction

**Décision** : `@tailwindcss/postcss` comme unique plugin PostCSS, `autoprefixer` retiré, Webpack
Encore conservé.

**Rationale** : Tailwind 4 intègre le préfixage. Encore reste la chaîne du projet, et
`enablePostCssLoader()` ne change pas. Le fichier `tailwind.config.js` disparaît au profit d'une
configuration en CSS.

**Alternative écartée** : passer à Vite ou à AssetMapper. Hors sujet, et AssetMapper a justement été
retiré du projet comme code mort.

---

## R4 — Palette : deux niveaux, jamais de couleur en dur dans un gabarit

**Décision** : couleurs brutes définies une fois dans `:root`, puis exposées aux utilitaires par un
bloc `@theme inline`.

**Point d'attention majeur** : la configuration actuelle **écrase** `amber-400/500/600/700` par des
valeurs maison, et ajoute un `bg-blue-950` qui n'est pas une couleur mais un nom de classe. Un
composant du kit qui référencerait `amber-500` prendrait donc la teinte du site — ce qui est
souhaitable ici, mais accidentel. L'accent devient un token nommé, et les surcharges de la palette
standard disparaissent.

**Conséquence mesurable** : `bg-bg-blue-950` (la double préfixation actuelle) doit disparaître des
gabarits.

---

## R5 — Thèmes clair et sombre suivant la préférence système

**Décision** : les tokens sombres restent la définition par défaut ; le thème clair est déclaré sous
`prefers-color-scheme: light`. Le variant `dark:` du kit est conservé pour les composants qui
l'emploient.

**Rationale** : le site est nativement sombre. Déclarer le sombre par défaut évite un flash clair au
chargement et garde le rendu actuel comme référence de non-régression — seul le thème clair est du
neuf.

**Ce qui doit être décidé et consigné** (FR-015) : fond, texte, surfaces, bordures et surtout
**l'accent ambre**. Contrastes mesurés pour `#faa734`, l'accent actuel :

| Fond | Rapport | AA texte (4,5:1) | AA grand texte / UI (3:1) |
|---|---|---|---|
| Ardoise du site `#23313e` | **6,73:1** | ✅ | ✅ |
| Blanc | **1,97:1** | ❌ | ❌ |
| `slate-50` | **1,89:1** | ❌ | ❌ |

**C'est le point le plus important de cette recherche.** L'accent du site est inutilisable en thème
clair, y compris pour de grands textes ou des contours de champs. Et la variante la plus sombre que
la palette possède déjà — `amber-700`, `#b76a14` — plafonne à **4,13:1**, donc encore sous le seuil
du texte normal.

**Le thème clair impose donc une teinte d'accent qui n'existe nulle part dans le projet.** Mesures
de candidats : `#a35c12` → 5,13:1, `#8a4f0f` → 6,55:1. Le choix est esthétique, la contrainte ne
l'est pas.

À défaut, l'alternative est de cantonner l'ambre aux **fonds** en thème clair, avec un texte sombre
par-dessus — ce qui change la manière dont l'accent se lit, et donc l'identité.

---

## R6 — Thème de formulaire

**Décision** : conserver `tailwind_2_layout` au premier passage, et vérifier le formulaire de contact
à l'écran.

**Rationale** : le relevé montre que ce thème pose très peu de classes (`text-red-700`,
`flex items-center`, `inline-flex items-center`) — l'essentiel du style vient déjà des classes
passées par le projet via `label_attr`/`attr`, précisément parce qu'une régression de bordure avait
été corrigée ainsi. La surface de rupture est donc faible.

**Alternative écartée** : basculer le formulaire sur les composants du kit. Hors périmètre (FR-013).

---

## R7 — Plafond de version du kit

**Décision** : `symfony/ux-toolkit:^2.36`.

**Vérifié** : la ligne 3.x exige **PHP >= 8.4**. Le serveur de production fournit 8.4, mais
l'intégration continue compile en 8.2 — et c'est elle qui contraint. La moitié Symfony de l'exigence
(`^7.4|^8.0`) est satisfaite depuis la montée en 7.4.

`symfony/ux-twig-component` est **déjà installé**, en dépendance transitive d'EasyAdmin, et
`config/packages/twig_component.yaml` existe déjà avec `anonymous_template_directory`. Il faut donc
**déclarer** la dépendance et **ajouter** le mapping `defaults`, non les créer.

---

## R8 — Comment vérifier, puisque rien ne l'automatise

**Décision** : produire un **relevé de référence avant bascule** — captures des huit pages publiques
aux trois largeurs — et comparer après. Sans ce relevé, SC-001 n'est pas vérifiable.

**Rationale** : il n'existe ni linter CSS, ni test visuel, ni test de bout en bout. La comparaison
avant/après est le seul instrument disponible, et elle n'existe que si les captures sont prises
**avant** de toucher au moteur.

**Volume** : 8 pages publiques × 3 largeurs = **24 captures** de référence, puis **48 écrans** à
contrôler après (les deux thèmes). `/admin` s'ajoute à part : il exige une authentification et
renvoie 302 anonymement, donc il ne peut pas figurer dans un relevé anonyme. Le thème clair n'a pas
de référence — il se contrôle au contraste, pas à la comparaison.

**Alternative écartée** : introduire des tests visuels automatisés. Ce serait un lot en soi, et le
projet n'a pas de socle Playwright versionné.
