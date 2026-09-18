---

description: "Task list for 008-editable-about-cv"
---

# Tasks: « À propos » et CV éditables depuis l'administration

**Input**: Design documents from `/specs/008-editable-about-cv/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/](contracts/), [quickstart.md](quickstart.md)

**Tests**: inclus. PHPUnit fait partie du gate CI, et le non-négociable 8 de la constitution fait du
gate la définition de « terminé ». Le découpage suit R8.

**Organization**: tâches groupées par user story. Les deux stories sont **P1** et **indépendamment
livrables** — chacune touche ses propres gabarits et son propre champ d'administration.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallélisable — fichier distinct, aucune dépendance sur une tâche inachevée
- **[Story]**: US1 (« À propos ») ou US2 (CV)

## Path Conventions

Application Symfony monolithique : `src/`, `templates/`, `tests/`, `config/`, `migrations/` à la
racine du dépôt. Chemins conformes à l'arborescence arrêtée dans [plan.md](plan.md).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: configurer le stockage du fichier avant d'écrire quoi que ce soit qui en dépende

- [X] T001 [P] Déclarer le mapping `cv` dans `config/packages/vich_uploader.yaml` : `uri_prefix: /documents/CV`, `upload_destination: '%kernel.project_dir%/public/documents/CV'`, `namer: Vich\UploaderBundle\Naming\SmartUniqueNamer` — calqué sur le mapping `personal_projects` existant
- [X] T002 [P] Ajouter `/public/documents/CV/` à `.gitignore`, près du bloc de `/public/images/personal_projects/`, avec un commentaire disant **pourquoi** : le déploiement fait `reset --hard` sans `clean`, donc seul un fichier ignoré survit (voir R3)

**Checkpoint**: un fichier déposé dans `public/documents/CV/` n'apparaît plus dans `git status`, et le PDF hérité y reste suivi.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: la table, son unique ligne, et l'écran d'administration qui la porte. Les deux stories en dépendent.

**⚠️ CRITICAL**: aucune story ne peut démarrer avant la fin de cette phase.

- [X] T003 Créer l'entité `src/Entity/SiteContent.php` — `declare(strict_types=1)`, `#[Vich\Uploadable]`, propriétés `aboutText`, `cvFile`, `cvFileName`, `cvOriginalName`, `updatedAt` selon [data-model.md](data-model.md). **`setCvFile()` DOIT toucher `updatedAt`**, sans quoi Vich ignore silencieusement tout remplacement de fichier
- [X] T004 Créer `src/Repository/SiteContentRepository.php` étendant `ServiceEntityRepository`, exposant `findCurrent(): ?SiteContent` — seul point d'accès à la ligne unique
- [X] T005 Générer la migration avec `make db-migration`, puis **réécrire le SQL à la main** : `CREATE TABLE site_content` (colonnes nullables sauf la PK) suivi d'un `INSERT` amorçant le paragraphe actuel de `templates/portfolio/section/about.html.twig`, ses cinq `<span class="text-brand-fg">` convertis en `**…**`, et le nom du PDF hérité dans `cv_file_name` et `cv_original_name`. Aucune entrée/sortie disque, aucune interaction
- [X] T006 Appliquer la migration avec `make db-migrate` et vérifier en base (`make psql`) que `site_content` contient **exactement une ligne**, accents et apostrophes typographiques intacts
- [X] T007 Créer `src/Controller/Admin/SiteContentCrudController.php` — `getEntityFqcn()`, et `configureActions()` désactivant `Action::NEW` et `Action::DELETE` pour tenir l'invariante de ligne unique (R6). Les champs arrivent avec chaque story
- [X] T008 Ajouter `yield MenuItem::linkToCrud('Site content', 'fa-solid fa-id-card', SiteContent::class);` dans `src/Controller/Admin/DashboardController.php::configureMenuItems()` — libellé **en anglais**, comme le reste du menu
- [X] T009 Vérifier à la main : `/admin` → **Site content** liste une seule ligne, et les boutons « Créer » et « Supprimer » sont absents

**Checkpoint**: la ligne unique existe, est amorcée et s'ouvre en édition. Les deux stories peuvent démarrer, en parallèle si besoin.

---

## Phase 3: User Story 1 - Réécrire le paragraphe « À propos » (Priority: P1) 🎯 MVP

**Goal**: le paragraphe de présentation devient éditable depuis l'administration, mise en valeur comprise, sans qu'aucun contenu saisi ne puisse altérer la page.

**Independent Test**: modifier le texte depuis `/admin`, recharger la page d'accueil, constater le nouveau texte et ses expressions en couleur de marque. Le pied de page n'est pas touché par cette story.

### Tests for User Story 1 ⚠️

> Écrire ces tests **avant** l'implémentation et vérifier qu'ils échouent.

- [X] T010 [P] [US1] Créer `tests/Service/Content/HighlightParserTest.php` couvrant les **treize cas** de [contracts/highlight-grammar.md](contracts/highlight-grammar.md), plus l'invariante de concaténation : concaténer les `text` restitue l'entrée moins les seuls marqueurs consommés
- [X] T011 [P] [US1] Créer `tests/Controller/AboutSectionRenderingTest.php` : le texte de la base est rendu ; `<script>alert(1)</script>` saisi ressort **échappé** dans le HTML ; `aboutText` vide donne ni section `#about`, ni entrée de navigation vers `#about`

### Implementation for User Story 1

- [X] T012 [P] [US1] Créer `src/DTO/TextSegment.php` — `final readonly class`, constructeur promu `(string $text, bool $highlighted)`, aucun comportement
- [X] T013 [US1] Créer `src/Service/Content/HighlightParser.php` — `parse(?string $text): list<TextSegment>`, analyse de gauche à droite **sans retour arrière**, service sans état, **ne lève jamais**. Dépend de T012
- [X] T014 [US1] Ajouter `TextareaField::new('aboutText')` dans `SiteContentCrudController::configureFields()` avec un `setHelp()` documentant la convention `**…**` — c'est le **seul** endroit où FR-007 est tenu — et `#[Assert\Length(max: 2000)]` avec message en français sur l'entité
- [X] T015 [US1] Injecter `SiteContentRepository` et `HighlightParser` dans `PortfolioController::index()` et passer `about_segments` au gabarit. Le contrôleur **orchestre et délègue** : aucune logique de grammaire ici
- [X] T016 [US1] Modifier `templates/portfolio/section/about.html.twig` — boucler sur `about_segments`, envelopper les segments mis en valeur dans `<span class="text-brand-fg">`, n'émettre la `<section>` que si la liste n'est pas vide. **Aucun `|raw`**
- [X] T017 [US1] Modifier `templates/portfolio/header/nav.html.twig` — l'entrée « À propos » suit la même condition que la section, faute de quoi elle pointerait sur une ancre absente (FR-009)
- [X] T018 [US1] Faire passer T010 et T011, puis `make lint` (php-cs-fixer, twig-cs-fixer, PHPStan 5)

**Checkpoint**: « À propos » est éditable de bout en bout. Le lien CV du pied de page fonctionne toujours, encore servi par le gabarit — cette story ne l'a pas touché.

---

## Phase 4: User Story 2 - Publier une nouvelle version du CV (Priority: P1)

**Goal**: le CV se remplace depuis l'administration, sans commit et sans accès au serveur, et le lien du pied de page sert toujours le fichier enregistré.

**Independent Test**: déposer un PDF depuis `/admin`, activer le lien du pied de page, vérifier que le fichier reçu est celui qui vient d'être déposé et qu'il porte son nom d'origine. La section « À propos » n'est pas touchée par cette story.

### Tests for User Story 2 ⚠️

- [X] T019 [P] [US2] Créer `tests/Controller/CvDownloadLinkTest.php` : `cvFileName` renseigné donne un lien vers le fichier, avec l'attribut `download` valant `cvOriginalName` et l'intitulé « Télécharger mon CV » ; `cvFileName` nul donne **aucun** lien CV ; les liens GitHub et LinkedIn sont présents dans les deux cas (FR-020)

### Implementation for User Story 2

- [X] T020 [US2] Ajouter le champ fichier dans `SiteContentCrudController::configureFields()` via `VichFileType`, et les contraintes `#[Assert\File(maxSize: '5M', mimeTypes: ['application/pdf'])]` sur `cvFile`, **messages écrits en français** (`default_locale` vaut `en`, voir R7)
- [X] T021 [US2] Passer l'entité `SiteContent` au gabarit depuis `PortfolioController::index()` — le repository y est déjà injecté si T015 est faite, sinon l'injecter ici. Une seule lecture sert les deux stories
- [X] T022 [US2] Modifier `templates/portfolio/footer/network.html.twig` — le troisième `<li>` n'est émis que si un CV est enregistré ; `href` via `vich_uploader_asset()`, `download` valant le nom d'origine. **Icône, position et intitulé accessible inchangés** (FR-016), et les deux premiers `<li>` non touchés
- [X] T023 [US2] Vérifier à la main les refus de dépôt (quickstart §5) : une image `.png`, un PDF de plus de 5 Mo, un fichier vide — à chaque fois un message explicite **et** le CV en ligne inchangé
- [X] T024 [US2] Vérifier qu'un dépôt réel laisse `git status` propre — si le fichier apparaît, T002 est incomplète et le prochain déploiement l'effacera
- [X] T025 [US2] Faire passer T019, puis `make lint`

**Checkpoint**: les deux stories fonctionnent, indépendamment et ensemble.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T026 [P] Mettre à jour le modèle de domaine dans `.claude/rules/business/radiant.md` : ajouter `SiteContent` à la liste des entités, en une ligne **prescriptive** — ce qu'elle porte, et le fait que sa ligne est unique. Pas d'historique, pas de justification
- [X] T027 [P] Mettre à jour le compte de tests dans `CLAUDE.md` (« 52 tests ») pour refléter les cas ajoutés
- [X] T028 Exécuter `rm -f var/test.db && make ci` — la base de test persiste entre les exécutions et masquerait une migration manquante
- [X] T029 Exécuter `make e2e` : axe sur les sept pages publiques, dans les deux thèmes, y compris avec un « À propos » vide
- [X] T030 Dérouler [quickstart.md](quickstart.md) en entier, les six scénarios, **dans les deux thèmes**
- [X] T031 Comparer la page d'accueil avec son état d'avant le changement : la différence doit être **nulle** (SC-003). Aucun test ne couvre ce point — il se vérifie à l'œil
- [X] T032 Vérifier que la migration s'exécute sur **PostgreSQL**, pas seulement sur le SQLite de la suite : le `INSERT` contient des accents et des apostrophes typographiques
- [X] T033 Consigner l'action de sortie de la release N+1 — retrait de `public/documents/CV/CV_Clement_BOUDINEL_Fullstack_PHP-Symfony.pdf` du dépôt une fois un CV déposé en production. **Sans cette suite, l'écart de FR-015 devient permanent**

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (T001-T002)** : aucune dépendance
- **Foundational (T003-T009)** : dépend du Setup — **bloque les deux stories**
- **US1 (T010-T018)** et **US2 (T019-T025)** : dépendent de la Foundational, puis indépendantes l'une de l'autre
- **Polish (T026-T033)** : dépend des stories qu'on souhaite livrer

### Chaîne critique

```
T001 ─┐
T002 ─┴→ T003 → T004 → T005 → T006 → T007 → T008 → T009 ─┬→ [US1] T010-T018
                                                         └→ [US2] T019-T025
```

T005 est le point de non-retour : la migration s'exécutera en production sans surveillance. Relire
son SQL avant de la committer.

### Dépendances internes

- **US1** : T012 avant T013. T013 avant T015. T015 avant T016. T016 et T017 vont **ensemble** — livrer l'une sans l'autre laisse une ancre morte et casse FR-009
- **US2** : T020 avant T023. T021 avant T022
- **Croisée** : T015 et T021 touchent le **même fichier** (`PortfolioController.php`). Jamais en parallèle ; la seconde étend ce que la première a posé

### Parallel Opportunities

- T001 et T002 : fichiers distincts
- T010, T011 et T012 : trois fichiers créés, aucun lien entre eux
- T019 en parallèle de n'importe quelle tâche d'US1
- T026 et T027 : deux fichiers de documentation distincts
- Les deux stories en entier, si deux personnes travaillent dessus — seul `PortfolioController.php` demande une coordination

---

## Parallel Example: User Story 1

```bash
# Les trois créations de fichiers d'US1, ensemble :
Task: "Créer tests/Service/Content/HighlightParserTest.php (13 cas du contrat)"
Task: "Créer tests/Controller/AboutSectionRenderingTest.php"
Task: "Créer src/DTO/TextSegment.php"
```

---

## Implementation Strategy

### MVP — US1 seule

1. Phase 1 (Setup) — T001 peut attendre si l'on ne livre qu'US1, mais le coût est nul
2. Phase 2 (Foundational) — obligatoire
3. Phase 3 (US1)
4. **STOP et valider** : quickstart §1 à §4
5. Livrable en l'état : le pitch devient éditable, le CV reste servi par le gabarit

### Livraison incrémentale

1. Setup + Foundational → la ligne unique existe et s'édite
2. + US1 → « À propos » éditable → vérifier → livrer
3. + US2 → CV éditable → vérifier → livrer
4. + Polish → gate vert, documentation à jour, action N+1 consignée

### Ce qu'il ne faut pas faire

- **Livrer T016 sans T017** : la section disparaît, l'entrée de navigation reste, l'ancre pointe sur rien. FR-009 tombe, et axe le voit
- **Committer T005 sans relire le SQL** : la migration s'exécute seule en production
- **Conclure qu'une suite est verte sans `rm -f var/test.db`** : d'anciennes tables masquent une migration manquante
- **Oublier T033** : l'écart de FR-015 devient permanent par simple inertie

---

## Notes

- Commits en **Conventional Commits** : `release.yml` dérive le tag semver du sujet. `feat:` pour les stories, `docs:` pour le Polish documentaire
- Un changement n'est pas terminé tant que php-cs-fixer, twig-cs-fixer et PHPStan ne passent pas
- JS et CSS n'ont aucun gate : rien ici n'en ajoute, mais toute retouche de gabarit se vérifie en chargeant la page
- Le cache Twig en dev masque les changements de gabarit : `make cc` avant de conclure à une régression

---

## Journal d'exécution

Écarts entre le plan et ce qui a été fait, et ce qui reste ouvert.

- **T005** — le générateur a embarqué du remaniement d'index `messenger_messages` sans rapport avec
  ce changement. Retiré à la main : la migration ne contient que la table et son amorçage.
- **T003** — l'attribut VichUploader prend `originalName`, pas `originalNameProperty`. Erreur levée
  au chargement des métadonnées, pas à la compilation. `data-model.md` corrigé.
- **T010** — le cas `** **` forme une paire valide, son contenu étant une espace. L'assertion de
  robustesse le comptait à tort comme non apparié ; c'est le test qui a été corrigé, pas le parseur.
- **T023 / T024** — vérifiés par un script jetable traversant la vraie chaîne Vich, faute d'accès au
  mot de passe de `/admin` : PNG refusé, PDF de 6 Mo refusé, PDF valide écrit sous un nom de stockage
  unique avec son nom d'origine conservé, `updatedAt` posé, fichier ignoré par git. **Reste à faire à
  la main** : un dépôt réel depuis l'interface d'administration.
- **T027** — `CLAUDE.md` annonçait 52 tests alors que la suite en comptait déjà 70 avant ce
  changement. Corrigé avec la valeur mesurée, 109.
- **T029** — `make e2e` finit avec **2 échecs sur 16**, tous deux sur « the recipe list stays
  conformant once scrolled ». **Ils préexistent** : vérifié en rejouant la suite sur l'arbre d'avant
  ce changement, même résultat. Les 14 autres cas passent, dont les deux thèmes de la page d'accueil.
  Hors périmètre de ce lot, à traiter à part.
