# Déploiement Coolify

Configuration Coolify pour Radiant : une ressource **Application** (image Docker tirée de GHCR) et
une ressource **PostgreSQL** gérée par Coolify, dans le même projet/environnement. La production
réelle tourne via `deploy.yml` (SSH, serveur dédié OVH, sans conteneur) — voir
[deployment.md](../../.claude/rules/technical/deployment.md) ; ce document décrit la configuration
à activer pour basculer sur Coolify.

## Architecture

- **Serveur applicatif** : FrankenPHP (PHP 8.3 + Caddy), image construite depuis
  `docker/frankenphp/Dockerfile.prod`.
- **Image** : construite et poussée par `ci.yml` (job `build-images`) vers
  `ghcr.io/korhy/radiant/frankenphp`, taguée `latest` et `<sha>`. Coolify **tire** cette image ; il
  ne la construit pas.
- **Base de données** : PostgreSQL 16, ressource Coolify séparée de l'application.
- **Cookbook** n'est pas déployé sur ce VPS : `COOKBOOK_API_URL` pointe vers son URL publique, pas
  vers un nom de conteneur interne.

## Accès au package GHCR

Si le package est privé, authentifier le VPS une fois :

```bash
docker login ghcr.io -u korhy
# coller un PAT classique GitHub, scope read:packages, comme mot de passe
```

Coolify tire ensuite l'image sans configuration supplémentaire.

## 1. Base de données

1. **+ New Resource → Database → PostgreSQL** (version 16).
2. Relever l'URL de connexion **interne** (icône œil) :
   ```
   postgres://<user>:<password>@<nom-du-conteneur>:5432/<db>
   ```
   Le `<nom-du-conteneur>` est le nom d'hôte interne à utiliser dans `DATABASE_URL`.
3. Activer les sauvegardes planifiées dans l'onglet dédié de la ressource.

> **Piège mot de passe** : si le mot de passe généré contient `@ : / ? # % $`, la DSN
> `DATABASE_URL` sera coupée au mauvais endroit. Préférer un mot de passe alphanumérique, ou encoder
> les caractères spéciaux. Le `$` est particulièrement piégeux : Coolify interprète `${...}` dans
> les valeurs de variables d'environnement.

## 2. Application

1. **+ New Resource → Application**, Build Pack : **Docker Image**.
2. Image : `ghcr.io/korhy/radiant/frankenphp:latest`.
3. Port exposé : **`8080`** (défini par `docker/frankenphp/Caddyfile.prod`). Healthcheck : `/healthz`
   répond `OK`.
4. Domaine : le domaine de prod. Coolify obtient le certificat via Let's Encrypt.
5. **Réseau.** Coolify isole chaque ressource sur son propre réseau Docker. Pour que l'application
   atteigne la base, activer **« Connect To Predefined Network »** dans l'onglet
   **Configuration → Advanced** de l'application (visible une fois la ressource créée), avec comme
   hôte le nom de conteneur relevé à l'étape 1.

## 3. Stockage persistant

`public/documents/CV` et `public/images/personal_projects` portent du contenu déposé depuis
l'admin (Vich), absent de l'image et du dépôt. Un conteneur est reconstruit à chaque déploiement :
sans volume persistant, un redéploiement les efface. Monter, dans Coolify
(**Application → Storages → + Add → Volume Mount**) :

- `/app/public/documents/CV`
- `/app/public/images/personal_projects`

## 4. Variables d'environnement

Toutes sont des variables **d'exécution** : l'image est déjà construite, aucune n'est nécessaire au
build. Laisser **« Build Variable? »** décoché. Noms uniquement (valeurs = secrets, jamais dans ce
dépôt) :

| Variable | Valeur / source |
|---|---|
| `APP_ENV` | `prod` |
| `APP_SECRET` | secret applicatif Symfony (`openssl rand -hex 16`) |
| `DATABASE_URL` | URL interne de l'étape 1, + `?serverVersion=16&charset=utf8` |
| `MAILER_DSN` | Mailjet (SMTP) |
| `COOKBOOK_API_URL` | URL **publique** de Cookbook (pas un nom de conteneur interne) |
| `COOKBOOK_API_USERNAME` / `COOKBOOK_API_PASSWORD` / `COOKBOOK_API_VERSION` | identifiants Cookbook |
| `MESSENGER_TRANSPORT_DSN` | `doctrine://default?auto_setup=0` |

Vérifier après enregistrement que `DATABASE_URL` apparaît sur **une seule ligne** dans l'interface
Coolify : un retour à la ligne mal placé la couperait en deux variables inutilisables.

## Hors périmètre

- **Sauvegardes applicatives et logs centralisés** : non couverts ici. Les sauvegardes planifiées
  de la ressource Postgres Coolify (étape 1) sont le seul filet de sécurité pour l'instant.
- **Décommissionnement du serveur dédié OVH** : aucune action dessus.

## Checklist de mise en production

1. Vérifier que `ghcr.io/korhy/radiant/frankenphp:latest` existe (job `build-images` de `ci.yml`).
2. Créer la ressource Postgres Coolify, relever l'URL interne.
3. Créer la ressource Application (Docker Image), activer *Connect To Predefined Network*.
4. Monter les deux volumes persistants.
5. Renseigner toutes les variables d'environnement.
6. Déployer ; vérifier `/healthz`, puis `doctrine:migrations:status` dans les logs du conteneur
   (cf. mémoire *Déploiement prod fragile* : un déploiement vert ne garantit pas que la migration
   est passée).
7. Pointer le domaine de prod vers le VPS Coolify, valider le certificat HTTPS.
8. Automatiser les redéploiements : webhook Coolify appelé en fin de `build-images`, ou déploiement
   manuel (*Redeploy*).
9. Retirer `deploy.yml` ; mettre à jour `README.md` et
   [deployment.md](../../.claude/rules/technical/deployment.md) pour refléter Coolify comme
   déploiement réel.
