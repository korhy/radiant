---
description: Component standard — every UI component starts from the Symfony UX Toolkit shadcn kit. Modals use Dialog. Reuse over duplication. Legacy migration strategy.
paths:
  - "**/*.twig"
  - "**/src/Twig/Components/**/*.php"
  - "**/assets/controllers/**/*.js"
---

# Composants — le kit shadcn

## La règle
**Tout composant visuel part du kit** ([Symfony UX Toolkit](https://ux.symfony.com/toolkit/kits/shadcn)).
Ne pas réécrire à la main, en Tailwind brut, ce que le kit livre déjà.

```bash
php bin/console ux:install <recipe> --kit=shadcn
```

Les fichiers sont **copiés** dans `templates/components/<Nom>.html.twig` (plus
`templates/components/<Nom>/…` pour un composant composite) et dans `assets/controllers/`. Ils
deviennent **du code du projet** : les relire à l'installation, les éditer librement. Ce n'est pas
une dépendance figée.

- **Rendre avec la syntaxe namespacée** : `<twig:Dialog>`, `<twig:Button>`, `<twig:Card>`. Les
  classes passées par l'appelant sont **fusionnées** par `tailwind_merge`, pas concaténées : elles
  écrasent bien celles du composant.
- **Enregistrer à la main tout contrôleur Stimulus copié** dans `assets/bootstrap.js`, sans quoi il
  ne tourne jamais — voir [ux-stimulus-turbo.md](ux-stimulus-turbo.md).
- **`data-controller` est fusionné, pas écrasé.** Pour ajouter un contrôleur à un composant, ne
  passer que le sien : celui du kit s'ajoute tout seul.
- **Les modales passent par `Dialog`.** Ne jamais réécrire une superposition à la main : `Dialog`
  s'appuie sur l'élément natif `<dialog>`, d'où viennent le piège au focus, Échap, l'inertie de
  l'arrière-plan et le retour du focus. N'écrire que ce que le natif ne couvre pas.
- **Ne pas dépouiller l'accessibilité du kit.** Les composants arrivent avec leurs `aria-*`, leur
  gestion du focus et leur câblage clavier. Un écran repris qui régresse en RGAA/EAA (WCAG 2.1 AA)
  n'est pas livrable — liste de contrôle dans [frontend-twig.md](frontend-twig.md).
- **Le texte visible reste français ; les noms de composants et de props restent anglais** — voir
  [naming.md](naming.md).

## Les couleurs viennent des tokens du site

Les composants du kit emploient le vocabulaire shadcn : `bg-background`, `text-foreground`,
`text-muted-foreground`, `border`, `ring-ring`, `bg-primary`… Ces noms sont **adossés aux rôles du
site** dans le bloc `@theme inline` d'`assets/styles/app.css`. Un composant installé prend donc
l'identité du portfolio sans être retouché.

- **Ne pas modifier les couleurs d'un composant copié** pour l'adapter au site : si sa teinte détonne,
  c'est un token qui manque au mappage, pas le composant qu'il faut peindre.
- **`accent` a chez shadcn le sens d'une surface de survol discrète.** L'ambre du portfolio, c'est
  `brand-*`. Un composant qui référence `bg-accent` doit rester discret.
- Détail du dispositif : [frontend-styling.md](frontend-styling.md).

## Un composant adossé à une classe

`config/packages/twig_component.yaml` déclare `anonymous_template_directory` **et** le mappage
`App\Twig\Components\`. Un composant qui a besoin de logique se pose donc en classe sous
`src/Twig/Components/`, sans configuration supplémentaire. Y mettre de la présentation, jamais une
requête Doctrine — la donnée arrive du contrôleur.

## Quand le kit n'a pas le bon composant

Le kit d'abord — mais **prendre un composant pour ce qu'il fait, pas pour son nom**. Une aide
contextuelle n'est pas une infobulle : le `Tooltip` du kit se révèle au survol, se dérobe au pointeur
qui l'approche et n'offre aucune fermeture au clavier — il échoue WCAG 1.4.13 dès que le contenu se
lit. Le ⓘ des mini-apps passe donc par `InfoDisclosure`, adossé au `<details>` natif.

Quand rien dans le kit ne couvre le besoin, écrire un **composant du projet aux conventions du
kit** : `templates/components/<Nom>.html.twig`, sous-composants dans `templates/components/<Nom>/`,
props documentées en tête, `attributes` fusionnés par `tailwind_merge`, un `data-slot` par partie.

- **`SectionHeader`** — l'en-tête d'une section du portfolio : barre collante et libellé de bureau.
- **`InfoDisclosure`** (+ `:Trigger`, `:Panel`) — le ⓘ qui révèle une aide.

**Un composant du projet ne porte que des classes structurelles ; l'appelant l'habille.** C'est ce
qui laisse le même `InfoDisclosure` être un ⓘ en ligne sur le bureau et un bouton de barre de 44 px
sur mobile.

Réserver les partiels préfixés d'un tiret bas (`templates/components/_*.html.twig`) à ce qui ne prend
**aucune variation** — l'icône d'une tuile, une légende. Les inclure explicitement, avec leurs
variables :

```twig
{{ include('components/_icon_motus.html.twig') }}
```

Restent en utilitaires bruts dans le gabarit, faute d'être repris : les champs de formulaire.

## Reprendre un composant existant — le « Legacy move »

Ne **jamais** écraser d'un coup un composant en service : l'API diffère et les gabarits cassent en
silence. Quand le nom d'un composant du kit entre en collision avec un existant :

1. **Déplacer l'ancien** pour libérer le nom canonique —
   `templates/components/X.html.twig` → `templates/components/Legacy/X.html.twig`
   (rendu `<twig:Legacy:X>`) ; l'équivalent adossé à une classe va dans `src/Twig/Components/Legacy/`.
2. **Repointer chaque usage** vers `Legacy:X`, puis `php bin/console lint:twig templates` — rien ne
   doit casser.
3. **Installer le composant du kit** sous le nom canonique.
4. **Migrer écran par écran**, en adaptant l'API, et **vérifier chaque écran** au navigateur, clavier
   seul compris, plus une passe automatisée (axe / Lighthouse) dans les deux thèmes. Une reprise doit
   *améliorer* l'accessibilité, jamais la dégrader.
5. **Supprimer le composant `Legacy/`** une fois son nombre d'usages tombé à zéro.

Un partiel inclus par `include()` n'entre pas en collision : il se reprend en remplaçant l'appel.

## Voir aussi
- Gabarits, partiels et accessibilité : [frontend-twig.md](frontend-twig.md)
- Tokens et utilitaires : [frontend-styling.md](frontend-styling.md)
- Interactivité : [ux-stimulus-turbo.md](ux-stimulus-turbo.md)
