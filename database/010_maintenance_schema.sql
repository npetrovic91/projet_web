-- AutoSAV - LIVRABLE 10 - Maintenance applicative et supervision minimale
-- Cible : MySQL/MariaDB Hostinger, InnoDB, utf8mb4.

CREATE TABLE IF NOT EXISTS sav_maintenance (
  mtn_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  mtn_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  mtn_message VARCHAR(500) NOT NULL DEFAULT 'Application temporairement indisponible pour maintenance.',
  mtn_allowed_roles JSON NULL,
  mtn_allowed_ips JSON NULL,
  mtn_started_at DATETIME NULL,
  mtn_ended_at DATETIME NULL,
  mtn_updated_by BIGINT UNSIGNED NULL,
  mtn_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  mtn_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (mtn_id),
  KEY idx_mtn_active (mtn_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_maintenance_events (
  mev_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  mev_uuid CHAR(36) NOT NULL,
  mev_event_type VARCHAR(60) NOT NULL,
  mev_severity VARCHAR(20) NOT NULL DEFAULT 'info',
  mev_message VARCHAR(500) NOT NULL,
  mev_context JSON NULL,
  mev_created_by BIGINT UNSIGNED NULL,
  mev_created_ip VARCHAR(45) NULL,
  mev_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (mev_id),
  UNIQUE KEY uq_mev_uuid (mev_uuid),
  KEY idx_mev_type_created (mev_event_type, mev_created_at),
  KEY idx_mev_severity_created (mev_severity, mev_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sav_maintenance
  (mtn_id, mtn_is_active, mtn_message, mtn_allowed_roles, mtn_allowed_ips, mtn_created_at, mtn_updated_at)
VALUES
  (1, 0, 'Application temporairement indisponible pour maintenance.', JSON_ARRAY('SUPERADMIN'), JSON_ARRAY('127.0.0.1','::1'), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  mtn_id = mtn_id;

INSERT INTO sav_permissions (per_code, per_label, per_module, per_description, per_is_active)
VALUES
  ('maintenance.read', 'Consulter la maintenance', 'Maintenance', 'Acces au tableau de bord maintenance.', 1),
  ('maintenance.manage', 'Gerer la maintenance', 'Maintenance', 'Activation, desactivation et parametrage du mode maintenance.', 1)
ON DUPLICATE KEY UPDATE
  per_label = VALUES(per_label),
  per_module = VALUES(per_module),
  per_description = VALUES(per_description),
  per_is_active = VALUES(per_is_active);
