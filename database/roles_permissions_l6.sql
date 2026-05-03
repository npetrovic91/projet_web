-- ============================================================
-- Seed L6 : rôles avec règles de création hiérarchique
-- ============================================================

-- Insérer les rôles de base (si pas encore fait en L5)
INSERT IGNORE INTO `sav_roles` (
    rol_code, rol_label, rol_description, rol_level, rol_can_create_roles, rol_is_system, rol_is_active
) VALUES
('SUPERADMIN',      'Super Administrateur',    'Accès total sans restriction',                          1,  '["SUPERADMIN","CONSTRUCTEUR","IMPORTATEUR","CONCESSIONNAIRE","REPARATEUR","MANAGER","USER"]', 1, 1),
('CONSTRUCTEUR',    'Constructeur',            'Gère les importateurs et utilisateurs constructeur',   10, '["IMPORTATEUR","MANAGER","USER"]',  1, 1),
('IMPORTATEUR',     'Importateur',             'Gère les concessions et utilisateurs importateur',     20, '["CONCESSIONNAIRE","MANAGER","USER"]', 1, 1),
('CONCESSIONNAIRE', 'Administrateur Concession','Gère les utilisateurs de sa concession',              30, '["MANAGER","USER"]', 1, 1),
('REPARATEUR',      'Réparateur',              'Gère les utilisateurs internes du réparateur',         40, '["USER"]', 1, 1),
('MANAGER',         'Manager de service',      'Visibilité élargie sur son équipe',                    50, '["USER"]', 1, 1),
('USER',            'Utilisateur standard',    'Accès limité à son espace personnel',                  60, '[]',        1, 1),
('ADMIN_SECURITE',  'Admin Sécurité',          'Gestion des blocages et supervision sécurité',          5, '[]',         1, 1),
('REFERENT_RGPD',   'Référent RGPD',           'Traitement des demandes RGPD',                          5, '[]',         1, 1),
('ADMIN_MAINTENANCE','Admin Maintenance',      'Gestion du mode maintenance et supervision',            5, '[]',         1, 1);

-- Permissions spécifiques module Users
INSERT IGNORE INTO `sav_permissions` (prm_code, prm_module, prm_action, prm_label) VALUES
('users.read',   'Users', 'read',   'Consulter les utilisateurs'),
('users.create', 'Users', 'create', 'Créer des utilisateurs'),
('users.update', 'Users', 'update', 'Modifier des utilisateurs'),
('users.delete', 'Users', 'delete', 'Supprimer des utilisateurs'),
('users.manage', 'Users', 'manage', 'Gérer tous les utilisateurs (SuperAdmin)');

-- Attribution permissions → rôles
-- SUPERADMIN : tout
INSERT IGNORE INTO sav_role_permissions (rlp_role_id, rlp_permission_id)
SELECT r.rol_id, p.prm_id
FROM sav_roles r, sav_permissions p
WHERE r.rol_code = 'SUPERADMIN' AND p.prm_module IN ('Users');

-- CONSTRUCTEUR : lire + créer + modifier
INSERT IGNORE INTO sav_role_permissions (rlp_role_id, rlp_permission_id)
SELECT r.rol_id, p.prm_id FROM sav_roles r, sav_permissions p
WHERE r.rol_code = 'CONSTRUCTEUR' AND p.prm_code IN ('users.read','users.create','users.update');

-- IMPORTATEUR : lire + créer + modifier
INSERT IGNORE INTO sav_role_permissions (rlp_role_id, rlp_permission_id)
SELECT r.rol_id, p.prm_id FROM sav_roles r, sav_permissions p
WHERE r.rol_code = 'IMPORTATEUR' AND p.prm_code IN ('users.read','users.create','users.update');

-- CONCESSIONNAIRE : lire + créer (scope restreint au service) + modifier
INSERT IGNORE INTO sav_role_permissions (rlp_role_id, rlp_permission_id)
SELECT r.rol_id, p.prm_id FROM sav_roles r, sav_permissions p
WHERE r.rol_code = 'CONCESSIONNAIRE' AND p.prm_code IN ('users.read','users.create','users.update');

-- REPARATEUR : lire + créer + modifier (interne uniquement)
INSERT IGNORE INTO sav_role_permissions (rlp_role_id, rlp_permission_id)
SELECT r.rol_id, p.prm_id FROM sav_roles r, sav_permissions p
WHERE r.rol_code = 'REPARATEUR' AND p.prm_code IN ('users.read','users.create','users.update');

-- MANAGER : lire son équipe uniquement
INSERT IGNORE INTO sav_role_permissions (rlp_role_id, rlp_permission_id)
SELECT r.rol_id, p.prm_id FROM sav_roles r, sav_permissions p
WHERE r.rol_code = 'MANAGER' AND p.prm_code = 'users.read';

-- USER : lire son propre profil uniquement (géré par middleware not by permission)
INSERT IGNORE INTO sav_role_permissions (rlp_role_id, rlp_permission_id)
SELECT r.rol_id, p.prm_id FROM sav_roles r, sav_permissions p
WHERE r.rol_code = 'USER' AND p.prm_code = 'users.read';

9. TESTS UNITAIRES

9.1 UserServiceTest.php
