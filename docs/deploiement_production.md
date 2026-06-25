# AUTOSAV — Procédure de déploiement production

## 1. Préparation serveur

- PHP 8.2 ou 8.3 recommandé sur Hostinger.
- MariaDB compatible avec le dump actuel.
- Extensions PHP : PDO MySQL, mbstring, fileinfo, openssl, json, zip.
- Racine web pointée vers `public/` uniquement.
- HTTPS obligatoire.

## 2. Configuration

1. Copier `.env.example` vers `.env`.
2. Renseigner les valeurs réelles : base de données, SMTP, clé de chiffrement, jeton healthcheck.
3. Vérifier que `APP_ENV=production` et `APP_DEBUG=false`.
4. Vérifier `TRUSTED_PROXIES` si Cloudflare, reverse proxy ou load balancer est utilisé.

## 3. Installation

```bash
composer2 install --no-dev --optimize-autoloader
php bin/production_preflight.php
```

Sur Hostinger, déposer le projet complet dans `public_html/` si la racine web
ne peut pas être modifiée. Le `.htaccess` racine bloque les fichiers sensibles
et redirige vers `public/`. Si le plan le permet, pointer directement la racine
web vers `public/`.

Les bibliothèques PHP restent dans `vendor/`. Les assets frontend déployables
sont déjà publiés dans `public/assets/vendor/`.

## 4. Base de données

- Importer le dump SQL validé.
- Appliquer les migrations ajoutées dans `database/migrations/`.
- Ne pas exécuter le jeu de données de test en production.

## 5. Tâches planifiées

Installer les entrées de `deploy/cron.example` dans le cron de l'utilisateur applicatif.

## 6. Sauvegardes

- Vérifier `BACKUP_DIR`.
- Tester `php bin/backup_database.php`.
- Conserver au moins 30 jours de sauvegardes.
- Prévoir une copie hors serveur.

## 7. Surveillance

- Endpoint HTTP : `/health.php?token=...`.
- CLI : `php bin/health_check.php`.
- Surveiller les journaux `storage/logs/` et les journaux SQL `sav_journaux_systeme`.

## 8. Sécurité

- Le dossier `storage/` ne doit jamais être exposé publiquement.
- Les secrets ne doivent jamais être committés.
- Le point d'entrée public doit rester `public/index.php`.
- Les uploads sont validés côté serveur par MIME réel.
