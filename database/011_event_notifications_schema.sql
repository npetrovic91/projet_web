-- AutoSAV - LIVRABLE 11 - Contacts internes/externes et notifications sur evenements declencheurs
-- Cible : MySQL/MariaDB Hostinger, InnoDB, utf8mb4.

CREATE TABLE IF NOT EXISTS sav_event_triggers (
  evt_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  evt_uuid CHAR(36) NOT NULL,
  evt_code VARCHAR(100) NOT NULL,
  evt_label VARCHAR(150) NOT NULL,
  evt_description VARCHAR(255) NULL,
  evt_module VARCHAR(80) NOT NULL,
  evt_payload_schema JSON NULL,
  evt_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  evt_created_by BIGINT UNSIGNED NULL,
  evt_updated_by BIGINT UNSIGNED NULL,
  evt_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  evt_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (evt_id),
  UNIQUE KEY uq_evt_uuid (evt_uuid),
  UNIQUE KEY uq_evt_code (evt_code),
  KEY idx_evt_module_active (evt_module, evt_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_notification_contacts (
  nco_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nco_uuid CHAR(36) NOT NULL,
  nco_company_id BIGINT UNSIGNED NOT NULL,
  nco_user_id BIGINT UNSIGNED NULL,
  nco_contact_type VARCHAR(20) NOT NULL DEFAULT 'external',
  nco_firstname VARCHAR(120) NULL,
  nco_lastname VARCHAR(120) NULL,
  nco_email VARCHAR(191) NOT NULL,
  nco_phone VARCHAR(30) NULL,
  nco_company_name VARCHAR(191) NULL,
  nco_role_label VARCHAR(150) NULL,
  nco_preferred_channel VARCHAR(30) NOT NULL DEFAULT 'email',
  nco_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  nco_created_by BIGINT UNSIGNED NULL,
  nco_updated_by BIGINT UNSIGNED NULL,
  nco_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nco_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (nco_id),
  UNIQUE KEY uq_nco_uuid (nco_uuid),
  KEY idx_nco_company_active (nco_company_id, nco_is_active),
  KEY idx_nco_user (nco_user_id),
  KEY idx_nco_email (nco_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_notification_rules (
  nru_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nru_uuid CHAR(36) NOT NULL,
  nru_company_id BIGINT UNSIGNED NOT NULL,
  nru_event_trigger_id BIGINT UNSIGNED NOT NULL,
  nru_contact_id BIGINT UNSIGNED NOT NULL,
  nru_channels JSON NOT NULL,
  nru_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  nru_created_by BIGINT UNSIGNED NULL,
  nru_updated_by BIGINT UNSIGNED NULL,
  nru_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nru_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (nru_id),
  UNIQUE KEY uq_nru_company_event_contact (nru_company_id, nru_event_trigger_id, nru_contact_id),
  UNIQUE KEY uq_nru_uuid (nru_uuid),
  KEY idx_nru_company_active (nru_company_id, nru_is_active),
  KEY idx_nru_event (nru_event_trigger_id),
  KEY idx_nru_contact (nru_contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_notifications (
  ntf_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ntf_uuid CHAR(36) NOT NULL,
  ntf_user_id BIGINT UNSIGNED NULL,
  ntf_contact_id BIGINT UNSIGNED NULL,
  ntf_event_trigger_id BIGINT UNSIGNED NULL,
  ntf_channel VARCHAR(30) NOT NULL DEFAULT 'app',
  ntf_title VARCHAR(180) NOT NULL,
  ntf_message TEXT NOT NULL,
  ntf_body TEXT NULL,
  ntf_type VARCHAR(50) NOT NULL DEFAULT 'info',
  ntf_link VARCHAR(255) NULL,
  ntf_payload JSON NULL,
  ntf_is_read TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  ntf_read_at DATETIME NULL,
  ntf_status VARCHAR(40) NOT NULL DEFAULT 'queued',
  ntf_sent_at DATETIME NULL,
  ntf_expires_at DATETIME NULL,
  ntf_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ntf_id),
  UNIQUE KEY uq_ntf_uuid (ntf_uuid),
  KEY idx_ntf_user_read (ntf_user_id, ntf_is_read),
  KEY idx_ntf_contact (ntf_contact_id),
  KEY idx_ntf_status_created (ntf_status, ntf_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_notification_audit (
  nau_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nau_uuid CHAR(36) NOT NULL,
  nau_rule_id BIGINT UNSIGNED NULL,
  nau_notification_id BIGINT UNSIGNED NULL,
  nau_action VARCHAR(60) NOT NULL,
  nau_details JSON NULL,
  nau_created_by BIGINT UNSIGNED NULL,
  nau_created_ip VARCHAR(45) NULL,
  nau_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (nau_id),
  UNIQUE KEY uq_nau_uuid (nau_uuid),
  KEY idx_nau_rule (nau_rule_id),
  KEY idx_nau_notification (nau_notification_id),
  KEY idx_nau_action_created (nau_action, nau_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sav_event_triggers (evt_uuid, evt_code, evt_label, evt_description, evt_module, evt_is_active)
VALUES
  (UUID(), 'USER_CREATED', 'Utilisateur cree', 'Declenche lors de la creation d un utilisateur.', 'Users', 1),
  (UUID(), 'GDPR_REQUEST_SUBMITTED', 'Demande RGPD deposee', 'Declenche lors du depot d une demande RGPD.', 'GDPR', 1),
  (UUID(), 'SECURITY_BLOCK_CREATED', 'Blocage securite cree', 'Declenche lors d un blocage IP ou email.', 'SecurityMonitoring', 1),
  (UUID(), 'MAINTENANCE_ENABLED', 'Maintenance activee', 'Declenche lors de l activation du mode maintenance.', 'Maintenance', 1)
ON DUPLICATE KEY UPDATE
  evt_label = VALUES(evt_label),
  evt_description = VALUES(evt_description),
  evt_module = VALUES(evt_module),
  evt_is_active = VALUES(evt_is_active);

INSERT INTO sav_permissions (per_code, per_label, per_module, per_description, per_is_active)
VALUES
  ('notifications.read', 'Consulter les notifications', 'Notifications', 'Acces aux notifications et contacts.', 1),
  ('notifications.manage', 'Gerer les regles de notification', 'Notifications', 'Creation et modification contacts/regles/evenements.', 1)
ON DUPLICATE KEY UPDATE
  per_label = VALUES(per_label),
  per_module = VALUES(per_module),
  per_description = VALUES(per_description),
  per_is_active = VALUES(per_is_active);
