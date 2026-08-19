# Relevé de référence — avant la migration Tailwind 4

Pris le **2026-08-19**, sur `main` en Tailwind 3.4, avant toute modification du moteur CSS.

## Ce que contient ce dossier

| Fichier | Rôle |
|---|---|
| `*.png` | 24 captures : 8 pages publiques × 375/768/1280 px, en pleine hauteur |
| `weight.txt` | poids de la feuille livrée — référence de NFR-001 et SC-006 |
| `renames.md` | les 24 occurrences à renommer, fichier et ligne (T005) |
| `capture.js` | le script qui a produit les captures, pour rejouer le **même** cadrage après |

## Pourquoi ces images sont versionnées

Elles sont **irremplaçables** : rejouer `capture.js` après la bascule donne l'état *nouveau*, pas
l'état de référence. Les perdre rendrait **SC-001 invérifiable**, et rien dans l'intégration continue
ne détecte une régression visuelle.

Elles sont temporaires pour autant : supprimables une fois T021 et T031 validés.

## Rejouer les captures après la bascule

```bash
NODE_PATH=.claude/skills/playwright-skill/node_modules \
  node specs/001-tailwind4-shadcn/baseline/capture.js /chemin/vers/apres
```

Le script suppose la pile locale démarrée (`make up`), l'API Cookbook joignable **depuis le
conteneur** (`host.docker.internal`, pas `127.0.0.1`), et les colonnes JSON des trois mini-apps
remplies — sans quoi les panneaux « Behind the scenes » se comparent à vide.

## Ce que le relevé ne couvre pas

- **`/admin`** : exige une authentification, renvoie 302 anonymement. À capturer connecté, à part.
- **Le thème clair** : il n'existe pas encore. Il se contrôlera au contraste, pas à la comparaison.
- **Tout ce qui se déclenche à l'interaction** : états de Motus, chargement des cartes Cookbook,
  ouverture du panneau. Une capture statique ne les montre pas — d'où les tâches T016 à T019.
