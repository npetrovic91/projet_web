# AUTOSAV — Runbook déploiement production
## Hostinger mutualisé (confirmé) / VPS / Serveur dédié
**Version :** 1.1 | **Date :** 2026-06-27 | **Auteur :** DevOps

> **Hébergement confirmé pour ce projet : Hostinger mutualisé classique.**
> Toutes les étapes marquées « VPS uniquement » ne s'appliquent pas tant que
> vous restez sur ce plan (pas d'accès root, pas de configs Apache/Nginx/
> PHP-FPM système, SSH potentiellement absent ou très restreint selon le
> plan exact). Elles sont conservées pour une migration future.

---

## PRÉ-REQUIS

| Composant | Version minimale | Recommandé |
|-----------|-----------------|------------|
| PHP | 8.1 | **8.2** (via hPanel → Sites Web → PHP) |
| MariaDB / MySQL | 10.6 | Fourni par Hostinger |
| Composer | 2.x | Disponible en local pour générer l'artéfact |
| Apache/Nginx | — | Géré par Hostinger sur mutualisé, non éditable |

---

## ÉTAPE 0 — CHECKLIST PRÉ-DÉPLOIEMENT

Avant chaque déploiement, valider **en local** (sur votre machine de dev) :

```bash
make security          # lint + PHPStan + tests + audit CVE
git status             # aucun fichier non commité
git log --oneline -5   # confirmer les commits à déployer
```

---

## ÉTAPE 1 — GÉNÉRATION DES SECRETS

**À faire UNE SEULE FOIS** lors de la première installation.
Ne jamais réutiliser les secrets d'un autre environnement.

```bash
make gen-encryption-key
# → copier la sortie dans .env : ENCRYPTION_KEY=base64:...

make gen-healthcheck-token
# → copier la sortie dans .env : HEALTHCHECK_TOKEN=...

# Mot de passe BDD : générer depuis hPanel → Bases de données → Changer mot de passe
```

---

## ÉTAPE 2 — CONFIGURATION .env

```bash
cp .env.production.template .env
nano .env   # jamais d'édition en clair via un client FTP non chiffré (SFTP/FTPS uniquement)
```

Valeurs obligatoires à renseigner : `DB_PASSWORD`/`DB_PASS`, `ENCRYPTION_KEY`,
`HEALTHCHECK_TOKEN`, `MAIL_HOST`/`MAIL_PORT`/`MAIL_USERNAME`/`MAIL_PASSWORD`,
`APP_URL`, et `MAIL_SANDBOX=false` une fois le SMTP réel testé.

`chmod 640 .env` si votre accès vous le permet (sinon, vérifier dans le
gestionnaire de fichiers hPanel que le fichier n'est pas dans un dossier
listable publiquement).

---

## ÉTAPE 3 — TRANSFERT DES FICHIERS

### Option A : Hostinger mutualisé sans SSH (le plus courant)

```bash
# En local
make install-prod   # composer install --no-dev --optimize-autoloader
tar -czf release.tar.gz --exclude='.git' --exclude='.github' --exclude='tests' \
    --exclude='*.sql' --exclude='database/seeds' .
```

Transférer `release.tar.gz` via **SFTP/FTPS** (hPanel → Fichiers → Détails
FTP, ou un client comme FileZilla en mode SFTP — jamais FTP non chiffré),
puis l'extraire via le **Gestionnaire de fichiers hPanel** (clic droit →
Extraire) directement dans `public_html/`. Aucune commande shell n'est
nécessaire côté serveur avec cette méthode.

### Option B : Hostinger mutualisé avec SSH activé (plans Premium/Business)

Si votre plan inclut un accès SSH (vérifiable dans hPanel → Avancé → SSH
Access), vous pouvez transférer par SCP/SFTP puis lancer les commandes
PHP listées aux étapes suivantes directement en SSH — mais vous n'aurez
toujours pas accès aux configs Apache/Nginx/PHP-FPM système (gérées par
hPanel uniquement, voir Étape 7 Option A).

```bash
scp release.tar.gz user@votre-serveur:/home/USER/domains/devnenad.fr/
ssh user@votre-serveur "cd domains/devnenad.fr && tar -xzf release.tar.gz -C public_html/"
```

### Option C : VPS / Serveur dédié avec accès root

Voir le job `deploy` (désactivé par défaut) dans `.github/workflows/ci.yml`
pour un déploiement automatisé blue/green par symlink — pertinent
uniquement sur cette option.

---

## ÉTAPE 4 — INITIALISATION DES DOSSIERS

Si SSH disponible : `make storage-init && make permissions`.

Sinon (Option A), vérifier via le Gestionnaire de fichiers hPanel que
`storage/{logs,sessions,cache,uploads,exports,backups}` existent et sont
inscriptibles (clic droit → Permissions → 755 ou 775 selon la config PHP
de l'hébergeur).

---

## ÉTAPE 5 — MIGRATIONS BASE DE DONNÉES

Si SSH disponible :
```bash
php bin/migrate.php --dry-run   # simulation
php bin/migrate.php             # application réelle
```

**Sans SSH** : importer manuellement les fichiers de
`database/migrations/*.sql` (jamais `database/seeds/`, réservé à la démo)
via **phpMyAdmin** (hPanel → Bases de données → phpMyAdmin), dans l'ordre
numérique/chronologique des noms de fichiers. `bin/migrate.php` reste
utilisable plus tard dès que SSH est disponible pour suivre les
migrations restantes (table `sav_migrations`).

En cas de doute sur ce qui a déjà été appliqué :
```sql
SELECT * FROM sav_migrations ORDER BY id DESC LIMIT 10;
```

---

## ÉTAPE 6 — VALIDATION PREFLIGHT

Si SSH disponible : `php bin/production_preflight.php` puis
`php scripts/check-production.php`.

Sans SSH, ces scripts peuvent être appelés une fois via un navigateur
**seulement si temporairement exposés et protégés**, ou exécutés lors
d'une future activation SSH. À défaut, vérifier manuellement la
checklist de l'Étape 2 et la configuration PHP via hPanel.

---

## ÉTAPE 7 — CONFIGURATION SERVEUR WEB

### Option A : Hostinger mutualisé (hPanel) — **applicable à ce projet**

1. **Document root** → hPanel → Sites Web → Gérer → Paramètres avancés
   - Définir la racine web sur `public_html/public/` si le plan le permet.
   - Sinon, laisser sur `public_html/` : le `.htaccess` racine redirige et
     bloque l'accès direct à `Core/`, `Modules/`, `storage/`, etc.
     (voir `.htaccess` à la racine du projet).

2. **PHP** → hPanel → Sites Web → PHP
   - Version : PHP **8.2** (minimum 8.1, voir `composer.json`).
   - Extensions : PDO, pdo_mysql, mbstring, openssl, json, fileinfo, zip, gd.

3. **CRON** → hPanel → Avancé → Cron Jobs — voir `deploy/cron.example`
   pour les 4 tâches à créer et les instructions détaillées.

### Option B : VPS / Serveur dédié (Apache) — **VPS uniquement, non applicable actuellement**

```bash
sudo cp deploy/apache-vhost.production.conf /etc/apache2/sites-available/autosav.conf
sudo a2enmod rewrite ssl headers proxy_fcgi setenvif
sudo a2ensite autosav
sudo apache2ctl configtest
sudo systemctl reload apache2
```

### Option C : VPS / Serveur dédié (Nginx) — **VPS uniquement, non applicable actuellement**

```bash
sudo cp deploy/nginx-vhost.production.conf /etc/nginx/sites-available/autosav
sudo ln -s /etc/nginx/sites-available/autosav /etc/nginx/sites-enabled/
# Ajouter les zones de rate limiting dans nginx.conf (section http), voir le fichier.
sudo nginx -t && sudo systemctl reload nginx
```

---

## ÉTAPE 8 — CERTIFICAT SSL

**Hostinger mutualisé** : le SSL (Let's Encrypt) est géré automatiquement
par hPanel → Sites Web → SSL. Rien à faire manuellement, le renouvellement
est automatique.

**VPS uniquement** :
```bash
sudo apt install certbot python3-certbot-apache   # ou python3-certbot-nginx
sudo certbot --apache -d devnenad.fr -d www.devnenad.fr
sudo systemctl status certbot.timer   # vérifier le renouvellement auto
```

---

## ÉTAPE 9 — VÉRIFICATION FINALE

```bash
# Health check HTTP (token requis, voir HEALTHCHECK_TOKEN dans .env)
curl "https://devnenad.fr/health.php?token=VOTRE_TOKEN"

# Vérifier les en-têtes de sécurité
curl -I https://devnenad.fr/
# Attendu : Strict-Transport-Security, X-Frame-Options, X-Content-Type-Options,
# Content-Security-Policy

# Score SSL (objectif A+) :
# https://www.ssllabs.com/ssltest/analyze.html?d=devnenad.fr
```

---

## ROLLBACK D'URGENCE

### Hostinger mutualisé (méthode confirmée pour ce projet)

```bash
# 1. Restaurer la BDD depuis la sauvegarde la plus récente (via phpMyAdmin
#    ou, si SSH disponible : zcat storage/backups/autosav_*.sql.gz | mysql -u DBUSER -p DBNAME)
# 2. Re-déployer l'archive de la release précédente via le Gestionnaire de
#    fichiers hPanel (écraser public_html/ avec l'archive précédente).
```

### VPS avec déploiement blue/green — **VPS uniquement**

```bash
PREVIOUS=$(ls -1dt /var/www/autosav/releases/*/ | sed -n '2p')
ln -sfn "$PREVIOUS" /var/www/autosav/current
```

---

## MONITORING POST-DÉPLOIEMENT

| Service | Comment | Fréquence |
|---------|---------|-----------|
| Health check HTTP | `GET /health.php?token=...` | 5 min (via cron + UptimeRobot) |
| Cron en retard | `php scripts/check-production.php` (section "Cron en retard") | Quotidien |
| Logs erreurs | `storage/logs/error.log` | Quotidien |
| Logs sécurité | `storage/logs/security.log` | Quotidien |
| Backups | `ls -lhS storage/backups/` | Quotidien |
| CVE dépendances | `make audit` (en local, avant chaque déploiement) | Hebdomadaire |

**Services de monitoring externes gratuits recommandés :** UptimeRobot
(alerte si le site ne répond plus), HealthChecks.io (alerte si un cron ne
se déclenche plus — voir `deploy/cron.example`).

---

## ROTATION DES SECRETS (procédure périodique, tous les 90 jours)

1. `make gen-encryption-key` et `make gen-healthcheck-token` en local.
2. Mettre à jour `.env` sur le serveur (via SFTP/éditeur hPanel).
3. Régénérer les sessions actives : vider `storage/sessions/` (déconnecte
   tous les utilisateurs — à planifier en heure creuse).
4. Vider `storage/cache/`.
5. Vérifier `curl https://devnenad.fr/health.php?token=NOUVEAU_TOKEN`.

---

## CONTACTS ET ESCALADE

| Niveau | Déclencheur | Action |
|--------|-------------|--------|
| P1 — Critique | Site down > 5 min | Rollback immédiat |
| P2 — Élevé | Erreurs 500 > 10% | Analyser `storage/logs/error.log`, correctif < 2h |
| P3 — Moyen | Performance dégradée | Ticket, correctif dans les 24h |
| P4 — Bas | Warning en logs | Ticket, planifier |
