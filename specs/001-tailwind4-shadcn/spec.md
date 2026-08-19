# Feature Specification: Tailwind 4 et le kit shadcn

**Feature Branch**: `001-tailwind4-shadcn`

**Created**: 2026-08-19

**Status**: Draft

**Input**: User description: "Étape 5 de l'audit — migrer Tailwind CSS 3.4 vers Tailwind 4 ET adopter le kit shadcn du Symfony UX Toolkit, en un seul lot."

## Contexte

Radiant est un portfolio : **le rendu est le produit**. Une régression visuelle ou une perte
d'accessibilité coûte davantage ici qu'un retard, puisque le site sert à démontrer une compétence.

L'interface est aujourd'hui écrite en utilitaires posés directement dans les gabarits, avec une
palette ardoise sombre et un accent ambre déclarés dans la configuration du moteur CSS. Il n'existe
aucun composant réutilisable au sens du kit : la réutilisation passe par des partiels Twig inclus à
la main.

Ce lot fait deux choses en même temps — le moteur CSS et le système de composants — parce que la
palette devrait sinon être portée deux fois : une fois vers un jeu de tokens neutre, une seconde
vers le modèle de tokens du kit.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Le site rend à l'identique sous le nouveau moteur CSS (Priority: P1)

Un visiteur parcourt le portfolio, ouvre le Taquin, joue à Motus, consulte les recettes, remplit le
formulaire de contact. Il ne perçoit **aucune différence** : mêmes couleurs, mêmes espacements, même
comportement responsive, mêmes états au survol et au focus.

**Why this priority**: c'est le socle. Tant que le moteur n'est pas basculé, aucun composant du kit
ne peut fonctionner. Et c'est la tranche la plus risquée : elle touche toutes les pages d'un coup.

**Independent Test**: livrable seul, sans aucun composant du kit. Se teste en comparant chaque écran
avant/après sur trois largeurs (mobile, tablette, bureau), et en rejouant les parcours des mini-apps.

**Acceptance Scenarios**:

1. **Given** le site tel qu'il est en production, **When** le moteur CSS est basculé, **Then** les
   neuf pages publiques rendent visuellement à l'identique aux trois largeurs de référence.
2. **Given** une partie de Motus en cours, **When** le joueur propose un mot, **Then** les trois
   états (bien placée, mal placée, absente) gardent leur couleur **et** leur redondance non
   chromatique — la nuance étant que ces classes sont posées par JavaScript et n'apparaissent dans
   aucun gabarit.
3. **Given** la grille du Taquin, **When** le joueur navigue aux flèches, **Then** le focus reste
   visible et la tuile se déplace comme avant.
4. **Given** le formulaire de contact, **When** il s'affiche, **Then** les champs conservent une
   bordure visible et un contraste conforme.

---

### User Story 2 - L'identité visuelle tient dans des tokens (Priority: P2)

Le propriétaire du site veut pouvoir changer l'accent ambre, ou la profondeur de l'ardoise, **en un
seul endroit**, sans parcourir les gabarits.

**Why this priority**: c'est ce qui rend le kit utilisable. Sans cette étape, le premier composant
installé arrivera avec sa palette d'origine et jurera avec le reste.

**Independent Test**: modifier la valeur d'un token et constater que l'accent change partout où il
est employé, sans autre modification.

**Acceptance Scenarios**:

1. **Given** la palette exprimée en tokens, **When** la valeur de l'accent est changée, **Then**
   tous les éléments accentués suivent, sur toutes les pages.
2. **Given** un composant fraîchement installé depuis le kit, **When** il est affiché sans
   personnalisation, **Then** il adopte l'identité du site et non celle du kit.

---

### User Story 3 - Le tiroir « Dans les coulisses » devient un composant du kit (Priority: P3)

Un visiteur ouvre le tiroir qui explique comment une mini-app est faite, navigue entre ses onglets
au clavier, le ferme avec Échap, et retrouve le focus là où il l'avait laissé.

**Why this priority**: c'est la démonstration que le kit apporte quelque chose. Ce tiroir
réimplémente aujourd'hui à la main un comportement de dialogue modal — piège au focus, retrait de
l'arbre d'accessibilité quand il est fermé, navigation entre onglets — que le composant du kit
fournit et maintient à notre place.

**Independent Test**: livrable après US1 et US2, écran par écran. Se teste au clavier seul, plus une
passe automatisée d'accessibilité, sur les trois mini-apps qui l'incluent.

**Acceptance Scenarios**:

1. **Given** une page de mini-app, **When** le visiteur ouvre le tiroir au clavier, **Then** le
   focus entre dedans et n'en sort pas tant qu'il est ouvert.
2. **Given** le tiroir ouvert, **When** le visiteur presse Échap, **Then** il se ferme et le focus
   revient sur l'élément qui l'avait ouvert.
3. **Given** le tiroir fermé, **When** le visiteur navigue à la tabulation, **Then** aucun de ses
   éléments n'est atteignable ni annoncé.
4. **Given** les quatre sections de contenu du tiroir, **When** le visiteur passe de l'une à
   l'autre, **Then** la navigation fonctionne aux flèches et l'onglet actif est annoncé comme tel.

---

### Edge Cases

- **Les classes posées par JavaScript.** Motus applique ses états depuis un contrôleur, et la carte
  recette du Cookbook construit son balisage en JavaScript. Ces classes n'existent dans aucun
  gabarit : si le nouveau moteur ne les voit pas, elles disparaissent **silencieusement**, sans
  erreur, et le défaut n'apparaît qu'en jouant.
- **Le gabarit d'icône résolu dynamiquement.** La grille d'accueil compose le nom du partiel à
  partir du slug de chaque mini-app. Toute réorganisation des partiels doit préserver ce contrat,
  sous peine de casser **la page d'accueil** et non la page concernée.
- **Le thème de formulaire.** Le formulaire de contact s'appuie sur un thème prévu pour une version
  antérieure du moteur CSS. Il a déjà produit une régression de contraste par le passé : champs sans
  bordure visible.
- **Le back-office.** L'outil d'administration embarque ses propres feuilles de style, indépendantes
  de celles du site. Elles ne doivent ni être affectées, ni se retrouver mêlées à celles du site.
- **La construction des assets vient de l'intégration continue**, pas d'un poste de développement :
  un rendu correct en local ne prouve rien sur le rendu livré.
- **Aucun garde-fou automatisé sur le CSS ni le JavaScript.** Une régression de style ne fera
  échouer aucun contrôle : elle ne se voit qu'à l'œil.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Le site MUST rendre visuellement à l'identique après la bascule du moteur CSS, sur les
  neuf pages publiques et aux trois largeurs de référence.
- **FR-002**: Les classes appliquées à l'exécution par JavaScript MUST continuer d'être stylées.
  Leur inventaire MUST être établi explicitement **avant** la bascule, et non découvert après.
- **FR-003**: La palette du site MUST être exprimée en tokens, de sorte qu'un changement d'accent ou
  de fond se fasse en un seul endroit.
- **FR-004**: Un composant installé depuis le kit MUST adopter l'identité du site sans
  personnalisation à chaque appel.
- **FR-005**: Le tiroir « Dans les coulisses » MUST être rendu par le composant de dialogue du kit,
  et MUST conserver au minimum le comportement clavier actuel : piège au focus, fermeture par Échap,
  retour du focus à l'élément déclencheur, retrait de l'arbre d'accessibilité à l'état fermé,
  navigation entre onglets aux flèches.
- **FR-006**: Chaque écran modifié MUST rester conforme RGAA/EAA (WCAG 2.1 AA) : navigable au
  clavier seul, focus visible, contrastes conformes, aucune information portée par la seule couleur.
  Un écran qui régresse sur ce point n'est pas livrable.
- **FR-007**: Le contrat de résolution dynamique des icônes de la grille d'accueil MUST être
  préservé.
- **FR-008**: Les feuilles de style du back-office MUST rester inchangées et distinctes de celles du
  site.
- **FR-009**: La configuration MUST déclarer explicitement les sources de classes, plutôt que de
  s'en remettre à une détection automatique.
- **FR-010**: Toute dépendance dont le projet dépend MUST être déclarée explicitement, et non
  héritée d'une autre dépendance.
- **FR-011**: La bascule du moteur MUST être livrable indépendamment de l'adoption des composants :
  le site doit être correct et livrable à la fin de US1, sans qu'aucun composant du kit ne soit
  installé.
- **FR-012**: Chaque écran touché MUST être vérifié à la main, écran par écran, et cette
  vérification MUST être consignée — aucun contrôle automatisé ne couvre le rendu.

### Non-Functional Requirements

- **NFR-001**: Le poids de la feuille de style livrée ne MUST pas dépasser 120 % de l'existant.
  Au-delà, c'est le signe que l'élagage des classes inutilisées ne fonctionne pas.
- **NFR-002**: Ce lot ne MUST comporter aucune migration de base de données.

### Key Entities

Aucune donnée métier n'est touchée. Les seules entités concernées le sont **indirectement** : la
grille d'accueil et le tiroir lisent les mini-apps enregistrées, dont les colonnes JSON alimentent
les quatre sections du tiroir. Leur structure ne change pas.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Sur les neuf pages publiques et aux trois largeurs de référence, **aucune différence
  visuelle perceptible** avant/après — hors changements délibérés, qui sont listés nommément.
- **SC-002**: Les trois états de Motus restent distinguables **en niveaux de gris**, donc sans
  recourir à la couleur.
- **SC-003**: Les trois mini-apps et le formulaire de contact sont **intégralement utilisables au
  clavier seul**, sans piège au focus involontaire.
- **SC-004**: Une passe automatisée d'accessibilité ne remonte **aucune violation de niveau AA** sur
  les pages modifiées — au minimum autant qu'avant, l'objectif étant d'améliorer.
- **SC-005**: Changer la couleur d'accent en **un seul endroit** se répercute sur l'ensemble du
  site.
- **SC-006**: La feuille de style livrée ne dépasse pas 120 % de son poids actuel.
- **SC-007**: Le tiroir « Dans les coulisses » n'est plus atteignable au clavier lorsqu'il est
  fermé, sur les trois mini-apps.

## Assumptions

- **Le lot ne change pas l'identité visuelle**, il change la manière dont elle est exprimée. Toute
  évolution esthétique est une décision séparée.
- **Le périmètre s'arrête aux pages publiques.** Le back-office n'est pas retouché.
- **Aucune traduction n'est introduite.** Les textes restent écrits en français dans les gabarits.
- **Le kit reste sur la ligne compatible avec la version de PHP compilée par l'intégration
  continue** — la ligne suivante exige une version que la CI ne fournit pas, bien que le serveur de
  production, lui, la fournisse.
- **La construction des assets reste à la charge de l'intégration continue.**
- **Les trois largeurs de référence** sont mobile, tablette et bureau, au sens des points de rupture
  déjà employés par le site.

## Dependencies

- Le kit impose un système de composants aujourd'hui présent, mais **seulement comme dépendance
  indirecte** d'un autre paquet : il doit être revendiqué explicitement.
- La vérification écran par écran suppose de pouvoir charger le site localement, avec des données
  représentatives en base et l'API externe joignable — ou explicitement simulée.

## Risques identifiés

| Risque | Pourquoi il est réel ici | Conséquence si ignoré |
|---|---|---|
| Les classes posées par JavaScript disparaissent | Elles n'apparaissent dans aucun gabarit ; c'est déjà la raison d'un contournement dans la feuille de style actuelle | Motus perd ses couleurs sans qu'aucune erreur ne soit levée |
| La palette actuelle **écrase** des noms de couleurs standards | L'accent ambre du site n'est pas l'ambre par défaut ; un composant du kit qui y ferait référence prendrait la mauvaise teinte | Incohérence visuelle diffuse, difficile à diagnostiquer |
| Le thème de formulaire n'est pas prévu pour cette version | Il a déjà supprimé les bordures des champs par le passé | Régression de contraste, donc de conformité |
| Rien ne détecte une régression de style | Ni linter, ni test visuel, ni test de bout en bout | Le défaut part en production et n'est vu que par un visiteur |
| Le déploiement est automatique au merge | Toute intégration continue verte sur la branche principale déclenche une livraison | Aucune fenêtre de relecture entre le merge et la mise en ligne |

## Clarifications nécessaires

- **FR-013**: L'étendue de la composantisation dans ce lot est
  [NEEDS CLARIFICATION: se limite-t-on au tiroir modal, ou reprend-on aussi les éléments récurrents
  — boutons, cartes, pastilles de tags, champs de formulaire ?]
- **FR-014**: Le traitement des thèmes clair et sombre est
  [NEEDS CLARIFICATION: le site est aujourd'hui en ardoise sombre figée, alors que le kit livre une
  paire clair/sombre. Fige-t-on un thème unique, ou ouvre-t-on la bascule ?]
