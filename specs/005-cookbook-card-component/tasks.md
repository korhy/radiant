---

description: "Task list — carte recette unique pour le Cookbook"
---

# Tasks: Carte recette unique pour le Cookbook

**Input**: Design documents from `/specs/005-cookbook-card-component/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/](contracts/)

**Tests**: oui — la spec les demande explicitement (SC-002, SC-003) et le quickstart nomme ce que
chacun prouve. Le test de non-divergence est le garde-fou du lot.

**Organization**: une phase par récit utilisateur. Particularité assumée de ce lot : **US2 et US3
deviennent vrais mécaniquement dès que US1 est livré**, parce que les trois écarts ont une cause
unique. Leurs phases ne les *implémentent* donc pas — elles les **prouvent** et suppriment ce qui
reste. C'est dit ici plutôt que déguisé en indépendance.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallélisable (fichiers différents, aucune dépendance en cours)
- **[Story]**: US1 à US4, tracé vers [spec.md](spec.md)
- Chemins de fichiers exacts dans chaque description

---

## Phase 1: Setup

**Purpose**: partir d'un état connu, et se donner la référence qui servira à prouver l'absence de régression.

- [X] T001 Vérifier le point de départ : `make ci` vert (45 tests) et `npm run dev` sans erreur
- [X] T002 Capturer la référence du rendu actuel — enregistrer le balisage d'une carte du premier écran et celui d'une carte du défilement dans `specs/005-cookbook-card-component/baseline/cards-before.html`, avec au moins une recette sans vignette
- [X] T003 [P] Relever les classes visuelles de la carte actuelle (`templates/app/cookbook/index.html.twig` lignes de la boucle) pour que le composant les reprenne à l'identique

**Checkpoint**: le rendu actuel est documenté — toute différence ultérieure sera visible, pas supposée.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: créer l'unique définition de la carte et les fragments partagés. **Aucun récit ne peut avancer avant.**

**⚠️ Ces trois fichiers sont la fondation du lot : tant qu'ils n'existent pas, les deux chemins de rendu restent séparés.**

- [X] T004 Installer la recette d'état vide du kit : `make console C="ux:install empty --kit=shadcn"`, relire les fichiers copiés dans `templates/components/Empty*`, et vérifier qu'aucun contrôleur Stimulus n'accompagne la recette (sinon l'enregistrer dans `assets/bootstrap.js`)
- [X] T005 Créer `templates/components/RecipeCard.html.twig` — prop `recipe` documentée en tête, lien de bout en bout via `path('cookbook_recipe', {id: recipe.id})`, vignette avec repli sur `components/_icon_cookbook.html.twig`, catégorie rendue par `<twig:Badge>`, durée conditionnelle, `attributes` fusionnés par `tailwind_merge`, `data-slot` par partie. Classes reprises de T003, à l'identique. Contrat : [contracts/recipe-card-component.md](contracts/recipe-card-component.md)
- [X] T006 [P] Créer `templates/app/cookbook/_recipe_grid_items.html.twig` — boucle sur `recipes` appelant `<twig:RecipeCard>`, sans aucune classe de grille (la grille appartient à l'appelant)
- [X] T007 [P] Créer `templates/app/cookbook/_recipe_grid_state.html.twig` — l'état vide bâti sur `<twig:Empty>`, paramétré par une variable `message`, habillé aux classes du site

**Checkpoint**: la carte a une définition unique, utilisable par les deux chemins. Rien ne la consomme encore.

---

## Phase 3: User Story 1 — La carte est la même partout (Priority: P1) 🎯 MVP

**Goal**: le premier écran et le défilement rendent la **même** carte, parce qu'ils passent par la même définition.

**Independent Test**: charger `/app/cookbook`, faire défiler deux pages, comparer les cartes — dont une sans vignette dans chaque groupe. Le repli doit être l'icône Cookbook des deux côtés.

### Tests for User Story 1

> Écrire T008 **avant** T010–T012 et le voir échouer : c'est lui qui distingue « la carte est unique » de « les deux copies se ressemblent aujourd'hui ».

- [X] T008 [US1] Créer `tests/Controller/CookbookCardTest.php` avec `testBothRenderPathsProduceTheSameCard` — même recette servie par `/app/cookbook` et par `/app/cookbook/recipes` via `MockHttpClient`, comparaison du balisage de carte normalisé (espaces réduits), assertion d'égalité stricte
- [X] T009 [P] [US1] Ajouter `testTheEndpointServesRenderedMarkup` — la réponse de `/app/cookbook/recipes` porte `html` non vide contenant le lien de la fiche, et **ne porte plus** de champ `recipes`

### Implementation for User Story 1

- [X] T010 [US1] Dans `templates/app/cookbook/index.html.twig`, remplacer le balisage de carte de la boucle par un include de `_recipe_grid_items.html.twig`, et supprimer les lignes qu'il remplace
- [X] T011 [US1] Dans `src/Controller/CookbookController.php`, faire renvoyer à `recipesJson()` la charge décrite par [contracts/recipes-endpoint.md](contracts/recipes-endpoint.md) — `html` rendu par `renderView('app/cookbook/_recipe_grid_items.html.twig', …)`, plus `empty`, `hasNextPage`, `nextPage`. Le bloc `catch (CookbookUnavailableException)` et sa réponse 503 ne bougent pas
- [X] T012 [US1] Dans `assets/controllers/cookbook_controller.js`, supprimer `#cardHtml()` et injecter `data.html` par `insertAdjacentHTML` ; conserver l'observateur, les filtres, le tri et le garde-fou `!res.ok`
- [X] T013 [US1] Lancer `npm run dev`, recharger `/app/cookbook` et faire défiler : vérifier T008 en conditions réelles, puis comparer avec `baseline/cards-before.html`

**Checkpoint**: **DU1 est refermé.** Le navigateur n'assemble plus de carte. US2 et US3 sont désormais vrais — les phases suivantes le prouvent.

---

## Phase 4: User Story 2 — Le contenu de l'API ne peut pas altérer la page (Priority: P1)

**Goal**: prouver que S3 est refermé, et supprimer le **dernier** balisage encore composé par le navigateur.

**Independent Test**: servir une recette dont le titre contient `L'entrecôte "façon <chef>"` et une chaîne ressemblant à une balise ; la page reste intacte et le titre s'affiche littéralement.

### Tests for User Story 2

- [X] T014 [US2] Ajouter `testRecipeFieldsAreEscaped` à `tests/Controller/CookbookCardTest.php` — titre piégé servi par les deux chemins ; assertions : la chaîne brute n'apparaît pas dans le balisage, sa forme échappée oui, et l'attribut `alt` est échappé lui aussi
- [X] T015 [P] [US2] Ajouter `testAnEmptyResultSetServesTheEmptyState` — une réponse d'API sans recette donne `empty: true` et un `html` contenant l'état vide, pas une chaîne vide

### Implementation for User Story 2

- [X] T016 [US2] Dans `src/Controller/CookbookController.php`, renvoyer l'état vide rendu par `_recipe_grid_state.html.twig` quand la première page ne ramène rien, avec `empty: true`
- [X] T017 [US2] Dans `assets/controllers/cookbook_controller.js`, supprimer la chaîne `grid.innerHTML = '<div class="col-span-full…'` et la remplacer par l'injection de `data.html` quand `data.empty` est vrai (remplacement, pas ajout)
- [X] T018 [US2] Dans `templates/app/cookbook/index.html.twig`, faire passer la branche « aucune recette » par `_recipe_grid_state.html.twig` ; laisser la branche d'indisponibilité en place, elle n'est jamais servie au client — *fait dès T010, les deux branches passant par le même partiel*
- [X] T019 [US2] Relire `assets/controllers/cookbook_controller.js` de bout en bout : **plus aucun littéral de gabarit ne doit contenir de balise**. C'est le critère d'arrêt de la phase — *un dernier littéral reconstruisait le conteneur « Charger plus » : il est désormais rendu par le gabarit et simplement masqué. Restent les libellés de tri en français dans le JS, hors périmètre*

**Checkpoint**: **S3 est refermé**, et plus aucun chemin ne compose de balisage côté navigateur.

---

## Phase 5: User Story 3 — La destination d'une carte a une seule source (Priority: P2)

**Goal**: verrouiller le fait qu'aucun chemin d'URL n'est recomposé à la main.

**Independent Test**: modifier le chemin de la route `cookbook_recipe` en local ; les cartes des deux groupes suivent.

- [X] T020 [US3] Ajouter `testTheCardLinkComesFromTheRouter` à `tests/Controller/CookbookCardTest.php` — l'adresse portée par une carte servie par le défilement est **exactement** celle générée par le routeur pour cette recette
- [X] T021 [US3] Vérifier par `grep` qu'aucune chaîne `/app/cookbook/recipe/` ne subsiste dans `assets/` ; supprimer ce qui traîne — *l'URL du point d'accès lui-même passe aussi en valeur Stimulus, comme `data-motus-guess-url-value` : plus aucun chemin en dur dans le JS*

**Checkpoint**: une route qui bouge ne peut plus casser silencieusement la moitié des cartes.

---

## Phase 6: User Story 4 — Le chargement reste perceptible sans la vue (Priority: P3)

**Goal**: annoncer l'ajout de recettes aux technologies d'assistance, sans voler le focus.

**Independent Test**: au lecteur d'écran, charger une page supplémentaire — l'ajout est annoncé, le focus ne bouge pas.

**Détachable** : si cette phase saute, les phases 3 à 5 restent complètes et cohérentes.

- [X] T022 [US4] Ajouter au gabarit `templates/app/cookbook/index.html.twig` une région `role="status"` **vide et présente dès le premier rendu**, hors de la grille
- [X] T023 [US4] Dans `src/Controller/CookbookController.php`, ajouter le champ `announcement` à la charge — phrase française **rendue par Twig**, jamais composée en PHP (voir décision D4 de [research.md](research.md))
- [X] T024 [US4] Dans `assets/controllers/cookbook_controller.js`, recopier `data.announcement` dans la région `role="status"` après insertion, sans déplacer le focus
- [X] T025 [US4] Ajouter à `tests/Smoke/AccessibilityTest.php` un test figeant la présence de la région `role="status"` sur `/app/cookbook`
- [ ] T026 [US4] **À faire par toi** — vérifier au lecteur d'écran (VoiceOver) : chargement d'une page annoncé, focus immobile ; et l'échec de chargement annoncé au même titre

**Checkpoint**: le défilement infini n'est plus muet.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T027 [P] Ajouter `RecipeCard` à la liste des composants du projet dans `.claude/rules/technical/components-shadcn.md`, avec la raison pour laquelle la recette `card` du kit n'a pas été retenue
- [X] T028 [P] Marquer **DU1** et **S3** traités dans `docs/audit/audit-2026-08-18.md`, ajouter l'étape correspondante à la section « Ordre recommandé », et mettre à jour le compte de la dimension Sécurité
- [X] T029 Dérouler [quickstart.md](quickstart.md) en entier — les six gestes manuels, aux trois largeurs et dans les deux thèmes — *fait contre une API bouchon ; largeurs mobile/tablette/bureau et les deux thèmes*
- [X] T030 axe-core sur `/app/cookbook` dans les deux thèmes, **avant et après** un chargement par défilement — **0 violation WCAG 2.1 A/AA** sur les quatre passes, barre de debug Symfony exclue. Une violation critique préexistante trouvée au passage (`select-name` sur le filtre de catégorie) et corrigée ici. Restent 3 constats de bonnes pratiques, structurels et hors périmètre : pas de `<main>`, pas de `<h1>`, contenu hors points de repère
- [X] T031 Faire le contrôle SC-001 : modifier une classe visuelle de `RecipeCard`, vérifier que la modification se voit sur les cartes des **deux** groupes, puis annuler la modification
- [X] T032 Conservé en référence, comme `specs/001-tailwind4-shadcn/baseline/` : `cards-before.html` et `cards-after.html` documentent les trois écarts voulus (`data-slot`, `alt=""`, Badge du kit)
- [X] T033 `make ci` vert, et `npm run dev` relancé après tout `npm run build` de contrôle

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)** → aucune dépendance
- **Foundational (Phase 2)** → dépend de Phase 1 · **bloque tous les récits**
- **US1 (Phase 3)** → dépend de Phase 2 · c'est le MVP
- **US2 (Phase 4)** → dépend de **US1** : sans le rendu déplacé côté serveur, ses tests n'ont rien à prouver
- **US3 (Phase 5)** → dépend de **US1** · indépendant de US2
- **US4 (Phase 6)** → dépend de **US1** · détachable, livrable seul plus tard
- **Polish (Phase 7)** → dépend des récits livrés

### La chaîne réelle

```text
Phase 2 (fondation)
      └─> US1  ──┬─> US2   (preuve + dernier balisage client)
                 ├─> US3   (preuve + nettoyage des URL)
                 └─> US4   (annonce, détachable)
```

**US2, US3 et US4 sont parallélisables entre eux** une fois US1 livré : ils touchent des fichiers et
des tests distincts, sauf `cookbook_controller.js` que US2 et US4 modifient tous les deux — à
séquencer ou à relire ensemble.

### Within Each User Story

- Les tests s'écrivent **avant** l'implémentation et doivent échouer d'abord — en particulier T008
- La fondation avant les appelants ; l'appelant serveur avant l'appelant JavaScript
- Un récit se termine par sa vérification manuelle, la CI ne voyant ni le JS ni le rendu

### Parallel Opportunities

- **Phase 2** : T006 et T007 en parallèle (fichiers distincts), après T005
- **Phase 3** : T009 en parallèle de T008
- **Phase 4** : T015 en parallèle de T014
- **Phase 7** : T027 et T028 en parallèle (documents distincts)

---

## Parallel Example: Phase 2

```bash
# Après T005 (le composant existe), les deux fragments sont indépendants :
Task: "Créer templates/app/cookbook/_recipe_grid_items.html.twig"
Task: "Créer templates/app/cookbook/_recipe_grid_state.html.twig"
```

---

## Implementation Strategy

### MVP — US1 seul

1. Phase 1 (Setup) → l'état de départ est documenté
2. Phase 2 (Fondation) → la carte a une définition unique
3. Phase 3 (US1) → les deux chemins l'utilisent
4. **STOP et VALIDER** : T008 vert, comparaison avec la référence, défilement rejoué à la main
5. Livrable tel quel : **DU1 refermé**, et S3 avec lui — même si les phases 4 à 6 attendent

### Livraison incrémentale

1. MVP (phases 1-3) → **DU1 + S3** → PR livrable
2. + US2 (phase 4) → le dernier balisage client disparaît, S3 est **prouvé** par un test
3. + US3 (phase 5) → la source unique de l'URL est verrouillée par un test
4. + US4 (phase 6) → le défilement n'est plus muet
5. Polish (phase 7) → audit et règles à jour, quickstart déroulé

---

## Notes

- **Aucun contrôleur Stimulus nouveau** : rien à enregistrer dans `assets/bootstrap.js` — sauf si la
  recette `empty` en apportait un, ce que T004 vérifie.
- **Aucune migration, aucune entité** : la donnée vient de l'API Cookbook.
- **`insertAdjacentHTML` reste employé** et reste sûr : la chaîne vient du serveur, où Twig a
  échappé chaque champ. Le risque venait de l'interpolation, pas de la méthode.
- **Les deux tests de dégradation de `PublicRoutesTest` ne doivent pas être modifiés.** S'ils
  demandent à l'être, c'est que le contrat 503 a bougé — ce que le lot interdit (FR-007).
- Commiter par tâche ou par groupe cohérent, en Conventional Commits : `refactor(front)` pour la
  fondation et US1, `fix(front)` pour la tranche qui referme S3, `feat(front)` pour US4.
