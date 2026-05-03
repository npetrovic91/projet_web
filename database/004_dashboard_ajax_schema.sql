-- AutoSAV - LIVRABLE 4 - Dashboard modulaire et AJAX post-authentification

CREATE TABLE IF NOT EXISTS sav_dashboard_widgets (
  dwi_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dwi_code VARCHAR(100) NOT NULL,
  dwi_label VARCHAR(160) NOT NULL,
  dwi_view_file VARCHAR(160) NOT NULL,
  dwi_ajax_endpoint VARCHAR(255) NULL,
  dwi_roles_json JSON NOT NULL,
  dwi_default_order INT UNSIGNED NOT NULL DEFAULT 100,
  dwi_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  dwi_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dwi_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (dwi_id),
  UNIQUE KEY uq_dwi_code (dwi_code),
  KEY idx_dwi_active_order (dwi_is_active, dwi_default_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_user_dashboard_widgets (
  udw_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  udw_user_id BIGINT UNSIGNED NOT NULL,
  udw_widget_id BIGINT UNSIGNED NOT NULL,
  udw_is_visible TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  udw_sort_order INT UNSIGNED NOT NULL DEFAULT 100,
  udw_config_json JSON NULL,
  udw_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  udw_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (udw_id),
  UNIQUE KEY uq_udw_user_widget (udw_user_id, udw_widget_id),
  KEY idx_udw_user (udw_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_notifications (
  ntf_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ntf_uuid CHAR(36) NULL,
  ntf_user_id BIGINT UNSIGNED NULL,
  ntf_contact_id BIGINT UNSIGNED NULL,
  ntf_event_trigger_id BIGINT UNSIGNED NULL,
  ntf_channel VARCHAR(30) NOT NULL DEFAULT 'app',
  ntf_title VARCHAR(191) NOT NULL,
  ntf_message TEXT NULL,
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
  KEY idx_ntf_expires (ntf_expires_at),
  KEY idx_ntf_contact (ntf_contact_id),
  KEY idx_ntf_status_created (ntf_status, ntf_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_company_brands (
  cbr_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cbr_company_id BIGINT UNSIGNED NOT NULL,
  cbr_brand_id BIGINT UNSIGNED NOT NULL,
  cbr_is_primary TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  cbr_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cbr_id),
  UNIQUE KEY uq_cbr_company_brand (cbr_company_id, cbr_brand_id),
  KEY idx_cbr_company (cbr_company_id),
  KEY idx_cbr_brand (cbr_brand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sav_dashboard_widgets
  (dwi_code, dwi_label, dwi_view_file, dwi_ajax_endpoint, dwi_roles_json, dwi_default_order, dwi_is_active)
VALUES
  ('quick_links', 'Acces rapide', 'widget_user.php', NULL, '["SUPERADMIN","CONSTRUCTEUR","IMPORTATEUR","CONCESSIONNAIRE","REPARATEUR","MANAGER","USER"]', 10, 1),
  ('manager_scope', 'Vue manager', 'widget_manager.php', '/ajax/dashboard/widget/manager_scope', '["SUPERADMIN","MANAGER"]', 20, 1),
  ('notifications', 'Notifications', 'widget_notifications.php', '/ajax/dashboard/widget/notifications', '["SUPERADMIN","CONSTRUCTEUR","IMPORTATEUR","CONCESSIONNAIRE","REPARATEUR","MANAGER","USER"]', 30, 1),
  ('security', 'Securite', 'widget_securite.php', '/ajax/dashboard/widget/security', '["SUPERADMIN"]', 40, 1)
ON DUPLICATE KEY UPDATE
  dwi_label = VALUES(dwi_label),
  dwi_view_file = VALUES(dwi_view_file),
  dwi_ajax_endpoint = VALUES(dwi_ajax_endpoint),
  dwi_roles_json = VALUES(dwi_roles_json),
  dwi_default_order = VALUES(dwi_default_order),
  dwi_is_active = VALUES(dwi_is_active);
