# Implementation Plan: Tailwind 4 et le kit shadcn

**Branch**: `001-tailwind4-shadcn` | **Date**: 2026-08-19 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/001-tailwind4-shadcn/spec.md`

## Summary

Basculer le moteur CSS de Tailwind 3.4 vers Tailwind 4, exprimer l'identité du site en tokens avec un
thème clair et un thème sombre suivant la préférence système, et reprendre le panneau
« Behind the scenes » sur le composant `Dialog` du kit shadcn.

La recherche a resserré le risque là où il est réellement, et l'a déplacé par rapport à ce que la
spéc supposait :

- **Ce qui inquiétait ne pose pas problème.** Le bloc Motus est du CSS écrit à la main, pas des
  utilitaires : Tailwind 4 n'élague pas le CSS de l'auteur, la contrainte disparaît. Déclarer
  `assets/` comme source couvre les utilitaires réellement posés par JavaScript.
- **Les ruptures de Tailwind 4 sont étroites** : 24 occurrences en tout, dont 18 bordures sans
  couleur explicite qui changeront de teinte sans qu'aucun diff ne le montre.
- **Le vrai point dur est le thème clair.** L'accent ambre du site donne **1,97:1 sur blanc** — il
  est inutilisable en thème clair, y compris en grand texte, et la variante la plus sombre de la
  palette actuelle plafonne à 4,13:1. Ce lot **crée** donc une teinte d'accent qui n'existe nulle
  part.

Découpage en trois tranches livrables séparément : moteur (P1), tokens et thèmes (P2), composant
(P3). P1 est livrable seule.

## Technical Context

**Language/Version**: PHP 8.2 (plafond de la CI ; la production fournit 8.4), Twig 3, JavaScript ES2020

**Primary Dependencies**: Tailwind CSS 4 via `@tailwindcss/postcss` · `tw-animate-css` ·
`symfony/ux-toolkit:^2.36` (dev) · `symfony/ux-twig-component` (à déclarer, aujourd'hui transitif) ·
`twig/html-extra` · `tales-from-a-dev/twig-tailwind-extra` · `symfony/ux-icons` · Webpack Encore

**Storage**: PostgreSQL 16 — **non touché**, aucune migration (NFR-002)

**Testing**: PHPUnit (37 tests). Aucun test ne couvre le rendu : la validation est manuelle et
décrite dans [quickstart.md](quickstart.md)

**Target Platform**: navigateurs de bureau et mobiles ; serveur mutualisé OVH

**Project Type**: application web Symfony rendue côté serveur

**Performance Goals**: aucune cible chiffrée hors NFR-001 (poids de la feuille ≤ 120 % de l'existant)

**Constraints**: aucun contrôle automatisé du rendu · les assets sont construits par la CI, pas
localement · le déploiement part tout seul au merge sur `main`

**Scale/Scope**: **8 pages publiques** (plus `/admin`, authentifié, vérifié à part) · 23 gabarits ·
420 classes distinctes · 5 contrôleurs Stimulus · **48 écrans à vérifier**
(8 pages × 3 largeurs × 2 thèmes)

## Constitution Check

*GATE — évalué avant la Phase 0, réévalué après la Phase 1.*

| Non-négociable | Applicable ? | Verdict |
|---|---|---|
| 1. Typage strict, PHP 8.2 | Marginal — aucun PHP nouveau attendu hors déclaration de dépendance | ✅ |
| 2. Sécurité par Symfony | Non concerné : aucune route, aucun contrôle d'accès touché | ✅ |
| 3. Couches | Non concerné : aucune requête, aucune logique métier déplacée | ✅ |
| 4. Identifiants anglais, interface française | Applicable : les composants du kit portent des noms anglais, les textes restent français | ✅ |
| 5. Migrations non interactives | **Aucune migration** (NFR-002) — un fichier de migration dans ce lot serait une erreur | ✅ |
| 6. Conventional Commits | Applicable | ✅ |
| 7. Accessibilité, critère d'acceptation | **Cœur du lot.** FR-006, C2, SC-002/003/004/007 | ✅ |
| 8. Le gate passe | Applicable — mais il ne voit **rien** de ce lot | ⚠️ voir ci-dessous |

| Contrainte de planification | Verdict |
|---|---|
| Tailwind 4 et le kit livrés ensemble | ✅ c'est l'objet même du lot |
| Suite de tests mince, pas absente | ✅ le plan ne prétend pas que les 37 tests couvrent le rendu |
| Régressions front invisibles à la CI | ✅ [quickstart.md](quickstart.md) dit écran par écran comment chacune est vérifiée à la main |

**⚠️ Le point n°8 mérite d'être dit franchement plutôt que coché.** `make ci` restera vert quoi que
ce lot casse : ni php-cs-fixer, ni twig-cs-fixer, ni PHPStan, ni les 37 tests ne regardent une
couleur, un contraste ou une bordure. **Une CI verte n'est pas une validation ici.** Le seul
instrument est le relevé de référence de l'étape 0 du quickstart — et il n'existe que s'il est pris
**avant** de toucher au moteur. C'est la raison d'être de la première tâche.

**Aucune violation à justifier** : la section *Complexity Tracking* reste vide.

## Project Structure

### Documentation (this feature)

```text
specs/001-tailwind4-shadcn/
├── plan.md              # ce fichier
├── spec.md              # exigences et critères de succès
├── research.md          # Phase 0 — mesures sur le code réel
├── data-model.md        # Phase 1 — aucun changement de schéma, formes JSON à préserver
├── quickstart.md        # Phase 1 — le protocole de validation manuelle
├── contracts/
│   └── ui-contracts.md  # Phase 1 — les 5 contrats d'interface
├── checklists/
│   └── requirements.md  # contrôle qualité de la spéc
└── tasks.md             # Phase 2 — produit par /speckit-tasks
```

### Source Code (repository root)

```text
assets/
├── styles/
│   └── app.css                    # ⭐ cœur du lot : @import, @source, @theme, tokens clair/sombre,
│                                  #    et le bloc Motus (CSS écrit à la main, lignes 11-347)
├── controllers/
│   ├── motus_controller.js        # pose des classes motus-* à l'exécution
│   ├── cookbook_controller.js     # construit la carte recette en utilitaires
│   └── app_detail_drawer_controller.js  # ⭐ remplacé ou réduit en US3
└── bootstrap.js                   # enregistrement manuel des contrôleurs

templates/
├── components/
│   ├── _app_detail_drawer.html.twig   # ⭐ devient <twig:Dialog> en US3
│   └── _icon_<slug>.html.twig         # 🔒 contrat C1 — ne pas déplacer
├── portfolio/**, app/**, contact/, legal/, security/   # passe de renommage (24 occurrences)
└── base.html.twig

config/packages/
└── twig_component.yaml            # existe déjà ; ajouter le mapping `defaults`

postcss.config.js                  # autoprefixer retiré, @tailwindcss/postcss seul
tailwind.config.js                 # supprimé, remplacé par la config CSS
package.json · composer.json       # dépendances du kit
```

**Structure Decision** : projet Symfony unique, rendu côté serveur. Pas de séparation
frontend/backend : les gabarits Twig **sont** le frontend, et les composants du kit sont copiés dans
`templates/components/` — ils deviennent du code du projet, pas une dépendance vendorisée.

## Ordre d'exécution et points de non-retour

| Tranche | Contenu | Livrable seule ? | Point de non-retour |
|---|---|---|---|
| **0** | Relevé de référence : 24 captures + poids de la feuille | — | **Aucun**, mais rien ne peut être validé sans lui |
| **1 (US1)** | Renommages (24 occurrences), bascule du moteur, `@source`, palette portée à l'identique | ✅ oui | La suppression de `tailwind.config.js` |
| **2 (US2)** | Tokens, thème clair, contraste de l'accent | ✅ oui | Le choix de la teinte d'accent claire — décision esthétique consignée |
| **3 (US3)** | Dépendances du kit déclarées, mapping `defaults`, panneau repris sur `Dialog` | ✅ oui | Suppression de l'ancien partiel, une fois zéro usage |

**Règle** : chaque tranche est vérifiée selon [quickstart.md](quickstart.md) avant d'entamer la
suivante. Le déploiement partant tout seul au merge, une tranche à moitié vérifiée est une tranche en
production.

## Complexity Tracking

*Aucune violation du contrôle de constitution — section vide, comme prévu.*
