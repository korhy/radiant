# Phase 1 — Modèle de données

**Feature**: `008-editable-about-cv` · **Date**: 2026-09-18

Une table, une ligne, quatre colonnes utiles. Le modèle est volontairement plat : il n'y a ni
relation, ni collection, ni état à faire transiter.

---

## Entité `SiteContent`

`src/Entity/SiteContent.php` · table `site_content` · repository `SiteContentRepository`

| Propriété | Type PHP | Colonne | Nullable | Rôle |
|---|---|---|---|---|
| `id` | `?int` | `id`, PK auto | non | identité technique |
| `aboutText` | `?string` | `about_text` TEXT | oui | le paragraphe « À propos », marqueurs `**…**` compris, **tel que saisi** |
| `cvFile` | `?File` | — (non persisté) | oui | propriété de travail VichUploader, jamais en base |
| `cvFileName` | `?string` | `cv_file_name` VARCHAR(255) | oui | nom de stockage du fichier, écrit par Vich |
| `cvOriginalName` | `?string` | `cv_original_name` VARCHAR(255) | oui | nom d'origine du fichier déposé, servi dans l'attribut `download` |
| `updatedAt` | `?\DateTimeImmutable` | `updated_at` TIMESTAMP | oui | horodatage exigé par Vich pour déclencher la persistance sur remplacement de fichier |

`#[Vich\Uploadable]` sur la classe ; `#[Vich\UploadableField(mapping: 'cv', fileNameProperty: 'cvFileName', originalNameProperty: 'cvOriginalName')]` sur `cvFile`.

### Pourquoi `updatedAt` n'est pas décoratif

VichUploader ne détecte un remplacement de fichier que si **une propriété persistée de l'entité
change**. Déposer un nouveau PDF ne modifie aucune colonne du point de vue de Doctrine — le nom de
stockage est écrit par Vich *après* le calcul du changeset. `setCvFile()` doit donc toucher
`updatedAt`, exactement comme `PersonalProject` le fait déjà. Sans ça, le dépôt est silencieusement
ignoré : c'est le piège classique de ce bundle, et il ne se voit qu'à l'exécution.

### Invariantes

- **Ligne unique.** La table contient exactement une ligne. Tenue par le back-office (`NEW` et
  `DELETE` désactivées, voir R6) et amorcée par la migration. Le repository expose
  `findCurrent(): ?SiteContent` et est le seul point d'accès.
- **`aboutText` est du texte, pas du balisage.** Il est stocké brut, marqueurs compris. Aucune
  transformation à l'écriture : le rendu est calculé à la lecture, et rien d'autre ne le consomme.
- **`cvFileName` et `cvOriginalName` vont par paire.** Les deux nuls, ou les deux renseignés. Les
  deux nuls signifient « aucun CV publié » et le lien du pied de page disparaît (FR-013).

### Validation

| Cible | Contrainte | Message |
|---|---|---|
| `aboutText` | `Assert\Length(max: 2000)` | en français, explicite |
| `cvFile` | `Assert\File(maxSize: '5M', mimeTypes: ['application/pdf'])` | en français, explicite |

Les messages sont écrits en français sur les contraintes, `default_locale` valant `en` — voir R7.

---

## Objet de rendu `TextSegment`

`src/DTO/TextSegment.php` — DTO immuable, aux côtés de `ContactDTO`.

| Propriété | Type | Rôle |
|---|---|---|
| `text` | `string` | le fragment, en texte pur |
| `highlighted` | `bool` | ce fragment porte-t-il la couleur de marque |

`final readonly class`, constructeur promu, aucun comportement. Il n'est **jamais persisté** : c'est
la sortie de `HighlightParser` et l'entrée du gabarit.

C'est ce type qui porte la garantie de FR-006 : `text` est du texte, Twig l'échappe, et il n'existe
aucun champ par lequel du balisage pourrait transiter.

---

## Migration

Une seule migration, `up()` en deux temps :

1. `CREATE TABLE site_content (…)` — colonnes toutes nullables sauf la clé primaire, donc
   rétro-compatible au sens de la constitution : un code antérieur qui ignore la table continue de
   fonctionner.
2. `INSERT INTO site_content (about_text, cv_file_name, cv_original_name, updated_at) VALUES (…)` —
   le paragraphe actuel repris de `templates/portfolio/section/about.html.twig`, ses cinq fragments
   aujourd'hui enveloppés dans `<span class="text-brand-fg">` convertis en `**…**`, et le nom du PDF
   hérité dans les deux colonnes de nom.

`down()` fait `DROP TABLE site_content`.

**Contrainte de production** : cette migration s'exécute sans surveillance à chaque déploiement vert
sur `main`. Elle ne fait que du DDL et un `INSERT` — pas d'entrées/sorties disque, pas
d'interaction, pas de destruction. Le `INSERT` n'est pas idempotent, mais il ne s'exécute qu'une
fois par base, la table étant créée dans la même migration.

**Attention au dialecte** : le `INSERT` contient des apostrophes typographiques et des accents.
L'échappement doit être celui de Doctrine, et le test d'exécution vaut sur **PostgreSQL** — la suite
PHPUnit tourne sur SQLite et ne prouve rien sur ce point.
