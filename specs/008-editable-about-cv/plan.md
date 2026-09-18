# Implementation Plan: « À propos » et CV éditables depuis l'administration

**Branch**: `008-editable-about-cv` | **Date**: 2026-09-18 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/008-editable-about-cv/spec.md`

## Summary

Deux contenus figés dans le code — le paragraphe « À propos » et le fichier CV — passent en base et
deviennent éditables depuis EasyAdmin, au même régime que les expériences et les projets.

L'approche tient en trois décisions, développées dans [research.md](research.md) :

1. **Un agrégat unique `SiteContent`** (ligne unique, un écran d'administration) plutôt que deux
   entités singleton — R1.
2. **La mise en valeur est rendue par des segments typés**, jamais par du HTML assemblé en PHP : le
   service produit de la donnée, Twig produit le balisage et l'échappe. C'est la conception qui
   ferme FR-006, pas une précaution à maintenir — R2.
3. **Le fichier déposé survit au déploiement parce qu'il est ignoré par git**, `deploy.yml` faisant
   `reset --hard` sans `clean`. Corollaire imposé : l'amorçage du CV hérité **s'étale sur deux
   releases** — R3 et R4.

## Technical Context

**Language/Version**: PHP 8.2 (plafond de la CI ; le serveur tourne 8.4, la CI est la contrainte)

**Primary Dependencies**: Symfony 7.4, Doctrine ORM 3, EasyAdmin 4.13, VichUploader 2.4, Twig 3,
symfony/validator 7.4. **Aucune dépendance nouvelle** — un moteur Markdown a été explicitement
écarté (R2)

**Storage**: PostgreSQL 16 en production, SQLite pour la suite de tests. Le binaire du CV vit sur le
système de fichiers du serveur, sous `public/documents/CV/`, ignoré par git (R3)

**Testing**: PHPUnit — unitaires sur la grammaire, fonctionnels sur le rendu de la page d'accueil ;
Playwright + axe pour l'accessibilité, déjà en place comme gate CI

**Target Platform**: serveur OVH, déploiement par CI sur commit vert sur `main`, migrations jouées
automatiquement et sans surveillance

**Project Type**: application web Symfony monolithique, rendu serveur

**Performance Goals**: aucune contrainte propre. Deux lectures de base en plus par affichage de la
page d'accueil, sur une table d'une ligne — hors de tout budget mesurable

**Constraints**: migration rétro-compatible et non interactive ; aucun SQL de dialecte dans les
tests ; accessibilité WCAG 2.1 AA opposable ; texte visiteur en français, identifiants en anglais

**Scale/Scope**: un éditeur, une ligne, un fichier. 4 fichiers PHP créés, 4 modifiés, 3 gabarits
touchés, 1 migration, 2 fichiers de configuration

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Non-négociable | Statut | Comment il est tenu |
|---|---|---|
| 1. Typage strict, PHP 8.2 | **PASS** | `declare(strict_types=1)` dans les 4 fichiers créés ; propriétés, paramètres et retours typés. `final readonly class` — disponible en 8.2. Aucune syntaxe 8.3+ |
| 2. Sécurité par Symfony | **PASS** | Aucune route nouvelle. L'écriture passe par `/admin`, déjà couvert par `access_control`. Aucune comparaison de rôle en dur, aucun voter nécessaire — l'autorisation ne dépend pas de l'objet |
| 3. Couches | **PASS** | La grammaire vit dans `src/Service/Content/`, l'accès aux données dans `SiteContentRepository`. `PortfolioController` orchestre et délègue. Aucune requête dans un gabarit |
| 4. Identifiants anglais, UI française | **PASS** | `SiteContent`, `HighlightParser`, `TextSegment`, menu « Site content ». Le texte visiteur reste français, désormais en base au lieu du gabarit — ce qui ne change pas la règle : toujours pas de catalogue de traduction |
| 5. Migrations non surveillées et rétro-compatibles | **PASS** | Une migration, `CREATE TABLE` + `INSERT`. Colonnes nullables, aucune destruction, aucune entrée/sortie disque, aucune interaction. Le code antérieur ignore la table et continue de tourner |
| 6. Conventional Commits | **PASS** | `feat:` pour la fonctionnalité — déclenche une mineure, ce qui est correct |
| 7. Accessibilité opposable | **PASS** | Section et entrée de navigation disparaissent **ensemble** quand le texte est vide (FR-009) : pas d'ancre morte. Le lien CV disparaît entièrement plutôt que d'être désactivé. Aucune couleur nouvelle : `text-brand-fg` porte déjà son contraste mesuré |
| 8. Le gate définit le « terminé » | **PASS** | php-cs-fixer, twig-cs-fixer, PHPStan 5 et PHPUnit. Les gabarits touchés passent aussi la passe axe |

**Verdict avant Phase 0** : aucune violation. Une réserve temporaire, consignée en *Complexity
Tracking*, sur la fenêtre de deux releases du CV hérité.

**Re-vérification après Phase 1** : le design confirme les huit lignes. Deux points méritent d'être
nommés, parce qu'ils se sont précisés à la conception :

- Le non-négociable 2 gagne un argument : avec des `TextSegment`, l'injection de balisage n'est pas
  *filtrée*, elle est **structurellement impossible**. Le type est la garantie.
- Le non-négociable 5 est respecté grâce à un choix explicite : aucune copie de fichier dans la
  migration (R4). Une migration qui fait des entrées/sorties disque peut lever, et une migration qui
  lève laisse la production avec du code neuf sur un schéma ancien — l'état que `deploy.yml`
  s'emploie précisément à rendre impossible.

## Project Structure

### Documentation (this feature)

```text
specs/008-editable-about-cv/
├── plan.md                          # Ce fichier
├── spec.md                          # Exigences, clarifiées
├── research.md                      # Phase 0 — huit décisions
├── data-model.md                    # Phase 1 — SiteContent, TextSegment, migration
├── quickstart.md                    # Phase 1 — six scénarios de vérification
├── contracts/
│   ├── highlight-grammar.md         # La grammaire `**…**`, treize cas
│   └── cv-download-link.md          # Ce que rend le pied de page, et quand
├── checklists/
│   └── requirements.md              # 16/16
└── tasks.md                         # Phase 2 — produit par /speckit-tasks
```

### Source Code (repository root)

```text
src/
├── Entity/
│   └── SiteContent.php              # CRÉÉ — agrégat à ligne unique, #[Vich\Uploadable]
├── Repository/
│   └── SiteContentRepository.php    # CRÉÉ — findCurrent(): ?SiteContent
├── DTO/
│   └── TextSegment.php              # CRÉÉ — final readonly, (text, highlighted)
├── Service/
│   └── Content/
│       └── HighlightParser.php      # CRÉÉ — parse(?string): list<TextSegment>
├── Controller/
│   ├── PortfolioController.php      # MODIFIÉ — injecte le repository et le parseur
│   └── Admin/
│       ├── SiteContentCrudController.php  # CRÉÉ — NEW et DELETE désactivées
│       └── DashboardController.php        # MODIFIÉ — entrée de menu « Site content »
templates/portfolio/
├── section/about.html.twig          # MODIFIÉ — boucle sur les segments, rendu conditionnel
├── header/nav.html.twig             # MODIFIÉ — l'entrée « À propos » suit la section
└── footer/network.html.twig         # MODIFIÉ — le 3e <li> devient conditionnel
config/packages/
└── vich_uploader.yaml               # MODIFIÉ — mapping `cv`
migrations/
└── VersionYYYYMMDDHHMMSS.php        # CRÉÉ — table + amorçage
tests/
├── Service/Content/HighlightParserTest.php  # CRÉÉ — les treize cas du contrat
└── Controller/SiteContentRenderingTest.php  # CRÉÉ — rendu conditionnel de la page d'accueil
.gitignore                           # MODIFIÉ — /public/documents/CV/
```

**Structure Decision** : aucune structure nouvelle. Le code se range dans les emplacements que le
dépôt impose déjà — entités et repositories à plat, services sous `src/Service/<Domaine>/`,
contrôleurs EasyAdmin sous `src/Controller/Admin/`, gabarits du portfolio sous
`templates/portfolio/**`. Le seul répertoire créé est `src/Service/Content/`, qui suit exactement la
forme de `src/Service/Motus/` et `src/Service/Cookbook/`.

`src/Twig/` **n'est pas créé** : il n'y a aucun filtre Twig, puisqu'il n'y a aucun HTML à produire
côté PHP (R2). Le dépôt n'a aujourd'hui aucune extension Twig, et cette fonctionnalité ne lui en
donne pas la première.

## Complexity Tracking

> Une seule entrée : un écart temporaire, structurellement imposé.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|---|---|---|
| **FR-015 n'est pleinement tenu qu'à la release N+1.** Le PDF hérité reste suivi par git pendant cette release ; après un premier dépôt, le `git reset --hard` du déploiement le restaure et il redevient accessible à son URL d'origine, sans être référencé | Le déploiement remet l'arbre à l'état de `main` **avant** de jouer les migrations. Un fichier retiré du dépôt à la release N disparaîtrait du serveur avant que quoi que ce soit ne puisse l'amorcer, et la ligne seedée pointerait sur un fichier absent | *Copier le fichier dans `postUp()`* : fait entrer des entrées/sorties disque dans une migration non surveillée et ne referme pas la fenêtre — le fichier hérité, toujours suivi, revient au déploiement suivant de la même manière. *Branche de repli dans le service* : ajoute un chemin de code mort dès le premier dépôt, à retirer à la release N+1 de toute façon. *Renoncer à l'amorçage* : contredit FR-019, explicitement validée |

**Action de sortie** : une fois un CV déposé depuis l'administration en production, un commit dédié
retire `public/documents/CV/CV_Clement_BOUDINEL_Fullstack_PHP-Symfony.pdf` du dépôt. Sans cette
suite, l'écart devient permanent — c'est le dernier point de la liste de contrôle du
[quickstart](quickstart.md).

## Ce que le plan ne fait pas

- **Les liens GitHub et LinkedIn ne deviennent pas éditables** — tranché à la clarification Q2. Ils
  restent dans le gabarit, et FR-020 en fait une non-régression vérifiée.
- **Aucun catalogue de traduction n'est introduit.** Les messages de validation resteront en anglais
  par défaut ; c'est pourquoi ceux de cette fonctionnalité sont écrits en français sur les
  contraintes (R7). Le constat général reste ouvert, et hors périmètre.
- **`public/documents/CV/CV_champs_formulaires_en_ligne.md` n'est pas touché.** Ce document de
  travail est suivi par git et servi publiquement ; le signalement est dans
  [research.md](research.md), la décision appartient au propriétaire.
