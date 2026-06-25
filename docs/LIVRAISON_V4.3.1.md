# AUTOSAV v4.3.1 - Notes de livraison

## Objet

Version corrective de securite basee sur v4.3.

## Corrections integrees

- Suppression de `diagnostic_login_demo.php` du paquet de production.
- Blocage explicite de `diagnostic_login_demo.php` dans `.htaccess`.
- Ajout des permissions explicites pour les modules sensibles :
  - abonnements
  - verrous
  - validations
  - standards
  - horaires
- Ajout des alias `has_permission()` associes aux nouveaux modules.
- Remplacement de `requireAuth()` par `requirePermission()` dans :
  - `StandardController`
  - `ValidationController`
  - `HorairesController`
  - `JobController`
  - `VerrousController`
- Nettoyage `AuthService` :
  - suppression du code mort remplace par `RoleResolver`
  - correction des messages mojibake visibles
- Delegation super-admin vers `RoleResolver` dans :
  - `UserService`
  - `DashboardService`

## SQL Hostinger

Importer dans cet ordre :

1. `database/migrations/2026_06_04_v43_acl_version_session_invalidation.sql`
2. `database/migrations/2026_06_05_v431_permissions_modules_sensibles.sql`

La migration v4.3.1 ne depend pas de `sav_migrations` et ajoute aussi `uti_acl_version` si la colonne manque encore.

## Important

- Ne pas remettre `diagnostic_login_demo.php` sur le serveur de production.
- Conserver le `.env` reel de Hostinger hors zip.
- Se reconnecter apres import SQL pour repartir avec les permissions rechargees.
