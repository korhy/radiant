# Phase 0 — Recherche : « À propos » et CV éditables

**Feature**: `008-editable-about-cv` · **Date**: 2026-09-18

Huit décisions. Trois sont structurantes : la forme du rendu mis en valeur (R2), la persistance du
fichier déposé face au `git reset --hard` du déploiement (R3), et l'amorçage du contenu initial,
qui impose **deux releases** (R4).

---

## R1 — Un agrégat unique plutôt que deux entités

**Décision** : une entité `SiteContent`, à **ligne unique**, portant le texte « À propos » *et* le
fichier CV.

**Rationale** : les deux contenus partagent exactement le même cycle de vie — uniques, édités par le
même compte, sans relation à autre chose. Deux entités imposeraient deux CRUD, deux entrées de menu
et deux migrations pour deux champs. Un seul agrégat donne **un écran d'administration**,
« Site content », où le propriétaire trouve tout ce qui n'est ni une expérience, ni un projet, ni
une mini-app. Le nom est volontairement extensible : le titre professionnel ou le portrait pourront
l'y rejoindre sans nouvelle table.

**Alternatives écartées** :

- *Deux entités `AboutText` et `Cv`* — plus pur domainement, mais deux singletons dans un
  back-office qui compte déjà quatre entrées : coût de navigation réel, bénéfice nul.
- *Une table clé/valeur générique* — perd le typage, la validation par champ et le `VichUploader`.
  Exactement le genre de généricité que `backend-php.md` proscrit.

---

## R2 — La mise en valeur est rendue par des segments typés, jamais par du HTML produit en PHP

**Décision** : un service `HighlightParser` transforme le texte saisi en une liste de segments
typés (`TextSegment{ text: string, highlighted: bool }`). Le gabarit boucle sur ces segments et
enveloppe lui-même ceux qui sont mis en valeur. **Aucun `|raw`, aucun HTML assemblé côté PHP.**

**Rationale** : c'est la leçon du constat **S3 / DU1** de ce dépôt — la carte recette assemblait du
balisage à la main et interpolait du texte non échappé. La solution retenue alors est retenue ici :
le code produit de la **donnée**, Twig produit le **balisage** et applique son échappement
automatique. Avec des segments, FR-006 (« aucun contenu saisi ne peut altérer la page ») n'est pas
une précaution à maintenir, c'est une propriété de la conception : il n'existe aucun chemin par
lequel du texte saisi puisse devenir de la structure.

**Alternatives écartées** :

- *Filtre Twig renvoyant du HTML marqué `is_safe: html`* — le plus court, et le seul qui réintroduit
  la faille de S3 : toute évolution du filtre peut laisser passer du balisage. Rejeté sur principe.
- *Un vrai moteur Markdown (`league/commonmark`)* — une dépendance et une surface entière (liens,
  images, HTML brut) pour un seul effet. FR-004 exige justement que ce soit le **seul** effet.
- *Éditeur WYSIWYG plus assainisseur HTML* — écarté à la clarification Q1.

**Grammaire retenue** : `**expression**`. Un marqueur non refermé, imbriqué ou vide est rendu
**littéralement**, tel que saisi (FR-005) : pas d'erreur, pas de fragment avalé. Le texte est traité
comme un bloc unique — retours à la ligne préservés, aucune notion de paragraphe, de liste ni de
lien (FR-004).

---

## R3 — Où atterrit le fichier déposé, et pourquoi c'est la vraie question

**Décision** : mapping VichUploader `cv`, destination `public/documents/CV/` — **le répertoire
actuel**, ajouté à `.gitignore` par cette release.

**Rationale** : le déploiement (`deploy.yml`) fait `git fetch && git reset --hard origin/main` et
**ne fait délibérément pas `git clean`** — le commentaire du workflow le dit explicitement, pour que
`public/.htaccess` et `composer.phar` survivent. Conséquence directe, et c'est elle qui commande
tout le reste :

| Nature du fichier sur le serveur | Sort au déploiement suivant |
|---|---|
| Non suivi par git (dépôt depuis l'admin) | **survit** |
| Suivi par git, modifié ou supprimé sur le serveur | **restauré à l'état de `main`** |

C'est le mécanisme qui fait déjà survivre `public/images/personal_projects/`, ignoré depuis
`.gitignore:26`. Un CV déposé depuis l'administration hérite exactement de la même garantie :
FR-014 est satisfait par le fait que le fichier est **ignoré**, pas par le répertoire choisi.

Rester dans `public/documents/CV/` garde l'URL publique de la famille de fichiers existante et le
répertoire que le propriétaire connaît. Le `.gitignore` n'affecte pas le fichier hérité, qui reste
suivi — c'est précisément ce dont R4 a besoin.

**Alternative écartée** : *un nouveau répertoire `public/uploads/cv/`* — plus net sur le papier,
mais il oblige à **copier** le fichier hérité pour amorcer le contenu (voir R4), et cette copie ne
peut se faire que depuis une migration faisant des entrées/sorties disque. Aucun gain : la fenêtre
de transition de R4 est identique dans les deux cas.

---

## R4 — L'amorçage du contenu initial impose deux releases

**Décision** : **Release N** (cette fonctionnalité) — la migration crée la ligne `SiteContent`, avec
le paragraphe actuel *marqué* et `cvFileName` pointant sur le PDF hérité, qui **reste suivi par
git**. **Release N+1** — une fois un CV déposé depuis l'administration, le binaire hérité est retiré
du dépôt par un commit dédié.

**Rationale** : c'est une contrainte structurelle, pas un choix de confort. Le déploiement remet
l'arbre à l'état de `main` **avant** de jouer les migrations. Donc :

1. Si la release N retirait le PDF du dépôt, le `reset --hard` l'effacerait du serveur **avant** que
   quoi que ce soit ne puisse l'amorcer — la ligne seedée pointerait sur un fichier absent.
2. Aucune migration ne peut copier un fichier qui n'existe plus au moment où elle s'exécute.

Le fichier hérité doit donc rester suivi tant qu'il est la seule source, et ne peut être retiré
qu'**après** avoir été remplacé. Deux releases, sans échappatoire.

**Conséquence assumée** : entre les deux releases, le PDF hérité reste accessible à son URL
d'origine même après un premier dépôt — le `reset --hard` le restaure. FR-015 (« ne pas laisser
l'ancien fichier accessible indéfiniment ») est donc satisfait **à l'issue de la release N+1**, pas
à la release N. C'est consigné dans le *Complexity Tracking* du plan, et c'est le seul écart
temporaire de la fonctionnalité.

**Le texte, lui, est amorcé sans réserve** : la migration insère le paragraphe actuel avec ses cinq
expressions marquées, en SQL, dans le `up()`. FR-019 est satisfait dès la release N pour
« À propos ».

**Alternatives écartées** :

- *Copie du fichier dans `postUp()`* — décale le problème sans le résoudre (voir R3) et fait entrer
  des entrées/sorties disque dans une migration qui s'exécute sans surveillance en production. Une
  migration qui lève casse le déploiement à mi-parcours, l'état que `deploy.yml` s'emploie
  justement à rendre impossible.
- *Branche de repli dans le service* — « si aucun dépôt, servir le chemin hérité » : un chemin de
  code mort dès le premier dépôt, à retirer ensuite. Même release N+1, plus du code en plus.
- *Fixtures ou commande console lancée en SSH* — contredit FR-019 et l'esprit de la fonctionnalité.

---

## R5 — Le nom du fichier téléchargé reste lisible

**Décision** : `SmartUniqueNamer` pour le nom de stockage, comme `personal_projects`, plus
`originalNameProperty` pour conserver le nom d'origine, servi dans l'attribut `download` du lien.

**Rationale** : un nom de stockage unique évite les collisions et l'empoisonnement de cache ; mais
un recruteur ne doit pas recevoir `5f3a...-1.pdf` dans ses téléchargements. L'attribut `download`
accepte une valeur de nom de fichier et s'applique en même origine — ce qui est le cas, le fichier
étant servi par le site. Aucune route ni contrôleur n'est nécessaire.

**Alternative écartée** : *une route servant le fichier avec un `Content-Disposition`* — un
contrôleur, un test et une surface d'exposition supplémentaires pour un résultat identique.

---

## R6 — Un écran d'administration pour une ligne unique

**Décision** : `SiteContentCrudController` classique, avec `NEW` et `DELETE` désactivées dans
`configureActions()`. L'index liste l'unique ligne ; l'édition se fait d'un clic.

**Rationale** : robuste sans hypothèse sur l'identifiant. Désactiver `NEW` et `DELETE` fait de la
ligne unique une **invariante tenue par le back-office**, pas une convention orale.

**Alternative écartée** : *entrée de menu pointant directement sur l'édition de l'id 1*
(`->setAction(Action::EDIT)->setEntityId(1)`) — un clic de moins, mais l'identifiant `1` devient une
constante implicite qu'une base reconstruite invalide silencieusement.

---

## R7 — Validation du dépôt

**Décision** : contraintes `Assert\File` sur la propriété `File` — `application/pdf` uniquement,
`maxSize: '5M'` — avec messages écrits en français.

**Rationale** : le CV est un document de candidature ; le PDF est le seul format qui traverse les
ATS sans dégât. 5 Mo laisse largement la place à un CV mis en page tout en bornant ce qu'un dépôt
peut consommer. La contrainte porte sur le **type déclaré et le contenu**, pas sur l'extension.

**Point de vigilance** : les messages de validation s'affichent aujourd'hui **en anglais**
(`default_locale: en`) — constat connu et ouvert du dépôt. Les messages sont donc écrits
explicitement en français sur les contraintes, plutôt que laissés aux messages par défaut.

---

## R8 — Ce qui est prouvé par un test

**Décision** : trois niveaux, ciblés sur ce qui peut réellement casser.

| Niveau | Ce qui est prouvé |
|---|---|
| Unitaire — `HighlightParserTest` | la grammaire : texte nu, un marqueur, plusieurs, non refermé, imbriqué, vide, `**` adjacentes, et du balisage saisi qui doit rester du texte |
| Fonctionnel — page d'accueil | le texte de la base est rendu ; le balisage saisi est échappé ; texte vide donne ni section ni entrée de navigation ; CV présent donne un lien vers la bonne cible ; CV absent, aucun lien |
| Accessibilité — `make e2e` (axe) | la page d'accueil reste sans anomalie dans les deux thèmes, y compris avec un « À propos » vide |

**Rationale** : la grammaire est de la logique pure — c'est là que les tests unitaires paient. Le
reste est du rendu conditionnel, que seul un test fonctionnel attrape.

**Le piège connu** : la suite tourne sur **SQLite**, la production sur **PostgreSQL**. Aucun test ici
ne doit s'appuyer sur du SQL de dialecte ; tous passent par l'ORM et le client HTTP. Et `var/test.db`
persiste entre les exécutions : le vider avant de conclure qu'une suite est verte.

---

## Constat incident, hors périmètre

`public/documents/CV/CV_champs_formulaires_en_ligne.md` est **suivi par git et servi publiquement**.
C'est un document de travail — une banque de réponses préparées pour les formulaires de candidature
— accessible à qui devine l'URL. Il n'y a là ni secret ni identifiant, mais ce n'est visiblement pas
un contenu destiné à la publication.

Cette fonctionnalité **n'y touche pas**. Le signalement est fait ici pour qu'il soit tranché
sciemment ; le `.gitignore` posé en R3 ne le couvre pas, puisqu'il est déjà suivi.
