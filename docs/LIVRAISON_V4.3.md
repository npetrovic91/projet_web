# AUTOSAV v4.3 - Notes de livraison

## Base de depart

- Code repris depuis l'export `public_html (1).zip`.
- Base SQL embarquee remplacee par le dernier export fourni : `database/u166513890_base.sql`.
- Les fichiers runtime sensibles ne sont pas livres : `.env`, logs, sessions PHP et cache applicatif.
- En v4.3.1, `diagnostic_login_demo.php` n'est plus livre dans le paquet production.

## Corrections integrees

- Correction du risque `SQLSTATE[HY093]` dans `BrandModel` avec des placeholders PDO uniques.
- Centralisation dans `Core/Model/BaseModel.php` des helpers dynamiques :
  - `filterColumns()`
  - `tableColumns()`
  - `insertDynamic()`
  - `updateDynamic()`
  - `statusId()`
- Nettoyage des duplications dans :
  - `Modules/Functions/Models/FunctionModel.php`
  - `Modules/Skills/Models/SkillModel.php`
  - `Modules/Qualifications/Models/QualificationModel.php`
- Ajout de `Core/Security/Class/RoleResolver.php`.
- Rechargement des roles et permissions par societe active et marque active.
- Cache session pour l'acces tenant dans `TenantEntitlementMiddleware`, avec invalidation lors du changement de contexte.
- Verification tolerante de `uti_acl_version` dans `AuthMiddleware` pour recharger les droits apres modification ACL.

## SQL a importer sur Hostinger

1. Importer ou conserver la base principale selon ton besoin :
   - `database/u166513890_base.sql`

2. Puis importer la migration v4.3 :
   - `database/migrations/2026_06_04_v43_acl_version_session_invalidation.sql`

Cette migration ne depend pas de `sav_migrations`.

## Important

- Remettre le `.env` de production manuellement sur Hostinger.
- Verifier que `storage/logs`, `storage/sessions` et `storage/cache` sont accessibles en ecriture.
- Apres deploiement, se reconnecter pour repartir avec une session propre.
- Ne pas remettre `diagnostic_login_demo.php` sur le serveur de production.
- ABAC n'est pas active en v4.3 : le correctif recu etait incomplet et referencait des classes absentes.
