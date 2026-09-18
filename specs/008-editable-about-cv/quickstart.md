# Quickstart — vérifier « À propos » et CV éditables

**Feature**: `008-editable-about-cv` · **Date**: 2026-09-18

Comment prouver que la fonctionnalité marche. Six scénarios, dans l'ordre où ils se cassent le plus
souvent. Détails de la grammaire dans [`contracts/highlight-grammar.md`](contracts/highlight-grammar.md),
du lien CV dans [`contracts/cv-download-link.md`](contracts/cv-download-link.md), des champs dans
[`data-model.md`](data-model.md).

---

## Prérequis

```bash
make up                 # app, Postgres, Mailpit — la stack doit tourner
make db-migrate         # applique la migration, qui amorce la ligne unique
```

L'URL de dev dépend du mode de lancement : `docker compose ps` dit le port réellement publié
(8080 avec la stack Docker, 8000 avec `symfony serve`). Les commandes ci-dessous écrivent `:8080`.

L'administration est sur `/admin`, entrée **Site content** dans le menu.

---

## 1. Le contenu initial est déjà là — sans rien saisir

C'est FR-019, et c'est le premier à vérifier parce qu'il conditionne tous les autres.

```bash
open http://localhost:8080/
```

**Attendu** : la section « À propos » affiche le paragraphe actuel, avec ses **cinq** expressions en
couleur de marque — « back-end », « e-commerce », « certification Data Science & IA », « Teacher
Assistant en Data Science et Développement Web », « intelligents ». Le pied de page affiche les
trois liens, CV compris.

**Le vrai test** : comparer avec la page d'avant le changement. La différence doit être **nulle**
(SC-003). Faire la comparaison dans les deux thèmes.

---

## 2. Réécrire le texte, et le voir (FR-001, FR-002, FR-003)

1. `/admin` → **Site content** → éditer la ligne.
2. Remplacer le texte par : `Développeur **PHP** et **Symfony**, orienté données.`
3. Enregistrer, recharger la page d'accueil.

**Attendu** : le nouveau paragraphe s'affiche, `PHP` et `Symfony` en couleur de marque, le reste en
texte courant. Aucun redéploiement, aucun vidage de cache.

**Si rien ne change** : c'est le cache Twig, pas une régression. `make cc`.

---

## 3. Le contenu saisi ne peut pas altérer la page (FR-006, SC-005)

Saisir successivement, en vérifiant la page après chaque enregistrement :

| Saisie | Attendu à l'écran |
|---|---|
| `<script>alert(1)</script>` | le texte s'affiche **littéralement**, aucune boîte de dialogue |
| `<b>gras</b>` | s'affiche littéralement, sans gras |
| `Marqueur **jamais refermé` | s'affiche littéralement, marqueur compris |
| `**a **b** c**` | rendu conforme au cas 8 du contrat de grammaire |

**À vérifier aussi** : la console du navigateur reste vide, et le code source de la page ne contient
ni `<script>` ni `<b>` issus de la saisie.

---

## 4. Vider le texte n'abîme pas la page (FR-009)

1. Effacer entièrement le champ, enregistrer.
2. Recharger la page d'accueil.

**Attendu** : aucune section « À propos », **et** aucune entrée « À propos » dans la navigation
latérale. Pas de titre orphelin, pas de bloc vide, pas d'ancre morte.

**Au clavier** : tabuler dans la navigation ne doit jamais atteindre une entrée « À propos ».

Remettre le texte ensuite.

---

## 5. Publier un nouveau CV (FR-010 à FR-013, FR-016)

1. `/admin` → **Site content** → déposer un PDF, enregistrer.
2. Pied de page → activer le lien CV.

**Attendu** : le fichier téléchargé est celui qui vient d'être déposé, et il arrive **sous son nom
d'origine**, pas sous le nom de stockage. L'icône, la position et l'intitulé du lien sont inchangés.

Puis les refus (FR-011) — à chaque fois, le CV en ligne doit rester servi :

| Dépôt | Attendu |
|---|---|
| une image `.png` | refus, message explicite |
| un `.pdf` de plus de 5 Mo | refus, message explicite |
| un fichier vide | refus |

Enfin, le cas vide (FR-013) : vider le champ fichier et vérifier qu'**aucun** lien CV n'apparaît
dans le pied de page — et qu'il n'est pas non plus atteignable au clavier.

---

## 6. Les liens GitHub et LinkedIn n'ont pas bougé (FR-020)

Vérification de non-régression, à faire en dernier : les deux liens pointent toujours vers les mêmes
destinations, dans le même ordre, avec les mêmes icônes. Ils ne sont pas éditables, et c'est
volontaire.

---

## La passe automatisée

```bash
rm -f var/test.db        # sinon d'anciennes tables masquent une migration manquante
make phpunit             # unitaires + fonctionnels
make lint                # php-cs-fixer, twig-cs-fixer, PHPStan niveau 5
make e2e                 # Playwright + axe, les sept pages publiques, deux thèmes
```

`make ci` enchaîne exactement ce que fait la CI. Aucun des trois linters n'est optionnel : un
changement n'est pas terminé tant qu'ils ne passent pas.

**Ce que la suite ne prouve pas**, et qu'il faut donc regarder à la main :

- le rendu sur **PostgreSQL** — la suite tourne sur SQLite, et la migration insère du texte accentué
  avec des apostrophes typographiques ;
- l'apparence dans les deux thèmes — aucun test visuel n'existe ;
- le comportement réel du dépôt de fichier — les tests fonctionnels ne déposent pas de vrai PDF.

---

## Avant de déclarer terminé

- [ ] Les scénarios 1 à 6 passent, dans les deux thèmes
- [ ] `make ci` est vert, après `rm -f var/test.db`
- [ ] La migration a été exécutée **sur PostgreSQL**, pas seulement sur SQLite
- [ ] Le `.gitignore` couvre bien les nouveaux dépôts, et `git status` reste propre après un dépôt
- [ ] La release N+1 — retrait du PDF hérité du dépôt — est notée quelque part, sinon FR-015 reste
      en suspens indéfiniment
