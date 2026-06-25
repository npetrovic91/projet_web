-- ============================================================
-- AUTOSAV — Migration 2026_06_05_v431_permissions_modules_sensibles
-- ------------------------------------------------------------
-- RECONSTITUTION : fichier référencé par
-- tests/Unit/V431SecurityHardeningTest.php mais absent de
-- l'archive public_html (6).zip (cf. ROADMAP_PRODUCTION.md §1.1).
-- Reconstitué à partir des permissions réellement présentes en
-- base (per_id 60-67 dans le dump de production : abonnement.gerer,
-- verrou.consulter/gerer, validation.*, standard.*, horaire.*).
--
-- Contexte (v4.3.1) : les contrôleurs Abonnements, Verrous,
-- Validation, Standards et Horaires ne reposaient jusqu'ici que sur
-- requireAuth() (authentifié = autorisé), sans permission dédiée.
-- Cette migration introduit les permissions fines manquantes et
-- force la réévaluation des droits des utilisateurs concernés en
-- invalidant leur cache ACL de session (uti_acl_version), pour que
-- le retrait/l'ajout de droits soit pris en compte sans attendre une
-- reconnexion.
--
-- Idempotent : INSERT IGNORE / WHERE NOT EXISTS partout.
-- ============================================================

INSERT IGNORE INTO sav_permissions (per_code, per_description, per_statut_id, per_cree_le)
VALUES
    ('verrou.consulter', 'Consulter verrous, sessions et contextes utilisateur', 1, NOW()),
    ('verrou.gerer', 'Créer, modifier et libérer les verrous d''entités', 1, NOW()),
    ('validation.consulter', 'Consulter les demandes de validation', 1, NOW()),
    ('standard.consulter', 'Consulter standards, versions et exigences', 1, NOW()),
    ('standard.gerer', 'Créer et modifier standards, versions et exigences', 1, NOW()),
    ('horaire.consulter', 'Consulter horaires et calendriers de travail', 1, NOW()),
    ('horaire.gerer', 'Créer et modifier horaires et calendriers de travail', 1, NOW()),
    ('abonnement.gerer', 'Créer et modifier abonnements, formules et espaces', 1, NOW());

-- Le rôle technique super_administrateur reçoit l'ensemble de ces
-- permissions sensibles (gestion + consultation).
INSERT INTO sav_roles_permissions (rpe_role_id, rpe_permission_id, rpe_effet, rpe_cree_le)
SELECT r.rol_id, p.per_id, 'autoriser', NOW()
FROM sav_roles r
JOIN sav_permissions p ON p.per_code IN (
    'verrou.consulter', 'verrou.gerer', 'validation.consulter', 'validation.gerer',
    'standard.consulter', 'standard.gerer', 'horaire.consulter', 'horaire.gerer',
    'abonnement.gerer'
)
WHERE r.rol_code = 'super_administrateur'
AND NOT EXISTS (
    SELECT 1 FROM sav_roles_permissions rp
    WHERE rp.rpe_role_id = r.rol_id AND rp.rpe_permission_id = p.per_id
);

-- administrateur_general_societe reçoit les permissions de gestion
-- correspondant à ce qui était auparavant ouvert à tout authentifié.
INSERT INTO sav_roles_permissions (rpe_role_id, rpe_permission_id, rpe_effet, rpe_cree_le)
SELECT r.rol_id, p.per_id, 'autoriser', NOW()
FROM sav_roles r
JOIN sav_permissions p ON p.per_code IN (
    'verrou.consulter', 'verrou.gerer', 'validation.consulter', 'validation.gerer',
    'standard.consulter', 'standard.gerer', 'horaire.consulter', 'horaire.gerer'
)
WHERE r.rol_code = 'administrateur_general_societe'
AND NOT EXISTS (
    SELECT 1 FROM sav_roles_permissions rp
    WHERE rp.rpe_role_id = r.rol_id AND rp.rpe_permission_id = p.per_id
);

-- Invalide le cache ACL de session de tous les utilisateurs détenant
-- un rôle contextuel actif sur l'un de ces deux rôles, pour forcer la
-- relecture des permissions au prochain appel (cf. AuthMiddleware,
-- qui compare uti_acl_version au numéro stocké en session).
UPDATE sav_utilisateurs u
JOIN sav_roles_contextuels_utilisateurs rcu ON rcu.rcu_utilisateur_id = u.uti_id AND rcu.rcu_statut_id = 1
JOIN sav_roles r ON r.rol_id = rcu.rcu_role_id
SET u.uti_acl_version = u.uti_acl_version + 1
WHERE r.rol_code IN ('super_administrateur', 'administrateur_general_societe');
