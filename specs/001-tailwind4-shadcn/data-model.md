# Phase 1 — Modèle de données

**Feature**: Tailwind 4 et le kit shadcn · **Date**: 2026-08-19

## Aucun changement de schéma

Ce lot ne touche ni entité, ni colonne, ni migration (NFR-002). Il est consigné ici pour que le point
soit vérifiable plutôt que supposé : **si une migration apparaît dans ce lot, c'est une erreur** — les
migrations s'exécutent seules en production à chaque livraison.

## Données lues, dont la forme doit être préservée

Le panneau « Behind the scenes », qui change d'implémentation en US3, lit **quatre colonnes JSON** de
l'entité `App`. Leur forme n'est pas documentée par un schéma : elle est induite par le gabarit. La
reprise sur le composant du kit doit donc préserver exactement ces formes.

| Colonne | Forme attendue par le gabarit actuel | Onglet |
|---|---|---|
| `techStack` | objet `{ catégorie: [libellés] }` — la clé est affichée comme intertitre | Présentation |
| `challenges` | liste d'objets `{ title, description }` | Défis |
| `improvements` | liste d'objets `{ description }` (le `title` est présent mais commenté dans le gabarit) | À venir |
| `resources` | liste d'objets `{ label, url }` | Ressources |

**Chaque onglet gère déjà le cas vide** en affichant « Aucune information pour l'instant. » — ce
comportement fait partie du contrat et doit survivre à la reprise.

> **Piège relevé** : `improvements` rend `{# item.title #}` dans un commentaire Twig. Le titre est
> donc dans les données mais invisible à l'écran. Le reproduire tel quel, ou le corriger, est une
> décision à prendre **explicitement** au moment de la reprise — pas un détail à trancher au clavier.

## Donnée qui pilote un chemin de fichier

`App.slug` est interpolé dans un nom de gabarit (`components/_icon_<slug>.html.twig`). Voir le
contrat correspondant dans [contracts/](contracts/ui-contracts.md) : c'est le seul endroit du projet
où une valeur de base compose un chemin de gabarit, et il casse **la page d'accueil**, pas la page
concernée.
