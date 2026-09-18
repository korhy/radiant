# Feature Specification: « À propos » et CV éditables depuis l'administration

**Feature Branch**: `008-editable-about-cv`

**Created**: 2026-09-18

**Status**: Draft

**Input**: User description: "I want to be able to edit the following from the site administration: the \"About\" section of my portfolio; the links and the CV file in the footer"

> **Périmètre arrêté après clarification (2026-09-18)** : parmi les éléments du pied de page, seul
> le **fichier CV** devient éditable. Les liens GitHub et LinkedIn changent trop rarement pour
> justifier le coût ; ils restent écrits dans le gabarit.

## Contexte

Le portfolio édite déjà par le back-office ce qui change souvent : les expériences, les projets
personnels et les mini-applications. Deux contenus y échappent encore et restent figés dans le code.

| Contenu | Où il vit aujourd'hui | Ce que coûte une modification |
|---|---|---|
| Paragraphe « À propos » | texte français inline dans le gabarit de la section | édition du gabarit, commit, attente du déploiement |
| Fichier CV | binaire versionné dans le dépôt, référencé par son chemin | commit d'un PDF, puis déploiement complet |

Ce sont pourtant **les deux contenus les plus volatils du site**. Le paragraphe « À propos » est le
pitch : il se réécrit à chaque inflexion de positionnement. Le CV est mis à jour à chaque nouvelle
mission, certification ou correction de mise en forme — et c'est le document qu'un recruteur
emporte. Aujourd'hui, corriger une virgule dans le pitch ou publier une version corrigée du CV
impose le même cycle qu'un changement de code, avec en prime, pour le CV, le remplacement d'un
binaire dans l'historique du dépôt.

L'objectif est de ramener ces deux contenus au même régime que le reste du site : **modifiables
depuis l'administration, visibles immédiatement, sans déploiement**.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Réécrire le paragraphe « À propos » (Priority: P1)

Le propriétaire du site se connecte à l'administration, ouvre l'entrée « À propos », remplace le
paragraphe de présentation par une version plus courte en marquant trois expressions clés,
enregistre, puis recharge la page publique. Le nouveau texte y est affiché, les expressions marquées
portant la couleur de marque exactement comme celles du texte précédent.

**Why this priority**: c'est le contenu le plus souvent retouché et le plus coûteux à changer
aujourd'hui. Livré seul, il supprime déjà un cycle commit/déploiement complet à chaque
reformulation.

**Independent Test**: testable seul. On modifie le texte depuis l'administration et on vérifie la
page d'accueil : le nouveau texte apparaît, l'ancien a disparu, les expressions marquées sont mises
en valeur, et la structure de la section (titre, ancre de navigation, repères d'accessibilité) est
inchangée.

**Acceptance Scenarios**:

1. **Given** un texte « À propos » enregistré depuis l'administration, **When** un visiteur ouvre la
   page d'accueil, **Then** ce texte s'affiche dans la section « À propos », sans redéploiement.
2. **Given** un texte comportant des expressions marquées avec la convention de saisie, **When** la
   page est rendue, **Then** ces expressions portent la couleur de marque et le reste du paragraphe
   reste en texte courant.
3. **Given** un texte contenant du balisage saisi par erreur ou collé depuis une autre source,
   **When** la page est rendue, **Then** ce balisage s'affiche comme du texte et ne modifie ni la
   structure, ni le style, ni le comportement de la page.
4. **Given** un marquage mal formé — ouvert et jamais refermé, ou imbriqué, **When** la page est
   rendue, **Then** le paragraphe reste lisible, aucun fragment de convention n'est visible en
   double, et la mise en page n'est pas cassée.
5. **Given** aucun texte « À propos » enregistré, **When** la page d'accueil est demandée,
   **Then** la page se rend sans erreur et la navigation reste cohérente.

---

### User Story 2 - Publier une nouvelle version du CV (Priority: P1)

Le propriétaire du site a mis à jour son CV. Il ouvre l'administration, dépose le nouveau PDF à la
place de l'ancien, enregistre. Le lien de téléchargement du pied de page sert désormais le nouveau
fichier ; l'ancien n'est plus proposé.

**Why this priority**: c'est le contenu dont la mise à jour est aujourd'hui la plus lourde — un
binaire dans un commit — et le plus visible pour un recruteur. Livrable indépendamment de la
section « À propos ».

**Independent Test**: testable seul. On dépose un fichier depuis l'administration, on suit le lien
du pied de page et on vérifie que le fichier téléchargé est bien celui qui vient d'être déposé.

**Acceptance Scenarios**:

1. **Given** un CV déposé depuis l'administration, **When** un visiteur active le lien de
   téléchargement du pied de page, **Then** il reçoit ce fichier.
2. **Given** un nouveau CV déposé en remplacement d'un précédent, **When** un visiteur active le
   lien, **Then** il reçoit la nouvelle version, sans que la page publique ait besoin d'un
   déploiement ni d'une purge manuelle.
3. **Given** un fichier refusé parce qu'il n'est pas au format attendu ou qu'il dépasse la taille
   admise, **When** l'enregistrement est tenté, **Then** l'administration l'explique et le CV en
   ligne reste inchangé.
4. **Given** aucun CV déposé, **When** la page d'accueil est rendue, **Then** aucun lien de
   téléchargement mort n'est affiché, et le reste du pied de page est intact.

---

### Edge Cases

- **Contenu vidé.** Que voit un visiteur si le texte « À propos » est effacé ou si aucun CV n'est
  déposé ? La page doit se rendre sans erreur, sans zone vide, sans ancre de navigation pointant
  vers rien et sans lien mort.
- **Caractères et balisage inattendus.** Apostrophes typographiques, accents, emoji, ou balisage
  collé depuis un traitement de texte : le texte s'affiche tel que saisi, sans jamais être
  interprété comme de la structure de page.
- **Convention de marquage mal formée.** Marqueur ouvert sans fermeture, marqueurs imbriqués,
  marqueur vide : rendu prévisible et lisible, jamais de page cassée.
- **Texte très long ou très court.** Un paragraphe de trois lignes comme un paragraphe de trente
  doivent rester lisibles dans les deux thèmes, sans débordement horizontal ni chevauchement de la
  colonne suivante.
- **Fichier CV inattendu.** Fichier vide, extension trompeuse, document de plusieurs dizaines de
  méga-octets : refus explicite à l'enregistrement, contenu en ligne inchangé.
- **Remplacement du CV pendant un téléchargement.** Un visiteur ayant déjà ouvert le lien reçoit la
  version disponible au moment de sa requête ; aucune corruption ni erreur n'est acceptable.
- **Contenu de secours au premier démarrage.** Sur une base fraîche, le site doit rester présentable
  et ne pas exposer une section « À propos » cassée.

## Requirements *(mandatory)*

### Functional Requirements

**Section « À propos »**

- **FR-001**: Le propriétaire du site DOIT pouvoir lire et remplacer le texte de la section
  « À propos » depuis l'administration, sans intervention sur le code ni déploiement.
- **FR-002**: Le texte enregistré DOIT être celui affiché aux visiteurs dès la requête suivante.
- **FR-003**: Le texte DOIT permettre de marquer des expressions au moyen d'une **convention de
  saisie simple et documentée** (`**expression**`) ; les expressions ainsi marquées sont rendues
  avec la couleur de marque du site.
- **FR-004**: La convention de marquage DOIT être le **seul effet disponible** : aucun autre style,
  lien, liste ou structure ne peut être introduit depuis le champ de saisie.
- **FR-005**: Un marquage mal formé — non refermé, imbriqué ou vide — DOIT produire un rendu
  prévisible et lisible, sans casser la mise en page ni laisser de marqueur résiduel visible.
- **FR-006**: Le système DOIT empêcher qu'un contenu saisi ou collé altère la structure, le style ou
  le comportement de la page publique.
- **FR-007**: La convention de saisie DOIT être rappelée au propriétaire du site à l'endroit où il
  saisit le texte ; elle ne doit pas dépendre d'une mémoire ou d'une documentation externe.
- **FR-008**: Le titre de la section, son ancre de navigation et les repères d'accessibilité
  associés NE DOIVENT PAS dépendre du contenu saisi ; ils restent définis par le site.
- **FR-009**: En l'absence de texte enregistré, le système DOIT rendre la page sans erreur et sans
  laisser de section vide ni d'entrée de navigation pointant vers rien.

**Fichier CV**

- **FR-010**: Le propriétaire du site DOIT pouvoir déposer un fichier CV depuis l'administration et
  remplacer celui actuellement en ligne.
- **FR-011**: Le système DOIT refuser, avec un message explicite, un fichier dont le format ou la
  taille sort des limites admises, et laisser le CV en ligne inchangé.
- **FR-012**: Le lien de téléchargement public DOIT servir le fichier actuellement enregistré, sans
  déploiement ni purge manuelle.
- **FR-013**: En l'absence de CV enregistré, le système NE DOIT PAS afficher de lien de
  téléchargement.
- **FR-014**: Le fichier CV NE DOIT PAS être versionné dans le dépôt, et son remplacement NE DOIT
  PAS dépendre d'un accès au serveur.
- **FR-015**: Le remplacement d'un CV NE DOIT PAS laisser l'ancien fichier accessible indéfiniment
  une fois le nouveau publié.
- **FR-016**: Le lien de téléchargement DOIT conserver sa place, son icône et son intitulé
  accessible actuels dans le pied de page.

**Transverses**

- **FR-017**: Ces deux contenus NE DOIVENT être modifiables que par un compte administrateur
  authentifié ; aucun accès public en écriture, et aucune modification possible depuis la page
  publique.
- **FR-018**: L'apparence publique après reprise des contenus actuels DOIT être indiscernable de
  l'apparence actuelle, dans les deux thèmes.
- **FR-019**: Les contenus existants aujourd'hui écrits dans le code — le paragraphe avec ses cinq
  expressions mises en valeur, et le CV courant — DOIVENT être disponibles comme contenu initial,
  sans saisie ni dépôt manuel après mise en ligne.
- **FR-020**: Les liens GitHub et LinkedIn du pied de page DOIVENT rester inchangés et continuer de
  fonctionner à l'identique.

### Key Entities

- **Texte « À propos »** : le paragraphe de présentation affiché sur la page d'accueil. Contenu
  unique — il n'en existe qu'un à la fois. Porte le texte, marquage de mise en valeur compris.
- **CV** : le document proposé au téléchargement dans le pied de page. Unique lui aussi : un seul
  fichier publié à un instant donné. Porte le fichier et la date de sa dernière mise à jour.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Le propriétaire du site peut réécrire le paragraphe « À propos », y compris ses
  expressions mises en valeur, et le voir en ligne en moins de 2 minutes — sans écrire de code,
  sans commit et sans déploiement.
- **SC-002**: Publier une nouvelle version du CV ne demande plus aucune manipulation de fichier sur
  le serveur ni dans le dépôt : 0 commit, 0 accès SSH.
- **SC-003**: Après reprise des contenus actuels, une comparaison visuelle de la page d'accueil
  avant/après ne relève aucune différence, dans les deux thèmes.
- **SC-004**: Les pages publiques concernées restent sans anomalie d'accessibilité détectée, dans
  les deux thèmes, quel que soit le contenu enregistré — y compris vide.
- **SC-005**: Aucun contenu saisi depuis l'administration ne peut faire exécuter du code ou altérer
  la mise en page côté visiteur, vérifié sur un jeu de saisies hostiles couvrant balisage, script
  et marquage mal formé.
- **SC-006**: Sur une base fraîche, la page d'accueil se rend sans erreur avant toute saisie dans
  l'administration.
- **SC-007**: Le propriétaire du site retrouve la convention de marquage sans consulter de
  documentation extérieure, dès sa première édition.

## Assumptions

- **Un seul éditeur.** Le site a un unique compte administrateur ; ni édition concurrente, ni
  workflow de relecture, ni brouillon, ni publication différée.
- **Pas d'historique de versions.** Enregistrer écrase la valeur précédente. Revenir en arrière
  suppose de ressaisir le texte ou de redéposer le fichier.
- **Pas de prévisualisation dédiée.** La vérification se fait en ouvrant la page publique, comme
  pour les expériences et les projets aujourd'hui.
- **Langue.** Les contenus saisis sont en français, comme tout le texte visiteur ; rien n'est
  traduit et aucun catalogue de traduction n'est introduit par cette fonctionnalité.
- **Périmètre fermé.** Seuls le paragraphe « À propos » et le fichier CV deviennent éditables. Les
  liens GitHub et LinkedIn, le nom, le titre professionnel, le portrait, le titre de la section, les
  entrées de navigation interne et les autres sections restent hors périmètre.
- **Le CV reste un document unique.** Une seule version publiée à la fois ; pas de CV par langue ni
  par poste ciblé.
- **Stockage des fichiers déposés.** Le dépôt de fichiers depuis l'administration existe déjà pour
  les projets personnels ; cette fonctionnalité s'appuie sur le même mécanisme et hérite de ses
  contraintes de persistance en production — notamment le fait que le répertoire de dépôt doit
  survivre aux déploiements.
- **Pas d'anciennes adresses à préserver.** L'adresse publique du CV peut changer d'une version à
  l'autre ; aucun engagement n'est pris sur la stabilité de l'ancienne adresse.
- **Le marquage `**…**` est retenu** parce qu'il est déjà familier et lisible tel quel dans le champ
  de saisie ; du texte contenant littéralement deux astérisques consécutives est considéré comme un
  cas marginal acceptable.
