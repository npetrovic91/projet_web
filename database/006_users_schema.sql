-- AutoSAV - LIVRABLE 6 - Utilisateurs, roles, rattachements, hierarchie
-- Cible : MySQL/MariaDB Hostinger, InnoDB, utf8mb4.

CREATE TABLE IF NOT EXISTS sav_roles (
  rol_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rol_uuid CHAR(36) NOT NULL,
  rol_code VARCHAR(60) NOT NULL,
  rol_label VARCHAR(120) NOT NULL,
  rol_level INT UNSIGNED NOT NULL DEFAULT 100,
  rol_description VARCHAR(255) NULL,
  rol_can_create_roles JSON NULL,
  rol_is_system TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  rol_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  rol_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  rol_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (rol_id),
  UNIQUE KEY uq_rol_uuid (rol_uuid),
  UNIQUE KEY uq_rol_code (rol_code),
  KEY idx_rol_level_active (rol_level, rol_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_users (
  use_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  use_uuid CHAR(36) NOT NULL,
  use_username VARCHAR(80) NULL,
  use_email VARCHAR(191) NOT NULL,
  use_password_hash VARCHAR(255) NOT NULL,
  use_password_changed_at DATETIME NULL,
  use_must_change_password TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  use_password_expires_at DATETIME NULL,
  use_email_verified_at DATETIME NULL,
  use_email_verification_token VARCHAR(191) NULL,
  use_email_verification_sent_at DATETIME NULL,
  use_email_verification_attempts INT UNSIGNED NOT NULL DEFAULT 0,
  use_2fa_enabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  use_2fa_secret VARCHAR(191) NULL,
  use_2fa_backup_codes JSON NULL,
  use_2fa_enabled_at DATETIME NULL,
  use_2fa_method VARCHAR(30) NULL,
  use_civility VARCHAR(20) NULL,
  use_lastname VARCHAR(120) NOT NULL,
  use_firstname VARCHAR(120) NOT NULL,
  use_phone VARCHAR(30) NULL,
  use_mobile VARCHAR(30) NULL,
  use_photo_url VARCHAR(255) NULL,
  use_employee_number VARCHAR(80) NULL,
  use_department VARCHAR(120) NULL,
  use_job_title VARCHAR(150) NULL,
  use_manager_id BIGINT UNSIGNED NULL,
  use_active_company_id BIGINT UNSIGNED NULL,
  use_active_brand_id BIGINT UNSIGNED NULL,
  use_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  use_is_locked TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  use_locked_until DATETIME NULL,
  use_locked_reason VARCHAR(255) NULL,
  use_failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
  use_last_login_at DATETIME NULL,
  use_last_login_ip VARCHAR(45) NULL,
  use_last_user_agent VARCHAR(500) NULL,
  use_terms_accepted_version VARCHAR(50) NULL,
  use_terms_accepted_at DATETIME NULL,
  use_locale VARCHAR(10) NOT NULL DEFAULT 'fr',
  use_timezone VARCHAR(80) NOT NULL DEFAULT 'Europe/Paris',
  use_gdpr_anonymized TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  use_is_system TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  use_created_by BIGINT UNSIGNED NULL,
  use_updated_by BIGINT UNSIGNED NULL,
  use_deleted_by BIGINT UNSIGNED NULL,
  use_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  use_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  use_deleted_at DATETIME NULL,
  use_deleted_reason VARCHAR(255) NULL,
  PRIMARY KEY (use_id),
  UNIQUE KEY uq_use_uuid (use_uuid),
  UNIQUE KEY uq_use_email (use_email),
  KEY idx_use_name (use_lastname, use_firstname),
  KEY idx_use_active (use_is_active, use_deleted_at),
  KEY idx_use_locked (use_is_locked, use_locked_until),
  KEY idx_use_active_context (use_active_company_id, use_active_brand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE sav_users
  ADD COLUMN IF NOT EXISTS use_username VARCHAR(80) NULL AFTER use_uuid,
  ADD COLUMN IF NOT EXISTS use_civility VARCHAR(20) NULL AFTER use_2fa_method,
  ADD COLUMN IF NOT EXISTS use_active_company_id BIGINT UNSIGNED NULL AFTER use_manager_id,
  ADD COLUMN IF NOT EXISTS use_active_brand_id BIGINT UNSIGNED NULL AFTER use_active_company_id,
  ADD COLUMN IF NOT EXISTS use_locked_reason VARCHAR(255) NULL AFTER use_locked_until,
  ADD COLUMN IF NOT EXISTS use_terms_accepted_version VARCHAR(50) NULL AFTER use_last_user_agent,
  ADD COLUMN IF NOT EXISTS use_terms_accepted_at DATETIME NULL AFTER use_terms_accepted_version,
  ADD COLUMN IF NOT EXISTS use_gdpr_anonymized TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER use_timezone,
  ADD COLUMN IF NOT EXISTS use_created_by BIGINT UNSIGNED NULL AFTER use_is_system,
  ADD COLUMN IF NOT EXISTS use_updated_by BIGINT UNSIGNED NULL AFTER use_created_by,
  ADD COLUMN IF NOT EXISTS use_deleted_by BIGINT UNSIGNED NULL AFTER use_updated_by;

CREATE TABLE IF NOT EXISTS sav_permissions (
  per_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  per_code VARCHAR(100) NOT NULL,
  per_label VARCHAR(150) NOT NULL,
  per_module VARCHAR(80) NOT NULL,
  per_description VARCHAR(255) NULL,
  per_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  per_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (per_id),
  UNIQUE KEY uq_per_code (per_code),
  KEY idx_per_module (per_module, per_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_role_permissions (
  rpe_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rpe_role_id BIGINT UNSIGNED NOT NULL,
  rpe_permission_id BIGINT UNSIGNED NOT NULL,
  rpe_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rpe_id),
  UNIQUE KEY uq_rpe_role_permission (rpe_role_id, rpe_permission_id),
  KEY idx_rpe_role (rpe_role_id),
  KEY idx_rpe_permission (rpe_permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_user_roles (
  url_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  url_user_id BIGINT UNSIGNED NOT NULL,
  url_role_id BIGINT UNSIGNED NOT NULL,
  url_is_primary TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  url_granted_by BIGINT UNSIGNED NULL,
  url_granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  url_revoked_by BIGINT UNSIGNED NULL,
  url_revoked_at DATETIME NULL,
  url_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (url_id),
  UNIQUE KEY uq_url_user_role (url_user_id, url_role_id),
  KEY idx_url_user_primary (url_user_id, url_is_primary),
  KEY idx_url_role (url_role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_user_companies (
  ucm_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ucm_user_id BIGINT UNSIGNED NOT NULL,
  ucm_company_id BIGINT UNSIGNED NOT NULL,
  ucm_is_primary TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  ucm_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  ucm_joined_at DATE NULL,
  ucm_left_at DATE NULL,
  ucm_created_by BIGINT UNSIGNED NULL,
  ucm_updated_by BIGINT UNSIGNED NULL,
  ucm_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ucm_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ucm_id),
  UNIQUE KEY uq_ucm_user_company (ucm_user_id, ucm_company_id),
  KEY idx_ucm_user_active (ucm_user_id, ucm_is_active),
  KEY idx_ucm_company_active (ucm_company_id, ucm_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_user_company_history (
  uch_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uch_user_id BIGINT UNSIGNED NOT NULL,
  uch_company_id BIGINT UNSIGNED NOT NULL,
  uch_job_title VARCHAR(150) NULL,
  uch_started_at DATE NOT NULL,
  uch_ended_at DATE NULL,
  uch_departure_reason VARCHAR(255) NULL,
  uch_notes TEXT NULL,
  uch_created_by BIGINT UNSIGNED NULL,
  uch_updated_by BIGINT UNSIGNED NULL,
  uch_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  uch_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (uch_id),
  KEY idx_uch_user_dates (uch_user_id, uch_started_at, uch_ended_at),
  KEY idx_uch_company (uch_company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_user_hierarchy (
  uhi_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uhi_user_id BIGINT UNSIGNED NOT NULL,
  uhi_manager_id BIGINT UNSIGNED NOT NULL,
  uhi_company_id BIGINT UNSIGNED NULL,
  uhi_is_primary TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  uhi_valid_from DATE NOT NULL,
  uhi_valid_until DATE NULL,
  uhi_created_by BIGINT UNSIGNED NULL,
  uhi_updated_by BIGINT UNSIGNED NULL,
  uhi_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  uhi_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (uhi_id),
  UNIQUE KEY uq_uhi_active_relation (uhi_user_id, uhi_manager_id, uhi_company_id, uhi_valid_until),
  KEY idx_uhi_user_active (uhi_user_id, uhi_valid_until),
  KEY idx_uhi_manager_active (uhi_manager_id, uhi_valid_until),
  KEY idx_uhi_company (uhi_company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sav_roles (rol_uuid, rol_code, rol_label, rol_level, rol_description, rol_can_create_roles, rol_is_system, rol_is_active)
VALUES
  (UUID(), 'SUPERADMIN', 'Administrateur global', 1, 'Acces complet a toute l application.', JSON_ARRAY('SUPERADMIN','CONSTRUCTEUR','IMPORTATEUR','CONCESSIONNAIRE','REPARATEUR','INDEPENDANT','STRUCTURE_ADMIN','MANAGER','USER'), 1, 1),
  (UUID(), 'CONSTRUCTEUR', 'Constructeur', 10, 'Compte de niveau constructeur.', JSON_ARRAY('IMPORTATEUR','CONCESSIONNAIRE','STRUCTURE_ADMIN','MANAGER','USER'), 1, 1),
  (UUID(), 'IMPORTATEUR', 'Importateur', 20, 'Compte de niveau importateur.', JSON_ARRAY('CONCESSIONNAIRE','STRUCTURE_ADMIN','MANAGER','USER'), 1, 1),
  (UUID(), 'CONCESSIONNAIRE', 'Concessionnaire', 30, 'Compte de niveau concession.', JSON_ARRAY('STRUCTURE_ADMIN','MANAGER','USER'), 1, 1),
  (UUID(), 'REPARATEUR', 'Reparateur independant', 35, 'Compte reparateur independant.', JSON_ARRAY('STRUCTURE_ADMIN','MANAGER','USER'), 1, 1),
  (UUID(), 'INDEPENDANT', 'Structure independante', 40, 'Compte structure autonome.', JSON_ARRAY('STRUCTURE_ADMIN','MANAGER','USER'), 1, 1),
  (UUID(), 'STRUCTURE_ADMIN', 'Administrateur de structure', 50, 'Admin local de structure.', JSON_ARRAY('MANAGER','USER'), 1, 1),
  (UUID(), 'MANAGER', 'Manager de service', 60, 'Manager avec visibilite sur son service.', JSON_ARRAY('USER'), 1, 1),
  (UUID(), 'USER', 'Utilisateur interne', 90, 'Utilisateur standard.', JSON_ARRAY(), 1, 1)
ON DUPLICATE KEY UPDATE
  rol_label = VALUES(rol_label),
  rol_level = VALUES(rol_level),
  rol_description = VALUES(rol_description),
  rol_can_create_roles = VALUES(rol_can_create_roles),
  rol_is_active = VALUES(rol_is_active);

INSERT INTO sav_permissions (per_code, per_label, per_module, per_description, per_is_active)
VALUES
  ('users.read', 'Consulter les utilisateurs', 'Users', 'Acces a la liste et aux fiches utilisateur.', 1),
  ('users.create', 'Creer un utilisateur', 'Users', 'Creation de profils dans le perimetre autorise.', 1),
  ('users.update', 'Modifier un utilisateur', 'Users', 'Modification des profils dans le perimetre autorise.', 1),
  ('users.hierarchy', 'Gerer la hierarchie', 'Users', 'Gestion des managers et subordonnes.', 1),
  ('users.companies', 'Gerer les rattachements', 'Users', 'Gestion des entreprises rattachees.', 1)
ON DUPLICATE KEY UPDATE
  per_label = VALUES(per_label),
  per_module = VALUES(per_module),
  per_description = VALUES(per_description),
  per_is_active = VALUES(per_is_active);

INSERT IGNORE INTO sav_role_permissions (rpe_role_id, rpe_permission_id)
SELECT r.rol_id, p.per_id
FROM sav_roles r
CROSS JOIN sav_permissions p
WHERE r.rol_code IN ('SUPERADMIN','CONSTRUCTEUR','IMPORTATEUR','CONCESSIONNAIRE','REPARATEUR','INDEPENDANT','STRUCTURE_ADMIN','MANAGER');
