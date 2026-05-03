-- ============================================================
-- AUTOSAV — Seed : Permissions système
-- ============================================================
INSERT INTO `sav_permissions` (`prm_code`, `prm_module`, `prm_action`, `prm_label`) VALUES
  ('users.read',         'Users',   'read',   'Lire les utilisateurs'),
  ('users.create',       'Users',   'create', 'Créer des utilisateurs'),
  ('users.update',       'Users',   'update', 'Modifier des utilisateurs'),
  ('users.delete',       'Users',   'delete', 'Supprimer des utilisateurs'),
  ('companies.read',     'Companies','read',  'Lire les entreprises'),
  ('companies.create',   'Companies','create','Créer des entreprises'),
  ('companies.update',   'Companies','update','Modifier des entreprises'),
  ('companies.delete',   'Companies','delete','Supprimer des entreprises'),
  ('security.read',      'Administration','read',   'Voir la sécurité'),
  ('security.manage',    'Administration','manage', 'Gérer blocages IP/email'),
  ('gdpr.manage',        'GDPR',    'manage', 'Gérer les demandes RGPD'),
  ('maintenance.manage', 'Maintenance','manage','Gérer la maintenance'),
  ('admin.access',       'Administration','read',   'Accès espace administration'),
  ('roles.manage',       'Roles',   'manage', 'Gérer les rôles et permissions')
ON DUPLICATE KEY UPDATE `prm_label` = VALUES(`prm_label`);
