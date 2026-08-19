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
| `techStack` | **liste plate** d'objets `{ category, name }`. Le gabarit regroupe lui-même par `category` (`_app_detail_drawer.html.twig:97-101`) et retombe sur « Autre » quand la clé manque | Présentation |
| `challenges` | liste d'objets `{ title, description }` | Défis |
| `improvements` | liste d'objets `{ description }` (le `title` est présent mais commenté dans le gabarit) | À venir |
| `resources` | liste d'objets `{ label, url }` | Ressources |

**Chaque onglet gère déjà le cas vide** en affichant « Aucune information pour l'instant. » — ce
comportement fait partie du contrat et doit survivre à la reprise.

> **Piège relevé** : `improvements` rend `{# item.title #}` dans un commentaire Twig. Le titre est
> donc dans les données mais invisible à l'écran. Le reproduire tel quel, ou le corriger, est une
> décision à prendre **explicitement** au moment de la reprise — pas un détail à trancher au clavier.

> **⚠️ Ces formes se lisent dans le gabarit, jamais dans le HTML rendu.** Le 2026-08-19, avoir déduit
> la forme de `techStack` depuis la page produite a fait écrire `{ catégorie: [libellés] }` — la
> forme *après* regroupement par le gabarit, pas celle stockée. Injecter cette forme en base a
> renvoyé `/app/motus` et `/app/cookbook` en 500 (« Key "name" ... does not exist »). Les colonnes
> JSON n'ont aucun schéma qui les contraigne : le gabarit **est** le schéma.

## Donnée qui pilote un chemin de fichier

`App.slug` est interpolé dans un nom de gabarit (`components/_icon_<slug>.html.twig`). Voir le
contrat correspondant dans [contracts/](contracts/ui-contracts.md) : c'est le seul endroit du projet
où une valeur de base compose un chemin de gabarit, et il casse **la page d'accueil**, pas la page
concernée.
