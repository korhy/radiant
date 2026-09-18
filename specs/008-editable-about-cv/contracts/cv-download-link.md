# Contrat — lien de téléchargement du CV

**Feature**: `008-editable-about-cv` · Couvre FR-009 à FR-016

Ce document fixe ce que le pied de page rend, et ce qu'il ne rend pas. Le lien existe déjà : c'est
le troisième `<li>` de `templates/portfolio/footer/network.html.twig`. Son apparence ne change pas —
seule sa **source** change.

---

## État observable

| État de `SiteContent` | Ce que rend le pied de page |
|---|---|
| `cvFileName` renseigné | le `<li>` du CV, avec son icône et son intitulé actuels |
| `cvFileName` nul | **rien** — pas de `<li>`, pas d'élément focalisable, pas d'espace réservé |
| aucune ligne `SiteContent` | identique au cas précédent : rien |

Les liens GitHub et LinkedIn sont **inchangés** dans les trois cas (FR-020). Ils restent écrits dans
le gabarit ; cette fonctionnalité ne les touche pas.

---

## Forme du lien

```twig
{% if cv_url %}
    <li class="mr-5 text-xs">
        <a class="block hover:text-content" href="{{ cv_url }}"
           target="_blank" rel="noreferrer" download="{{ cv_download_name }}">
            <span class="sr-only">Télécharger mon CV</span>
            {# icône inchangée #}
        </a>
    </li>
{% endif %}
```

| Élément | Source | Exigence |
|---|---|---|
| `href` | `vich_uploader_asset(site_content, 'cvFile')` | FR-012 — sert toujours le fichier enregistré |
| `download` | `cvOriginalName` | FR-016 / R5 — le visiteur reçoit un nom lisible, pas le nom de stockage |
| `<span class="sr-only">` | littéral, inchangé | FR-016 — l'intitulé accessible reste « Télécharger mon CV » |
| icône | littérale, inchangée | FR-016 — le SVG existant n'est pas touché |
| position | troisième `<li>`, inchangée | FR-016 |

**L'URL est publique et non devinable.** Le nom de stockage est produit par `SmartUniqueNamer`, donc
de la forme `cv-68f3a1c2-1.pdf`. Ce n'est pas une mesure de sécurité — un CV est fait pour être lu —
mais cela évite qu'une ancienne URL reste devinable après remplacement.

---

## Cycle de vie du fichier

| Événement | Effet attendu |
|---|---|
| Premier dépôt | le fichier est écrit sous son nom de stockage ; `cvFileName`, `cvOriginalName` et `updatedAt` sont renseignés |
| Dépôt de remplacement | le nouveau fichier est écrit, **l'ancien est supprimé** par Vich (`delete_on_update`, comportement par défaut) — FR-015 |
| Dépôt refusé (format ou taille) | rien n'est écrit, aucune colonne ne change, le CV en ligne reste servi — FR-011 |
| Téléchargement concurrent d'un remplacement | le visiteur reçoit la version correspondant à l'URL qu'il a demandée ; les noms de stockage étant uniques, aucun fichier n'est écrasé en place |

Le dernier point n'est pas un détail d'implémentation : c'est précisément parce que le nom de
stockage est unique qu'un remplacement **ne peut pas** corrompre un téléchargement en cours. Écrire
sous un nom fixe le permettrait.

---

## La réserve de la release N

Le PDF hérité, `public/documents/CV/CV_Clement_BOUDINEL_Fullstack_PHP-Symfony.pdf`, **reste suivi par
git** pendant cette release (voir R4). Conséquence à connaître, et à ne pas confondre avec un
défaut :

- après un premier dépôt, Vich supprime le fichier hérité du disque ;
- le déploiement suivant fait `git reset --hard` et **le restaure**, à son URL d'origine.

Il redevient donc accessible, sans être référencé par le pied de page. FR-015 n'est pleinement tenu
qu'après la release N+1, qui retire ce binaire du dépôt. C'est le seul écart temporaire de la
fonctionnalité, et il est consigné dans le *Complexity Tracking* du plan.

---

## Ce qui reste hors contrat

- Le site **ne vérifie pas** que le PDF s'ouvre, qu'il est lisible, ni qu'il contient un CV.
- Aucune conversion, aucune compression, aucune génération de vignette.
- Aucune historisation : la version précédente n'est pas conservée (hypothèse actée dans la spec).
