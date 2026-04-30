-- ============================================================
-- AUTOSAV — Seed : Compte SuperAdmin initial
-- IMPORTANT : Changer le mot de passe IMMÉDIATEMENT après installation
-- MDP par défaut : Admin@Autosav2025! (hash Argon2ID)
-- ============================================================

-- Insérer l'entreprise système
INSERT INTO `sav_company_types` (`ctp_id`, `ctp_code`, `ctp_label`, `ctp_sort_order`)
VALUES (99, 'SYSTEME', 'Système', 99)
ON DUPLICATE KEY UPDATE `ctp_code` = 'SYSTEME';

INSERT INTO `sav_companies` (`com_id`, `com_uuid`, `com_type_id`, `com_name`, `com_is_active`)
VALUES (1, '00000000-0000-0000-0000-000000000001', 99, 'AUTOSAV SYSTEM', 1)
ON DUPLICATE KEY UPDATE `com_name` = 'AUTOSAV SYSTEM';

-- Insérer le SuperAdmin (UUID fixe pour idempotence)
INSERT INTO `sav_users`
  (`use_uuid`, `use_email`, `use_password_hash`, `use_lastname`, `use_firstname`,
   `use_email_verified_at`, `use_is_active`, `use_is_system`, `use_active_company_id`,
   `use_terms_accepted_version`, `use_terms_accepted_at`)
VALUES
  ('00000000-0000-0000-0000-000000000001',
   'admin@autosav.fr',
   '$argon2id$v=19$m=65536,t=4,p=2$CHANGE_THIS_HASH_IN_PRODUCTION',
   'Administrateur', 'Super',
   NOW(), 1, 1, 1,
   '1.0', NOW())
ON DUPLICATE KEY UPDATE `use_email` = 'admin@autosav.fr';

-- Associer le rôle SuperAdmin
INSERT INTO `sav_user_roles` (`url_user_id`, `url_role_id`, `url_is_primary`, `url_granted_by`)
SELECT u.use_id, r.rol_id, 1, u.use_id
FROM `sav_users` u, `sav_roles` r
WHERE u.use_email = 'admin@autosav.fr'
  AND r.rol_code = 'SUPERADMIN'
ON DUPLICATE KEY UPDATE `url_is_primary` = 1;

-- Associer à l'entreprise système
INSERT INTO `sav_user_companies` (`ucm_user_id`, `ucm_company_id`, `ucm_is_primary`)
SELECT u.use_id, 1, 1
FROM `sav_users` u
WHERE u.use_email = 'admin@autosav.fr'
ON DUPLICATE KEY UPDATE `ucm_is_primary` = 1;
