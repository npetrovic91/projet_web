-- AutoSAV - LIVRABLE 9 - RGPD complet, workflow administratif, audit et anonymisation
-- Cible : MySQL/MariaDB Hostinger, InnoDB, utf8mb4.

ALTER TABLE sav_gdpr_requests
  ADD COLUMN IF NOT EXISTS grq_due_at DATETIME NULL AFTER grq_requested_ip,
  ADD COLUMN IF NOT EXISTS grq_rejection_reason VARCHAR(255) NULL AFTER grq_response,
  ADD COLUMN IF NOT EXISTS grq_export_path VARCHAR(255) NULL AFTER grq_rejection_reason;

CREATE TABLE IF NOT EXISTS sav_gdpr_actions (
  gac_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  gac_uuid CHAR(36) NOT NULL,
  gac_request_id BIGINT UNSIGNED NULL,
  gac_user_id BIGINT UNSIGNED NULL,
  gac_action VARCHAR(60) NOT NULL,
  gac_status VARCHAR(40) NOT NULL DEFAULT 'done',
  gac_details JSON NULL,
  gac_performed_by BIGINT UNSIGNED NOT NULL,
  gac_performed_ip VARCHAR(45) NULL,
  gac_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (gac_id),
  UNIQUE KEY uq_gac_uuid (gac_uuid),
  KEY idx_gac_request (gac_request_id),
  KEY idx_gac_user_action (gac_user_id, gac_action),
  KEY idx_gac_performed (gac_performed_by, gac_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_gdpr_retention_rules (
  grr_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  grr_code VARCHAR(80) NOT NULL,
  grr_label VARCHAR(150) NOT NULL,
  grr_table_name VARCHAR(120) NOT NULL,
  grr_date_column VARCHAR(120) NOT NULL,
  grr_retention_months INT UNSIGNED NOT NULL,
  grr_action VARCHAR(30) NOT NULL DEFAULT 'anonymize',
  grr_legal_basis VARCHAR(150) NULL,
  grr_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  grr_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  grr_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (grr_id),
  UNIQUE KEY uq_grr_code (grr_code),
  KEY idx_grr_table_active (grr_table_name, grr_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_gdpr_exports (
  gex_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  gex_uuid CHAR(36) NOT NULL,
  gex_user_id BIGINT UNSIGNED NOT NULL,
  gex_request_id BIGINT UNSIGNED NULL,
  gex_format VARCHAR(20) NOT NULL DEFAULT 'json',
  gex_file_name VARCHAR(180) NULL,
  gex_generated_by BIGINT UNSIGNED NOT NULL,
  gex_generated_ip VARCHAR(45) NULL,
  gex_expires_at DATETIME NULL,
  gex_downloaded_at DATETIME NULL,
  gex_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (gex_id),
  UNIQUE KEY uq_gex_uuid (gex_uuid),
  KEY idx_gex_user_created (gex_user_id, gex_created_at),
  KEY idx_gex_request (gex_request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sav_gdpr_retention_rules
  (grr_code, grr_label, grr_table_name, grr_date_column, grr_retention_months, grr_action, grr_legal_basis, grr_is_active)
VALUES
  ('LOGIN_ATTEMPTS', 'Tentatives de connexion', 'sav_login_attempts', 'lat_created_at', 12, 'delete', 'Securite applicative', 1),
  ('GDPR_REQUESTS', 'Demandes RGPD', 'sav_gdpr_requests', 'grq_created_at', 60, 'archive', 'Obligation de preuve', 1),
  ('AUDIT_ACTIONS', 'Actions RGPD', 'sav_gdpr_actions', 'gac_created_at', 60, 'archive', 'Obligation de preuve', 1)
ON DUPLICATE KEY UPDATE
  grr_label = VALUES(grr_label),
  grr_retention_months = VALUES(grr_retention_months),
  grr_action = VALUES(grr_action),
  grr_legal_basis = VALUES(grr_legal_basis),
  grr_is_active = VALUES(grr_is_active);

INSERT INTO sav_permissions (per_code, per_label, per_module, per_description, per_is_active)
VALUES
  ('gdpr.read', 'Consulter les demandes RGPD', 'GDPR', 'Acces au tableau de bord RGPD.', 1),
  ('gdpr.process', 'Traiter les demandes RGPD', 'GDPR', 'Validation, rejet, export et anonymisation encadree.', 1),
  ('gdpr.export', 'Exporter les donnees RGPD', 'GDPR', 'Generation d exports utilisateur.', 1),
  ('gdpr.anonymize', 'Anonymiser un utilisateur', 'GDPR', 'Anonymisation encadree selon les obligations applicables.', 1)
ON DUPLICATE KEY UPDATE
  per_label = VALUES(per_label),
  per_module = VALUES(per_module),
  per_description = VALUES(per_description),
  per_is_active = VALUES(per_is_active);
