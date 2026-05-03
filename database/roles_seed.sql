-- ============================================================
-- AUTOSAV — Seed : Rôles système
-- ============================================================
INSERT INTO `sav_roles`
  (`rol_code`, `rol_label`, `rol_description`, `rol_level`, `rol_can_create_roles`, `rol_is_system`, `rol_is_active`)
VALUES
  ('SUPERADMIN',      'Super Administrateur',      'Contrôle total de l application',                           1,  '["SUPERADMIN","CONSTRUCTEUR","IMPORTATEUR","CONCESSIONNAIRE","REPARATEUR","MANAGER","USER"]', 1, 1),
  ('CONSTRUCTEUR',    'Constructeur',               'Crée et gère les importateurs et utilisateurs constructeur', 10, '["IMPORTATEUR","USER"]', 1, 1),
  ('IMPORTATEUR',     'Importateur',                'Crée et gère les concessions et utilisateurs importateur',  20, '["CONCESSIONNAIRE","USER"]', 1, 1),
  ('CONCESSIONNAIRE', 'Administrateur Concession',  'Crée et gère les utilisateurs de sa concession',           30, '["MANAGER","USER"]', 1, 1),
  ('REPARATEUR',      'Réparateur Indépendant',     'Gère ses utilisateurs internes',                            40, '["MANAGER","USER"]', 1, 1),
  ('MANAGER',         'Manager de Service',         'Visibilité élargie sur son service',                        50, '["USER"]', 1, 1),
  ('USER',            'Utilisateur Standard',       'Accès limité à ses données et espace personnel',            60, '[]', 1, 1),
  ('ADMIN_SECURITE',  'Administrateur Sécurité',    'Gestion blocages IP/email et supervision sécurité',         5,  '[]', 1, 1),
  ('REFERENT_RGPD',   'Référent RGPD',              'Traitement des demandes RGPD',                              5,  '[]', 1, 1),
  ('ADMIN_MAINTENANCE','Admin Maintenance',          'Gestion du mode maintenance et supervision',                5,  '[]', 1, 1)
ON DUPLICATE KEY UPDATE `rol_label` = VALUES(`rol_label`);
