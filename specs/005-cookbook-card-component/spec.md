# Feature Specification: Carte recette unique pour le Cookbook

**Feature Branch**: `005-cookbook-card-component`

**Created**: 2026-08-21

**Status**: Draft

**Input**: User description: "DU1 — dédupliquer la carte recette du Cookbook. La carte est écrite deux fois : en Twig pour le rendu initial, et en littéral de gabarit JS pour le défilement infini. Les deux ont déjà divergé, et le second est la cause racine du constat S3 (interpolation sans échappement). Objectif : une seule définition de la carte, rendue côté serveur, réutilisée par les deux chemins."

## Contexte

La liste des recettes du Cookbook se remplit par deux chemins. Le premier écran est rendu par le
serveur ; les suivants arrivent par défilement infini et sont assemblés par le navigateur. Chacun
possède **sa propre définition de la carte**, écrite à la main, et les deux ont déjà divergé.

Trois écarts sont constatés aujourd'hui :

| Aspect | Premier écran | Après défilement |
|---|---|---|
| Recette sans vignette | icône Cookbook du site | un cercle plein, sans rapport |
| Destination de la carte | générée depuis la définition de la route | chemin recomposé à la main |
| Texte venu de l'API | échappé | inséré tel quel dans du balisage |

Le troisième écart est le **constat S3** de l'audit : un titre contenant une apostrophe casse déjà
le rendu, et un titre contenant du balisage serait interprété. L'exploitation suppose un compte
Cookbook compromis — le risque immédiat est faible, la fragilité est réelle et permanente.

Ces trois écarts ont la même cause : **la carte n'a pas de définition unique**. Les corriger un par
un les laisserait réapparaître à la prochaine retouche. C'est le constat **DU1**, dont le traitement
referme **S3** au passage.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - La carte est la même partout (Priority: P1)

Une visiteuse ouvre la liste des recettes, fait défiler jusqu'à charger deux pages
supplémentaires, puis remonte. Les cartes qu'elle a sous les yeux sont **indiscernables** : même
mise en page, mêmes états au survol et au focus, et surtout même repli quand une recette n'a pas de
vignette — l'icône du Cookbook, jamais un placeholder différent.

**Why this priority**: c'est la valeur centrale du lot. Tant que deux définitions coexistent, toute
retouche d'apparence doit être faite deux fois et la divergence revient.

**Independent Test**: livrable seul. Se teste en comparant le balisage produit par les deux chemins
pour un même jeu de recettes, dont au moins une sans vignette, une sans catégorie et une sans durée.

**Acceptance Scenarios**:

1. **Given** une recette sans vignette servie au premier écran et une autre servie après défilement,
   **When** la liste est affichée, **Then** les deux affichent le même repli visuel.
2. **Given** une modification de l'apparence de la carte, **When** elle est appliquée une seule fois,
   **Then** elle se voit sur les deux chemins sans autre édition.
3. **Given** une recette sans catégorie et sans durée, **When** sa carte est rendue par l'un ou
   l'autre chemin, **Then** aucune zone vide ni séparateur orphelin n'apparaît.

---

### User Story 2 - Le contenu de l'API ne peut pas altérer la page (Priority: P1)

Une recette dont le titre contient une apostrophe, un chevron ou des guillemets s'affiche
littéralement, à l'identique sur les deux chemins. Aucun contenu venu de l'API ne peut introduire de
balisage ni déclencher de comportement dans la page.

**Why this priority**: même priorité que US1 parce que c'est le constat **S3**, et parce que la même
correction structurelle les traite tous les deux. Aujourd'hui une simple apostrophe suffit à casser
le rendu — le défaut est déjà visible en usage normal, pas seulement en attaque.

**Independent Test**: se teste avec un jeu de recettes piégées (apostrophe, `<`, `&`, `"`, une chaîne
ressemblant à une balise) servies par les deux chemins.

**Acceptance Scenarios**:

1. **Given** une recette dont le titre contient `L'entrecôte "façon <chef>"`, **When** sa carte est
   affichée, **Then** le titre s'affiche exactement ainsi, et la page reste intacte.
2. **Given** une recette dont un champ contient une chaîne ressemblant à du balisage exécutable,
   **When** sa carte est affichée, **Then** rien n'est exécuté et la chaîne est visible comme du texte.
3. **Given** le même titre piégé, **When** on compare le premier écran et le défilement,
   **Then** le rendu est identique.

---

### User Story 3 - La destination d'une carte a une seule source (Priority: P2)

Le chemin d'accès à une fiche recette est défini à un seul endroit. Si ce chemin change, les cartes
du premier écran **et** celles du défilement suivent, sans qu'aucune chaîne d'URL n'ait à être
retouchée à la main.

**Why this priority**: la panne serait silencieuse — les cartes chargées par défilement mèneraient à
une page inexistante alors que celles du premier écran fonctionneraient encore. Utile, mais sans
effet visible tant que la route ne bouge pas.

**Independent Test**: se teste en modifiant le chemin de la route en local et en vérifiant que les
deux chemins de rendu pointent toujours au bon endroit.

**Acceptance Scenarios**:

1. **Given** le chemin de la fiche recette modifié à sa source, **When** la liste est rechargée et
   défilée, **Then** toutes les cartes mènent à la fiche, quel que soit leur chemin de rendu.

---

### User Story 4 - Le chargement reste perceptible sans la vue (Priority: P3)

Une personne qui navigue au lecteur d'écran sait que de nouvelles recettes viennent d'être ajoutées
à la liste, au lieu de découvrir en tabulant que le nombre d'éléments a changé.

**Why this priority**: écart d'accessibilité préexistant du défilement infini, qu'il serait
incohérent de reconduire à l'identique dans une carte refaite. Indépendant du reste du lot.

**Independent Test**: se teste au lecteur d'écran, ou en vérifiant qu'une région dédiée annonce le
nombre de recettes ajoutées.

**Acceptance Scenarios**:

1. **Given** la liste au premier écran, **When** une page supplémentaire est chargée,
   **Then** l'ajout est annoncé sans déplacer le focus de l'utilisateur.
2. **Given** le service de recettes indisponible pendant le défilement, **When** le chargement
   échoue, **Then** l'échec est annoncé au même titre qu'il est affiché.

---

### Edge Cases

- **Recette incomplète** : ni vignette, ni catégorie, ni durée — la carte reste lisible et cliquable.
- **Vignette injoignable** : l'URL d'image répond 404 — la carte ne laisse pas de zone cassée.
- **Titre très long** : la carte garde sa hauteur de grille sans déborder.
- **Aucun résultat** : une recherche sans correspondance affiche le message d'état, pas une grille vide.
- **Service indisponible en cours de défilement** : le comportement acquis au lot précédent est
  préservé — panne annoncée comme telle, jamais comme « aucun résultat ».
- **Dernière page** : le déclencheur de défilement disparaît sans laisser de zone de chargement figée.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: La carte recette MUST avoir une **définition unique**, utilisée aussi bien au premier
  écran qu'au chargement par défilement.
- **FR-002**: Le navigateur MUST NOT assembler lui-même le balisage d'une carte : ce que le
  défilement ajoute à la liste vient de la même définition que le premier écran.
- **FR-003**: Tout contenu issu de l'API affiché dans une carte MUST être traité comme du texte —
  aucun champ ne peut introduire de balisage, y compris dans un attribut.
- **FR-004**: La destination d'une carte MUST provenir de la définition de la route, jamais d'un
  chemin recomposé à la main.
- **FR-005**: Le repli d'une recette sans vignette MUST être l'icône Cookbook du site, sur les deux
  chemins.
- **FR-006**: Le rendu produit par les deux chemins MUST être identique à données égales — structure,
  classes et contenu.
- **FR-007**: Le contrat de dégradation acquis MUST être préservé : service indisponible → message
  d'indisponibilité, jamais un état « aucun résultat ».
- **FR-008**: Les cartes MUST rester accessibles au clavier et annoncées correctement : nom
  accessible du lien, image de repli non annoncée comme information, contraste conservé dans les deux
  thèmes.
- **FR-009**: L'ajout de recettes par défilement MUST être annoncé aux technologies d'assistance sans
  voler le focus.
- **FR-010**: Le message « aucun résultat » et les autres états de la liste MUST suivre la même règle
  que la carte : définis côté serveur, jamais composés en balisage par le navigateur.

### Key Entities

- **Recette (vue liste)** : identifiant, titre, vignette optionnelle, catégorie optionnelle, durée
  optionnelle. Les trois derniers champs sont facultatifs et doivent l'être partout.
- **Page de résultats** : la tranche de recettes servie pour une page donnée, plus l'information
  « existe-t-il une page suivante ».

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Une modification d'apparence de la carte se fait en **un seul endroit** et se voit sur
  les deux chemins de rendu, vérifié par une retouche témoin.
- **SC-002**: Sur un jeu de recettes piégées (apostrophe, chevrons, guillemets, chaîne ressemblant à
  du balisage), **100 %** s'affichent littéralement et **aucune** n'altère la page.
- **SC-003**: Le balisage des cartes du premier écran et celui des cartes chargées par défilement
  sont identiques à données égales — **zéro** différence hors valeurs.
- **SC-004**: Le nombre de définitions de la carte dans le dépôt passe de **2 à 1**.
- **SC-005**: axe-core relève **0 violation** sur la liste des recettes, dans les deux thèmes, avant
  et après un chargement par défilement.
- **SC-006**: Une page supplémentaire s'affiche en **moins d'une seconde** sur une connexion normale,
  sans régression perceptible par rapport à aujourd'hui.
- **SC-007**: Les parcours acquis restent verts : liste, fiche recette, recherche, filtre, tri,
  service indisponible — **aucun** ne régresse.

## Assumptions

- **Le point d'accès du défilement n'est consommé que par cette page.** Rien d'autre, dans le dépôt
  ou hors de lui, ne dépend de la forme de sa réponse : elle peut donc changer.
- **L'API Cookbook reste la source des données** et son contrat ne change pas ; ce lot ne touche que
  la façon dont Radiant les affiche.
- **Le kit shadcn est le point de départ** de tout composant nouveau : il est éprouvé sur le panneau
  « Behind the scenes », et le projet n'ajoute plus de partiels maison quand le kit couvre le besoin.
- **Turbo reste retiré.** Il a été supprimé délibérément au lot Étape 4 ; le réintroduire serait une
  décision à prendre pour elle-même, pas un effet de bord de ce lot.
- **Aucune migration de données** n'est attendue : le lot est un lot de rendu.
- **La vérification front reste manuelle** : ni linter JS/CSS, ni test visuel automatisé dans la CI.
  Le lot dit donc explicitement quels écrans sont chargés à la main et à quelles largeurs.

## Out of Scope

- Refonte de la recherche, du filtre par catégorie ou du tri — leur comportement est reconduit tel quel.
- Pagination classique en remplacement du défilement infini.
- Mise en cache des pages de résultats.
- Réintroduction de Turbo ou de tout autre mécanisme de navigation partielle.
- Traitement des autres constats ouverts de l'audit (**S5** anti-spam, **F6** métadonnées de partage,
  **N3** emballage des colonnes JSON).
