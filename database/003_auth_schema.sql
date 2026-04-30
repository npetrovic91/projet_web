-- AutoSAV - LIVRABLE 3 - Schema Authentification, securite, CGU
-- Compatibilite cible : MySQL/MariaDB Hostinger, InnoDB, utf8mb4.

CREATE TABLE IF NOT EXISTS sav_login_attempts (
  lat_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  lat_ip VARCHAR(45) NOT NULL,
  lat_email VARCHAR(191) NOT NULL,
  lat_user_id BIGINT UNSIGNED NULL,
  lat_success TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  lat_failure_reason VARCHAR(120) NULL,
  lat_user_agent VARCHAR(500) NULL,
  lat_context JSON NULL,
  lat_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (lat_id),
  KEY idx_lat_ip_created (lat_ip, lat_created_at),
  KEY idx_lat_email_created (lat_email, lat_created_at),
  KEY idx_lat_success_created (lat_success, lat_created_at),
  KEY idx_lat_user (lat_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_ip_blacklist (
  ibl_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ibl_uuid CHAR(36) NOT NULL,
  ibl_ip VARCHAR(45) NOT NULL,
  ibl_reason VARCHAR(255) NOT NULL,
  ibl_type VARCHAR(20) NOT NULL DEFAULT 'auto',
  ibl_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  ibl_expires_at DATETIME NULL,
  ibl_attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
  ibl_update_count INT UNSIGNED NOT NULL DEFAULT 0,
  ibl_created_by BIGINT UNSIGNED NULL,
  ibl_unblocked_by BIGINT UNSIGNED NULL,
  ibl_unblocked_at DATETIME NULL,
  ibl_meta JSON NULL,
  ibl_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ibl_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ibl_id),
  UNIQUE KEY uq_ibl_uuid (ibl_uuid),
  KEY idx_ibl_ip_active (ibl_ip, ibl_is_active),
  KEY idx_ibl_expires (ibl_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_email_blacklist (
  ebl_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ebl_uuid CHAR(36) NOT NULL,
  ebl_email VARCHAR(191) NOT NULL,
  ebl_reason VARCHAR(255) NOT NULL,
  ebl_type VARCHAR(20) NOT NULL DEFAULT 'auto',
  ebl_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  ebl_expires_at DATETIME NULL,
  ebl_attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
  ebl_created_by BIGINT UNSIGNED NULL,
  ebl_unblocked_by BIGINT UNSIGNED NULL,
  ebl_unblocked_at DATETIME NULL,
  ebl_meta JSON NULL,
  ebl_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ebl_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ebl_id),
  UNIQUE KEY uq_ebl_uuid (ebl_uuid),
  KEY idx_ebl_email_active (ebl_email, ebl_is_active),
  KEY idx_ebl_expires (ebl_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_unblock_history (
  ubh_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ubh_type VARCHAR(20) NOT NULL,
  ubh_target VARCHAR(191) NOT NULL,
  ubh_blocked_table_id BIGINT UNSIGNED NOT NULL,
  ubh_unblocked_by BIGINT UNSIGNED NOT NULL,
  ubh_reason VARCHAR(255) NOT NULL,
  ubh_admin_ip VARCHAR(45) NOT NULL,
  ubh_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ubh_id),
  KEY idx_ubh_type_target (ubh_type, ubh_target),
  KEY idx_ubh_admin (ubh_unblocked_by),
  KEY idx_ubh_created (ubh_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_email_tokens (
  etk_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  etk_user_id BIGINT UNSIGNED NOT NULL,
  etk_token_hash CHAR(64) NOT NULL,
  etk_expires_at DATETIME NOT NULL,
  etk_used_at DATETIME NULL,
  etk_used_ip VARCHAR(45) NULL,
  etk_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (etk_id),
  UNIQUE KEY uq_etk_token_hash (etk_token_hash),
  KEY idx_etk_user (etk_user_id),
  KEY idx_etk_expires (etk_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_password_reset_tokens (
  prt_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  prt_user_id BIGINT UNSIGNED NOT NULL,
  prt_token_hash CHAR(64) NOT NULL,
  prt_expires_at DATETIME NOT NULL,
  prt_used_at DATETIME NULL,
  prt_used_ip VARCHAR(45) NULL,
  prt_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (prt_id),
  UNIQUE KEY uq_prt_token_hash (prt_token_hash),
  KEY idx_prt_user (prt_user_id),
  KEY idx_prt_expires (prt_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_password_history (
  phs_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  phs_user_id BIGINT UNSIGNED NOT NULL,
  phs_hash VARCHAR(255) NOT NULL,
  phs_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (phs_id),
  KEY idx_phs_user_created (phs_user_id, phs_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_terms_versions (
  trv_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  trv_version VARCHAR(50) NOT NULL,
  trv_title VARCHAR(191) NOT NULL,
  trv_content LONGTEXT NOT NULL,
  trv_is_current TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  trv_published_at DATETIME NULL,
  trv_created_by BIGINT UNSIGNED NULL,
  trv_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (trv_id),
  UNIQUE KEY uq_trv_version (trv_version),
  KEY idx_trv_current_published (trv_is_current, trv_published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_terms_acceptances (
  tra_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tra_user_id BIGINT UNSIGNED NOT NULL,
  tra_terms_version_id BIGINT UNSIGNED NOT NULL,
  tra_action VARCHAR(20) NOT NULL,
  tra_ip VARCHAR(45) NOT NULL,
  tra_user_agent VARCHAR(500) NULL,
  tra_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (tra_id),
  KEY idx_tra_user_created (tra_user_id, tra_created_at),
  KEY idx_tra_version (tra_terms_version_id),
  KEY idx_tra_action (tra_action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_sessions (
  ses_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ses_session_id VARCHAR(128) NOT NULL,
  ses_user_id BIGINT UNSIGNED NOT NULL,
  ses_ip VARCHAR(45) NOT NULL,
  ses_user_agent VARCHAR(500) NULL,
  ses_started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ses_last_activity DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ses_ended_at DATETIME NULL,
  ses_ended_reason VARCHAR(50) NULL,
  PRIMARY KEY (ses_id),
  KEY idx_ses_session (ses_session_id),
  KEY idx_ses_user_active (ses_user_id, ses_ended_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE sav_users
  ADD COLUMN IF NOT EXISTS use_active_company_id BIGINT UNSIGNED NULL AFTER use_manager_id,
  ADD COLUMN IF NOT EXISTS use_active_brand_id BIGINT UNSIGNED NULL AFTER use_active_company_id,
  ADD COLUMN IF NOT EXISTS use_locked_reason VARCHAR(255) NULL AFTER use_locked_until,
  ADD COLUMN IF NOT EXISTS use_terms_accepted_version VARCHAR(50) NULL AFTER use_last_user_agent,
  ADD COLUMN IF NOT EXISTS use_terms_accepted_at DATETIME NULL AFTER use_terms_accepted_version,
  ADD COLUMN IF NOT EXISTS use_gdpr_anonymized TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER use_timezone;
