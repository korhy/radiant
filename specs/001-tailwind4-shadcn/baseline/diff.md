# T021 — Comparaison avant/après la bascule Tailwind 4

Relevé le **2026-08-19**, 24 écrans recapturés au même cadrage par `capture.js`.

## Verdict

**Aucune régression visuelle.** Un seul écart réel, listé ci-dessous au titre de SC-001
(« hors changements délibérés, listés nommément »).

## L'écart réel : le retour à la ligne d'un paragraphe

Sur `/`, le texte des expériences se coupe à un mot près différemment, ce qui décale les pastilles
de tags de quelques pixels. Effet mesurable : **l'accueil en 1280 px gagne 8 pixels de hauteur**
(2950 → 2958). **Les 23 autres écrans sont à hauteur strictement identique.**

C'est une conséquence de la typographie par défaut de Tailwind 4, pas d'une erreur de migration.
`text-sm` rend toujours 14px/20px : le décalage vient des métriques, pas d'un interligne modifié.
Non rattrapable sans figer chaque valeur, et sans effet sur la lisibilité.

## Ce qui n'est **pas** un écart

| Observation initiale | Explication |
|---|---|
| « 0/24 écrans identiques », 20 à 75 % de pixels différents | La **barre de débogage Symfony** est incluse dans les captures : ses chronos changent à chaque requête. Elle se masque sous 768 px — d'où des écrans 375 px propres et des 768/1280 bruyants |
| Écart maximal de 255 sur le Taquin | Le **mélange aléatoire** des tuiles à chaque chargement |
| Palette légèrement décalée partout | Tailwind 4 redéfinit sa palette en **OKLCH**, avec des valeurs qui ne sont pas au bit près celles de la v3. Écart maximal mesuré sur les écrans sans barre de débogage : **3 à 5 sur 255** — sub-perceptuel |

## Défaut de méthode à corriger pour la prochaine comparaison

Le relevé de référence a été pris **en environnement de développement**, barre de débogage comprise,
et sur des pages au contenu non déterministe. Les trois corrections à apporter à `capture.js` :

1. retirer la barre de débogage avant capture, ou capturer en environnement de production ;
2. neutraliser l'aléa (mélange du Taquin, mot du jour de Motus) ;
3. comparer avec une tolérance d'un pixel en vertical.

Sans quoi la comparaison produit un bruit qui masque le signal — ce qui a bien failli faire conclure
à une régression généralisée inexistante.

## Vérifications d'interaction (T016-T020)

Aucune capture statique ne les couvre.

| Contrôle | Résultat |
|---|---|
| **T016** Motus, trois états | ✅ `correct` rouge + liseré blanc, `present` jaune + soulignement sombre, `absent` ardoise. Redondance non chromatique préservée (SC-002) |
| **T017** Cookbook, carte | ✅ rayon 16 px, fond appliqué |
| **T018** Taquin, focus clavier | ✅ contour visible (`auto 1px`) — le renommage `focus:outline-hidden` n'a rien supprimé |
| **T019** Contact, bordure de champ | ✅ `solid 1px` (WCAG 1.4.11) |
| **T020** `/admin` | ✅ redirige vers `/login` — contrat C5 |
