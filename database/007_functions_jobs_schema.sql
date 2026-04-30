-- AutoSAV - LIVRABLE 7 - Fonctions, metiers, portees structurelles
-- Cible : MySQL/MariaDB Hostinger, InnoDB, utf8mb4.

CREATE TABLE IF NOT EXISTS sav_functions (
  fnc_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  fnc_uuid CHAR(36) NOT NULL,
  fnc_code VARCHAR(80) NOT NULL,
  fnc_label VARCHAR(150) NOT NULL,
  fnc_description VARCHAR(255) NULL,
  fnc_company_id BIGINT UNSIGNED NULL,
  fnc_is_global TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  fnc_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  fnc_created_by BIGINT UNSIGNED NULL,
  fnc_updated_by BIGINT UNSIGNED NULL,
  fnc_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fnc_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (fnc_id),
  UNIQUE KEY uq_fnc_uuid (fnc_uuid),
  UNIQUE KEY uq_fnc_code (fnc_code),
  KEY idx_fnc_company_active (fnc_company_id, fnc_is_active),
  KEY idx_fnc_global_active (fnc_is_global, fnc_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_user_functions (
  ufn_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ufn_user_id BIGINT UNSIGNED NOT NULL,
  ufn_function_id BIGINT UNSIGNED NOT NULL,
  ufn_company_id BIGINT UNSIGNED NULL,
  ufn_is_primary TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  ufn_created_by BIGINT UNSIGNED NULL,
  ufn_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ufn_id),
  UNIQUE KEY uq_ufn_user_function_company (ufn_user_id, ufn_function_id, ufn_company_id),
  KEY idx_ufn_user_primary (ufn_user_id, ufn_is_primary),
  KEY idx_ufn_function (ufn_function_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_jobs (
  job_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_uuid CHAR(36) NOT NULL,
  job_code VARCHAR(80) NOT NULL,
  job_label VARCHAR(150) NOT NULL,
  job_description VARCHAR(255) NULL,
  job_company_id BIGINT UNSIGNED NULL,
  job_is_global TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  job_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  job_created_by BIGINT UNSIGNED NULL,
  job_updated_by BIGINT UNSIGNED NULL,
  job_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  job_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (job_id),
  UNIQUE KEY uq_job_uuid (job_uuid),
  UNIQUE KEY uq_job_code (job_code),
  KEY idx_job_company_active (job_company_id, job_is_active),
  KEY idx_job_global_active (job_is_global, job_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_job_company_types (
  jct_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  jct_job_id BIGINT UNSIGNED NOT NULL,
  jct_company_type_id BIGINT UNSIGNED NOT NULL,
  jct_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  jct_created_by BIGINT UNSIGNED NULL,
  jct_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (jct_id),
  UNIQUE KEY uq_jct_job_type (jct_job_id, jct_company_type_id),
  KEY idx_jct_type_active (jct_company_type_id, jct_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_user_jobs (
  ujb_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ujb_user_id BIGINT UNSIGNED NOT NULL,
  ujb_job_id BIGINT UNSIGNED NOT NULL,
  ujb_company_id BIGINT UNSIGNED NULL,
  ujb_company_type_id BIGINT UNSIGNED NULL,
  ujb_is_primary TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  ujb_created_by BIGINT UNSIGNED NULL,
  ujb_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ujb_id),
  UNIQUE KEY uq_ujb_user_job_context (ujb_user_id, ujb_job_id, ujb_company_id),
  KEY idx_ujb_user_primary (ujb_user_id, ujb_is_primary),
  KEY idx_ujb_job (ujb_job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sav_functions (fnc_uuid, fnc_code, fnc_label, fnc_description, fnc_is_global, fnc_is_active)
VALUES
  (UUID(), 'DIRECTION', 'Direction', 'Fonction de direction ou pilotage.', 1, 1),
  (UUID(), 'SERVICE_CLIENT', 'Service client', 'Relation client et suivi apres-vente.', 1, 1),
  (UUID(), 'ADMINISTRATION', 'Administration', 'Gestion administrative interne.', 1, 1),
  (UUID(), 'TECHNIQUE', 'Technique', 'Fonction technique operationnelle.', 1, 1)
ON DUPLICATE KEY UPDATE
  fnc_label = VALUES(fnc_label),
  fnc_description = VALUES(fnc_description),
  fnc_is_active = VALUES(fnc_is_active);

INSERT INTO sav_jobs (job_uuid, job_code, job_label, job_description, job_is_global, job_is_active)
VALUES
  (UUID(), 'DIRECTEUR_SITE', 'Directeur de site', 'Pilotage global d une structure.', 1, 1),
  (UUID(), 'RESPONSABLE_APV', 'Responsable apres-vente', 'Responsabilite service apres-vente.', 1, 1),
  (UUID(), 'CONSEILLER_SERVICE', 'Conseiller service', 'Accueil client et suivi atelier.', 1, 1),
  (UUID(), 'TECHNICIEN', 'Technicien', 'Intervention technique atelier.', 1, 1)
ON DUPLICATE KEY UPDATE
  job_label = VALUES(job_label),
  job_description = VALUES(job_description),
  job_is_active = VALUES(job_is_active);

INSERT IGNORE INTO sav_job_company_types (jct_job_id, jct_company_type_id)
SELECT j.job_id, cty.cty_id
FROM sav_jobs j
CROSS JOIN sav_company_types cty
WHERE j.job_code IN ('DIRECTEUR_SITE','RESPONSABLE_APV','CONSEILLER_SERVICE','TECHNICIEN');

INSERT INTO sav_permissions (per_code, per_label, per_module, per_description, per_is_active)
VALUES
  ('functions.read', 'Consulter les fonctions', 'Functions', 'Acces aux fonctions disponibles dans le contexte.', 1),
  ('functions.manage', 'Gerer les fonctions', 'Functions', 'Creation et modification des fonctions globales ou structurelles.', 1),
  ('jobs.read', 'Consulter les metiers', 'Jobs', 'Acces aux metiers disponibles dans le contexte.', 1),
  ('jobs.manage', 'Gerer les metiers', 'Jobs', 'Creation et modification des metiers communs ou specifiques.', 1)
ON DUPLICATE KEY UPDATE
  per_label = VALUES(per_label),
  per_module = VALUES(per_module),
  per_description = VALUES(per_description),
  per_is_active = VALUES(per_is_active);
