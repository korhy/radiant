# Phase 1 — Contrats d'interface

**Feature**: Tailwind 4 et le kit shadcn · **Date**: 2026-08-19

Ce projet n'expose ni API publique ni ligne de commande. Ses contrats sont **des contrats
d'interface utilisateur** : des comportements sur lesquels d'autres parties du code s'appuient, et
qui cassent silencieusement s'ils changent.

---

## C1 — Résolution dynamique du gabarit d'icône

```twig
{{ include('components/_icon_' ~ appEntry.slug ~ '.html.twig') }}
```

**Invariant** : pour toute ligne `App`, le fichier `templates/components/_icon_<slug>.html.twig`
existe.

**Ce que ce lot doit garantir** : aucune réorganisation des partiels ne déplace, ne renomme ni
n'englobe ces fichiers. Si le kit amène une convention de nommage différente, **elle ne s'applique
pas à cette famille**.

**Mode de défaillance** : la **page d'accueil** lève une exception — pas la page de la mini-app.
Un contrôle qui ne visiterait que les pages de mini-apps ne verrait rien.

**Vérification** : la page d'accueil rend une icône par tuile (déjà couvert par
`PublicRoutesTest::testHomepageRendersTheStreamDeckTileAndItsIconPartial`).

---

## C2 — Contrat clavier du panneau « Behind the scenes »

C'est le contrat le plus dense du projet, et le seul que la reprise sur le composant du kit risque de
dégrader. Il est acquis depuis l'Étape 2 de l'audit et **ne peut que s'améliorer**.

| Comportement | Attendu | État actuel |
|---|---|---|
| Rôle | `role="dialog"` + `aria-modal="true"` | posé à la main |
| Fermé | l'attribut `inert` retire le panneau de l'ordre de tabulation **et** de l'arbre d'accessibilité | posé à la main |
| Ouverture | le focus entre dans le panneau | contrôleur Stimulus |
| Ouvert | le focus ne sort pas du panneau | contrôleur Stimulus |
| Échap | ferme le panneau | contrôleur Stimulus |
| Fermeture | le focus revient sur le bouton déclencheur | contrôleur Stimulus |
| Onglets | `role="tablist"`/`tab`/`tabpanel`, navigation aux flèches, `aria-selected` reflétant l'état | contrôleur Stimulus |
| Nom accessible | « Ouvrir les coulisses de cette application » sur le déclencheur, « Fermer les coulisses » sur la fermeture | `aria-label` |

**Règle de reprise** : chacune de ces lignes est vérifiée **au clavier seul** après bascule, sur les
trois mini-apps. Une ligne qui régresse annule la reprise.

---

## C3 — Contrat de tokens

**Invariant à établir** : aucune couleur littérale n'apparaît dans un gabarit. Toute couleur passe
par un token.

**Ce qui rend le contrat vérifiable** : changer la valeur du token d'accent en un seul endroit
modifie l'accent partout (SC-005). Si un écran ne suit pas, c'est qu'une couleur y est écrite en dur.

**Deux jeux de valeurs** : sombre par défaut, clair sous `prefers-color-scheme: light`. Les deux
satisfont AA — ce qui, pour l'accent, impose **deux teintes différentes** et non la même (voir
[research.md](../research.md) R5).

---

## C4 — Contrat de sources de classes

**Invariant** : toute classe utilitaire employée par le projet est visible depuis une source
déclarée.

Les sources déclarées doivent couvrir `templates/` **et** `assets/`, ce dernier parce que trois
contrôleurs Stimulus posent des utilitaires à l'exécution (voir [research.md](../research.md) R1).

**Mode de défaillance** : la classe n'est pas générée, l'élément s'affiche sans style, **aucune
erreur n'est levée**. Ne se voit qu'en exerçant le comportement — une partie de Motus jouée, une
carte recette chargée, un panneau ouvert.

---

## C5 — Contrat de séparation avec le back-office

**Invariant** : l'outil d'administration sert ses propres feuilles de style, indépendamment de
`app.css`. Ni fusion, ni contamination dans un sens ou dans l'autre.

**Vérification** : après bascule, `/admin` reste visuellement inchangé.
