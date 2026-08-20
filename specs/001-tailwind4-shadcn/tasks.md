---
description: "Task list for Tailwind 4 et le kit shadcn"
---

# Tasks: Tailwind 4 et le kit shadcn

**Input**: Design documents from `/specs/001-tailwind4-shadcn/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/](contracts/ui-contracts.md), [quickstart.md](quickstart.md)

**Tests**: aucune tâche de test automatisé. La spéc ne le demande pas, et **aucun test ne peut
couvrir ce lot** : ni php-cs-fixer, ni twig-cs-fixer, ni PHPStan, ni les 37 tests PHPUnit ne
regardent une couleur, un contraste ou une bordure. Les tâches de vérification sont **manuelles** et
sont des tâches à part entière, pas une formalité de fin.

**Organization**: groupées par user story, chacune livrable seule.

## Format: `[ID] [P?] [Story] Description`

- **[P]** : parallélisable (fichiers distincts, aucune dépendance en cours)
- **[Story]** : US1, US2, US3

---

## Phase 1: Setup — le relevé de référence

**Purpose**: sans ces captures, **SC-001 n'est pas vérifiable** et le lot ne peut pas être validé.
Cette phase se fait avant toute modification du moteur.

- [X] T001 Vérifier que la base de dev contient les trois lignes `App` avec leurs quatre colonnes JSON remplies (`make psql`, table `app`) — un panneau vide ne prouverait rien en US3
- [X] T002 **Bloquant** — démarrer l'API Cookbook en local. `/app/cookbook` est la page la plus dynamique du site (cartes construites en JavaScript) : l'exclure du relevé la soustrairait à toute comparaison, précisément là où le risque de purge est le plus fort. Ne pas poursuivre tant qu'elle ne rend pas
- [X] T003 Construire en mode production (`npm run build`) puis relever le poids **brut et gzippé** de `public/build/app.*.css` dans `specs/001-tailwind4-shadcn/baseline/weight.txt` — référence de NFR-001 et SC-006
- [X] T004 [P] Capturer les **24 écrans** de référence (8 pages publiques × 375/768/1280 px) dans `specs/001-tailwind4-shadcn/baseline/`, nommés `<page>-<largeur>.png`. `/admin` n'en fait pas partie : il exige une authentification et renvoie 302 anonymement — le capturer connecté, à part
- [X] T005 [P] Consigner dans `specs/001-tailwind4-shadcn/baseline/renames.md` la liste nominative des 24 occurrences à renommer, fichier et ligne (4 `outline-none`, 2 `shadow`, 18 bordures sans couleur) — voir [research.md](research.md) R2
- [X] T006 Repasser en build de développement (`npm run dev`) pour ne pas travailler sur des noms de fichiers hachés

---

## Phase 2: Foundational — la plomberie du moteur

**Purpose**: bascule mécanique, sans intention visuelle. Bloque US1, US2 et US3.

**⚠️ T008 à T010 vont dans le même commit que T013.** `outline-hidden` n'existe pas en Tailwind 3
et `outline-none` change de sens en Tailwind 4 : livrer la bascule sans les renommages laisserait le
site sans contour de focus.

- [X] T007 Installer `tailwindcss@^4` et `@tailwindcss/postcss`, retirer `tailwindcss@3` et `autoprefixer` de `package.json`
- [X] T008 Réécrire `postcss.config.js` avec `@tailwindcss/postcss` pour unique plugin — Tailwind 4 intègre le préfixage ([research.md](research.md) R3)
- [X] T009 Dans `assets/styles/app.css`, remplacer les trois directives `@tailwind` par `@import 'tailwindcss'`, et déclarer `@source '../../templates'` **et** `@source '../../assets'` — ce second est ce qui couvre les utilitaires posés par JavaScript, contrat C4
- [X] T010 Porter les surcharges de `tailwind.config.js` (`amber-400/500/600/700`, `bg-blue-950`) dans un bloc `@theme` de `assets/styles/app.css`, **à valeurs identiques**, puis supprimer `tailwind.config.js`
- [X] T011 Laisser le bloc Motus (`assets/styles/app.css` lignes 11-347) **inchangé et hors `@layer`** — Tailwind 4 n'élague pas le CSS de l'auteur, la contrainte de Tailwind 3 disparaît ([research.md](research.md) R1). Ne pas « ranger » ce bloc dans ce lot
- [X] T012 Vérifier que `npm run dev` et `npm run build` passent tous les deux sans erreur

---

## Phase 3: User Story 1 — Le site rend à l'identique (P1) 🎯 MVP

**Goal**: un visiteur ne perçoit aucune différence. Le moteur a changé, le rendu non.

**Independent Test**: comparer les 24 écrans au relevé de référence et rejouer les parcours des
mini-apps. Livrable sans aucun composant du kit.

- [X] T013 [US1] Appliquer les 6 renommages listés en T005 dans `templates/**` et `assets/controllers/*.js` : `outline-none` → `outline-hidden`, `shadow` → `shadow-sm`. Si l'outil de migration officiel de Tailwind est employé, **relire son diff intégralement** plutôt que lui faire confiance
- [X] T014 [US1] **Sans objet, vérifié le 2026-08-19.** Les 18 bordures annoncées étaient un artefact de comptage par jeton : contrôle fait par élément, toutes portent déjà un `border-<couleur>`. Zéro correction nécessaire
- [X] T015 [US1] Remplacer les usages de `bg-bg-blue-950` (double préfixation héritée) par le token correspondant dans `templates/**`
- [X] T016 [US1] Vérifier Motus à l'écran : jouer un mot, contrôler les trois états en couleur **et** en relief non chromatique — contrat C4, ces classes viennent du JavaScript (SC-002)
- [X] T017 [US1] Vérifier le Cookbook : faire défiler jusqu'au chargement d'une page suivante, les cartes ajoutées à l'exécution doivent être stylées comme les initiales
- [X] T018 [US1] Vérifier le Taquin au clavier : le contour de focus est visible sur les tuiles — c'est le contrôle des `outline-none` renommés
- [X] T019 [US1] Vérifier le formulaire de contact : les champs gardent une bordure visible et un contraste conforme (WCAG 1.4.11)
- [X] T020 [US1] Vérifier que `/admin` est visuellement inchangé — contrat C5, séparation avec le back-office
- [X] T021 [US1] Comparer les 24 écrans au relevé de T004 et consigner les écarts délibérés dans `specs/001-tailwind4-shadcn/baseline/diff.md` (SC-001)
- [X] T022 [US1] **Fait le 2026-08-19.** Reconstruire en production et vérifier que `public/build/app.*.css` reste sous **15 Ko gzippés** (SC-006, NFR-001). Mesuré : **9 707 octets** — conforme
- [X] T023 [US1] `make ci` puis livrer la tranche — **vert ne vaut pas validation ici**, seules T016 à T022 valident

**Checkpoint**: le site tourne sous Tailwind 4, à rendu identique. Livrable en l'état.

---

## Phase 4: User Story 2 — L'identité tient dans des tokens (P2)

**Goal**: changer l'accent en un seul endroit, et servir un thème clair conforme.

**Independent Test**: modifier la valeur du token d'accent, constater que tout le site suit ; puis
contrôler les contrastes du thème clair.

**⚠️ T024 est une décision esthétique, pas un réglage.** C'est le seul endroit du lot où l'on crée
au lieu de reproduire.

- [X] T024 [US2] Choisir et consigner dans `specs/001-tailwind4-shadcn/light-theme.md` la teinte d'accent du thème clair. **L'accent actuel `#faa734` donne 1,97:1 sur blanc** et `amber-700` `#b76a14` plafonne à 4,13:1 : les deux échouent AA pour du texte. Candidats mesurés : `#a35c12` (5,13:1), `#8a4f0f` (6,55:1) — voir [research.md](research.md) R5 **Décidé le 2026-08-20 : deux rôles pour l'ambre.** L'ambre de marque reste l'accent de fond (6,73:1 avec `--accent-on`) ; `#a35c12` sert d'accent en avant-plan (5,13:1). Consigné dans [light-theme.md](light-theme.md).
- [X] T025 [US2] Définir les tokens du thème sombre dans `:root` de `assets/styles/app.css` — fond, texte, surfaces, bordures, accent — aux valeurs actuelles du site, pour que le sombre reste la référence de non-régression Faits aux valeurs relevées dans le rendu existant, à trois exceptions mesurées et consignées.
- [X] T026 [US2] Exposer ces tokens aux utilitaires par un bloc `@theme inline`, en supprimant les surcharges de la palette standard posées en T010 `@theme inline` en place ; les surcharges de `amber-*` et `bg-blue-950` ont disparu.
- [X] T027 [US2] Déclarer le thème clair sous `@media (prefers-color-scheme: light)` avec la teinte retenue en T024 Thème clair déclaré sous `prefers-color-scheme: light`, valeurs décidées et non héritées.
- [X] T028 [US2] Remplacer dans `templates/**` les couleurs littérales restantes par les tokens — contrat C3 : plus aucune couleur en dur dans un gabarit **Zéro couleur littérale dans `templates/**` et `assets/controllers/`**, vérifié par relevé. Les 38 variantes `dark:` de `contact` et `login` disparaissent : le thème passe par les tokens.
- [X] T029 [US2] Vérifier SC-005 : changer la valeur du token d'accent, reconstruire, constater que tout le site suit, puis rétablir **Vérifié à l'écran le 2026-08-20** : `--brand-amber` basculé sur un cyan, tout le site a suivi ; rétabli. Chaque couleur de marque n'apparaît qu'une fois dans les 165 Ko livrés.
- [X] T030 [US2] Contrôler chaque couple texte/fond du thème clair contre les seuils AA (4,5:1 texte, 3:1 grand texte et interface) et consigner les mesures dans `light-theme.md` **42 paires mesurées, 0 échec.** Six écarts détectés au premier passage, tous corrigés. Table complète dans [light-theme.md](light-theme.md).
- [X] T031 [US2] Vérifier les **48 écrans** — 8 pages publiques × 3 largeurs × 2 thèmes (SC-008), plus `/admin` connecté dans les deux thèmes. Le thème clair n'a pas de référence à comparer : il se contrôle au contraste Écrans parcourus dans les deux thèmes aux trois largeurs. **Un défaut trouvé et corrigé** : les tuiles du Taquin étaient blanches sur carte blanche en thème clair.
- [X] T032 [US2] Vérifier que basculer la préférence système ne perd pas l'état en cours : partie de Motus, grille du Taquin, saisie du formulaire **Vérifié** : une partie de Motus en cours survit intacte au changement de préférence — le thème est purement CSS, aucun rechargement.
- [X] T033 [US2] Passe automatisée d'accessibilité (axe ou Lighthouse) sur les pages modifiées, **dans les deux thèmes** (SC-004) **axe-core, 6 pages × 2 thèmes : 0 violation.** Deux défauts préexistants trouvés et corrigés (contraste de la touche ENTRER, `aria-label` interdit sur les cases de Motus).
- [X] T034 [US2] `make ci` puis livrer la tranche **Fait le 2026-08-20.** `make ci` vert (php-cs-fixer 0, twig-cs-fixer 0, PHPStan 0, PHPUnit 37/37) et poids livré à **10 456 octets gzippés** sur un plafond de 15 360.

**Checkpoint**: identité pilotée par tokens, deux thèmes conformes. Livrable en l'état.

---

## Phase 5: User Story 3 — Le panneau « Behind the scenes » sur le kit (P3)

**Goal**: remplacer un dialogue modal écrit à la main par le composant du kit, sans rien perdre.

**Independent Test**: dérouler le contrat C2 au clavier seul sur les trois mini-apps.

- [X] T035 [US3] Déclarer `symfony/ux-twig-component` dans `composer.json` — il est **déjà installé** en dépendance transitive d'EasyAdmin, mais une dépendance dont on dépend se déclare ([research.md](research.md) R7) Déclaré en `^2.36` ; il n'était installé qu'en transitif d'EasyAdmin.
- [X] T036 [US3] Ajouter le mapping `defaults: App\Twig\Components\: 'components/'` à `config/packages/twig_component.yaml`, qui existe déjà avec `anonymous_template_directory` Mapping `defaults` ajouté.
- [X] T037 [US3] [P] Ajouter en `require` les dépendances d'exécution des composants copiés : `twig/html-extra`, `tales-from-a-dev/twig-tailwind-extra`, `symfony/ux-icons` Les trois déclarés en `require`. **`tales-from-a-dev/twig-tailwind-extra` n'embarque ni bundle ni configuration** et sa recette est une recette contrib, que Flex n'applique pas : l'extension et son runtime sont câblés à la main dans `config/services.yaml`, faute de quoi chaque composant lève « Unknown "tailwind_merge" filter ».
- [X] T038 [US3] [P] Ajouter `symfony/ux-toolkit:^2.36` en `require-dev` — la ligne 3.x exige PHP >= 8.4 et la CI compile en 8.2 Installé en `require-dev`. La commande est `ux:install`, pas `ux:install <c> --kit`.
- [X] T039 [US3] [P] Installer `tw-animate-css` (npm) et l'importer dans `assets/styles/app.css` après `@import 'tailwindcss'` Installé et importé juste après `@import 'tailwindcss'`.
- [X] T040 [US3] Installer le composant : `php bin/console ux:install dialog --kit shadcn`, puis relire les fichiers copiés — ils deviennent du code du projet 9 fichiers copiés, relus. `Dialog:Content` s'appuie sur l'élément **natif `<dialog>`** : piège au focus, Échap, inertie de l'arrière-plan et retour du focus viennent du navigateur. `dialog_controller.js` enregistré à la main dans `bootstrap.js`.
- [X] T041 [US3] Vérifier FR-004 : afficher un composant du kit **sans aucune personnalisation** sur une page de test, et constater qu'il prend les tokens du site et non ceux du kit. Si ce n'est pas le cas, la réconciliation de la palette (US2) est incomplète et la reprise du panneau doit attendre **Vérifié dans les deux thèmes.** Les composants parlent le vocabulaire shadcn (`bg-background`, `text-foreground`, `border`, `ring-ring`) : il est mappé sur les rôles du site dans `@theme inline`. `accent` ayant chez shadcn le sens d'une surface de survol discrète, les jetons de marque ont été renommés `brand-*` pour lui laisser la place.
- [X] T042 [US3] Réécrire `templates/components/_app_detail_drawer.html.twig` sur `<twig:Dialog>` en préservant les quatre onglets et les formes JSON attendues (`techStack`, `challenges`, `improvements`, `resources` — voir [data-model.md](data-model.md)) Le panneau est rendu par `Dialog` / `Dialog:Content` / `Dialog:Header` / `Dialog:Title` / `Dialog:Description`, ramené au bord droit par les classes passées à `Content` et fusionnées par `tailwind_merge`. Les quatre onglets et les formes JSON sont intacts.
- [X] T043 [US3] Trancher explicitement le sort de `{# item.title #}` dans l'onglet « À venir » : le titre est dans les données mais commenté dans le gabarit. Le reproduire ou le rétablir est une décision, pas un détail **Tranché par la donnée** : aucune ligne `improvements` des trois apps ne porte de `title`. Le titre est rendu sous garde `is defined`, comme la description voisine — rien n'est émis aujourd'hui, une ligne qui en gagne un s'affichera sans retoucher le gabarit.
- [X] T044 [US3] Réduire ou supprimer `assets/controllers/app_detail_drawer_controller.js` selon ce que le composant prend en charge, et mettre à jour son enregistrement dans `assets/bootstrap.js` — un contrôleur non enregistré ne tourne jamais, en silence Le contrôleur tombe de 94 à 62 lignes : il ne garde que le motif `tablist`. `data-controller` étant **fusionné** et non écrasé par le composant, seul `app-detail-drawer` est passé.
- [X] T045 [US3] Dérouler le contrat C2 ligne à ligne au clavier seul, sur les **trois** mini-apps : ouverture, piège au focus, flèches entre onglets, Échap, retour du focus, et panneau fermé inatteignable à la tabulation (SC-003, SC-007) **42 contrôles sur 42**, au clavier seul, sur les trois mini-apps (Playwright). L'inertie du panneau fermé passe d'`inert` posé à la main à `display: none` natif.
- [X] T046 [US3] Vérifier le contrat C1 : la page d'accueil rend une icône par tuile. `make phpunit` couvre déjà ce point via `PublicRoutesTest::testHomepageRendersTheStreamDeckTileAndItsIconPartial` Couvert par `PublicRoutesTest::testHomepageRendersTheStreamDeckTileAndItsIconPartial`, vert.
- [X] T047 [US3] Passe automatisée d'accessibilité sur les trois mini-apps, dans les deux thèmes — la reprise doit **améliorer** l'accessibilité, jamais la dégrader **0 violation** sur 3 mini-apps × 4 onglets × 2 thèmes, panneau ouvert.
- [X] T048 [US3] Supprimer l'ancien partiel et son contrôleur une fois zéro usage confirmé par `grep` **Sans objet** : le partiel a été réécrit sur place, le contrôleur réduit et non supprimé. Aucune cible orpheline (`drawer`, `overlay`, `inert`) ne subsiste.
- [X] T049 [US3] `make ci` puis livrer la tranche `make ci` vert (37 tests, 65 assertions). Poids livré **11 306 octets gzippés** sur un plafond de 15 360.

**Checkpoint**: le kit est en place et prouvé sur un composant réel.

---

## Phase 6: Polish — remettre la documentation d'aplomb

**Purpose**: ces fichiers sont chargés à chaque session. Les laisser périmés propagerait l'erreur —
c'est déjà arrivé sur ce projet.

- [X] T050 [P] Corriger `.claude/rules/technical/frontend-styling.md` : le passage sur `@source inline(...)` et le « problème de purge Motus » est **faux** ([research.md](research.md) R1). Réécrire la règle en Tailwind 4 au présent, sans section « cible » Réécrite au présent, en Tailwind 4 : sources déclarées, tokens en deux niveaux, AA dans les deux thèmes, plafond de poids. Plus de section « cible ».
- [X] T051 [P] Réécrire `.claude/rules/technical/components-shadcn.md` : retirer l'avertissement « le kit n'est pas installé » et la section des prérequis, devenus de l'historique. La règle énonce la convention en vigueur Réécrite : le kit est la convention en vigueur, `Dialog` pour les modales, le « Legacy move » pour reprendre un composant existant. Prérequis et avertissements retirés.
- [X] T052 [P] Mettre à jour `CLAUDE.md` : stack en Tailwind 4 + kit shadcn, et la mention des Twig Components Stack en Tailwind 4 + kit shadcn, Twig Components actifs, plafond PHP du kit rappelé.
- [X] T053 [P] Mettre à jour `.claude/rules/technical/frontend-twig.md` : les composants du kit deviennent la convention, les partiels `_*.html.twig` l'exception Les composants du kit deviennent la convention ; les partiels `_*.html.twig` l'exception. Contrastes et passe automatisée exigés **dans les deux thèmes**.
- [X] T054 Marquer l'Étape 5 comme faite dans `docs/audit/audit-2026-08-18.md`, et y consigner que DU2/DU3 restent ouverts (hors périmètre par FR-013) Étape 5 marquée traitée, avec la liste des défauts corrigés au-delà du périmètre. DU1/DU2/DU3 restent ouverts, hors périmètre par FR-013. `.claude/rules/business/radiant.md` suit.
- [ ] T055 Après merge, vérifier la production : les 8 routes répondent, le panneau s'ouvre, et les deux thèmes rendent — le déploiement part tout seul au merge sur `main`

---

## Dependencies

```
Phase 1 (Setup, T001-T006)
    └─> Phase 2 (Foundational, T007-T012)
            └─> Phase 3 (US1, T013-T023)  ← MVP, livrable seule
                    ├─> T050  (frontend-styling.md : dès que US1 est livrée)
                    └─> Phase 4 (US2, T024-T034)  ← livrable seule
                            └─> Phase 5 (US3, T035-T049)  ← livrable seule
                                    └─> Phase 6 restante (T051-T055)
```

**US2 dépend de US1** (les tokens remplacent la palette portée en T010) et **US3 dépend de US2**
(un composant du kit installé avant les tokens afficherait la palette du kit). Le découpage reste
utile : chaque tranche est livrable et vérifiable seule, et l'on peut s'arrêter après n'importe
laquelle.

**La Phase 6 n'est pas parallèle aux user stories, contrairement à ce qu'on pourrait croire d'une
phase de finition.** T051 et T052 décrivent un état où le kit **est** installé — les écrire avant la
Phase 5 produirait une règle fausse, et les règles sont chargées à chaque session. Seul **T050**
(la règle de style, dont le passage sur la purge Motus est erroné) peut suivre immédiatement US1.

## Parallélisation

- **Phase 1** : T004 et T005 en parallèle (captures et inventaire, sans rapport).
- **Phase 5** : T037, T038 et T039 en parallèle (trois gestionnaires de paquets distincts).
- **Phase 6** : T050 à T053 en parallèle (quatre fichiers distincts).
- **Phases 3 et 4** : peu de parallélisme réel — les tâches se succèdent sur `app.css` et sur les
  mêmes gabarits, et les tâches de vérification supposent l'état stabilisé.

## Implementation Strategy

**MVP = Phase 1 + Phase 2 + Phase 3.** Le site tourne sous Tailwind 4 à rendu identique. C'est
livrable, c'est vérifiable, et c'est ce qui débloque le reste.

**Livrer tranche par tranche.** Le déploiement part au merge sur `main` : une tranche à moitié
vérifiée est une tranche en production. Chaque *Checkpoint* est un point d'arrêt légitime.

**Le point le plus risqué n'est pas la bascule** — 24 occurrences, surface étroite. C'est **T024**,
le choix de la teinte d'accent claire : le seul endroit sans référence avant/après à opposer à une
régression.
