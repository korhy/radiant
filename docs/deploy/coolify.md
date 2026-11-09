# Déploiement Coolify

Radiant tourne en production sur Coolify : une ressource **Application** (image Docker tirée de
GHCR) et une ressource **PostgreSQL** gérée par Coolify, dans le même projet/environnement. Voir
[deployment.md](../../.claude/rules/technical/deployment.md) pour le pipeline CI qui produit
l'image.

## Architecture

- **Serveur applicatif** : FrankenPHP (PHP 8.3 + Caddy), image construite depuis
  `docker/frankenphp/Dockerfile.prod`.
- **Image** : construite et poussée par `ci.yml` (job `build-images`) vers
  `ghcr.io/korhy/radiant/frankenphp`, taguée `latest` et `<sha>`. Coolify **tire** cette image ; il
  ne la construit pas.
- **Base de données** : PostgreSQL 16, ressource Coolify séparée de l'application, reliée via
  « Connect To Predefined Network ».
- **Domaine** : `clementboudinel.fr`, proxié par **Cloudflare** (nuage orange) devant le VPS. Mode
  SSL/TLS Cloudflare : **Full** (pas *Flexible* — boucle de redirection avec Coolify ; pas
  *Full strict* — exigerait un certificat déjà valide à l'origine avant même que Let's Encrypt ait
  tourné).
- **Cookbook** n'est pas déployé sur ce VPS : `COOKBOOK_API_URL` pointe vers son URL publique, pas
  vers un nom de conteneur interne.

## Accès au package GHCR

Si le package est privé, authentifier le VPS une fois :

```bash
docker login ghcr.io -u korhy
# coller un PAT classique GitHub, scope read:packages, comme mot de passe
```

Coolify tire ensuite l'image sans configuration supplémentaire.

## Base de données

1. **+ New Resource → Database → PostgreSQL** (version 16).
2. L'URL de connexion **interne** (icône œil) a la forme :
   ```
   postgres://<user>:<password>@<nom-du-conteneur>:5432/<db>
   ```
   Le `<nom-du-conteneur>` est le nom d'hôte interne utilisé dans `DATABASE_URL` — **pas**
   `database` comme en local.
3. Sauvegardes planifiées activées dans l'onglet dédié de la ressource.

> **Piège mot de passe** : si le mot de passe généré contient `@ : / ? # % $`, la DSN
> `DATABASE_URL` est coupée au mauvais endroit. Préférer un mot de passe alphanumérique, ou encoder
> les caractères spéciaux. Le `$` est particulièrement piégeux : Coolify interprète `${...}` dans
> les valeurs de variables d'environnement.

## Application

1. **+ New Resource → Application**, Build Pack : **Docker Image**.
2. Image : `ghcr.io/korhy/radiant/frankenphp:latest`.
3. Port exposé : **`8080`** (défini par `docker/frankenphp/Caddyfile.prod`). Healthcheck : `/healthz`
   répond `OK`.
4. Domaine : `https://clementboudinel.fr`, ajouté via *Manage Domains*.
5. **Réseau.** Coolify isole chaque ressource sur son propre réseau Docker : « Connect To Predefined
   Network » (onglet **Configuration → Advanced**, visible une fois la ressource créée) rattache
   l'application au réseau de la base, avec comme hôte le nom de conteneur Postgres.

## Stockage persistant

`public/documents/CV` et `public/images/personal_projects` portent du contenu déposé depuis
l'admin (Vich), absent de l'image et du dépôt. Un conteneur est reconstruit à chaque déploiement :
sans volume persistant, un redéploiement les efface. Montés dans Coolify
(**Application → Storages → Volume Mount**) :

- `/app/public/documents/CV`
- `/app/public/images/personal_projects`

> **Ne jamais déposer un fichier à la main dans ces dossiers** (terminal, `docker cp`) pour
> remplacer un upload manquant. Vich stocke en base le **nom exact** du fichier
> (`SiteContent::$cvFileName`, `PersonalProject::$fileName`) ; un fichier posé sous un autre nom
> est invisible pour l'app et répond 404, même si le dossier n'est pas vide. Toujours uploader via
> l'admin (`/admin`), qui garde le nom de fichier synchronisé avec la ligne en base.

## Variables d'environnement

Toutes sont des variables **d'exécution** : l'image est déjà construite, aucune n'est nécessaire au
build. Case **« Build Variable? »** décochée. Noms uniquement (valeurs = secrets, jamais dans ce
dépôt) :

| Variable | Valeur / source |
|---|---|
| `APP_ENV` | `prod` |
| `APP_SECRET` | secret applicatif Symfony (`openssl rand -hex 16`) |
| `DATABASE_URL` | URL interne Postgres + `?serverVersion=16&charset=utf8`, **sur une seule ligne** |
| `MAILER_DSN` | Mailjet (SMTP) |
| `COOKBOOK_API_URL` | URL **publique** de Cookbook (pas un nom de conteneur interne) |
| `COOKBOOK_API_USERNAME` / `COOKBOOK_API_PASSWORD` / `COOKBOOK_API_VERSION` | identifiants Cookbook |
| `MESSENGER_TRANSPORT_DSN` | `doctrine://default?auto_setup=0` |

## Hors périmètre

- **Sauvegardes applicatives et logs centralisés** : non couverts. Les sauvegardes planifiées de la
  ressource Postgres Coolify sont le seul filet de sécurité.
- **Cookbook** : reste sur son propre hébergement, pas sur ce VPS.

## Recréer la ressource (disaster recovery)

1. Vérifier que `ghcr.io/korhy/radiant/frankenphp:latest` existe (job `build-images` de `ci.yml`).
2. Créer la ressource Postgres, relever l'URL interne.
3. Créer la ressource Application (Docker Image), activer *Connect To Predefined Network*.
4. Monter les deux volumes persistants.
5. Renseigner toutes les variables d'environnement.
6. Déployer ; vérifier `/healthz`, puis `doctrine:migrations:status` dans les logs du conteneur
   (cf. mémoire *Déploiement prod fragile* : un déploiement vert ne garantit pas que la migration
   est passée).
7. DNS : enregistrement `A` chez Cloudflare vers l'IP du VPS, mode SSL/TLS **Full**, puis domaine
   ajouté côté Coolify pour l'émission du certificat.
8. Automatiser les redéploiements : webhook Coolify appelé en fin de `build-images`, ou
   déploiement manuel (*Redeploy*).
