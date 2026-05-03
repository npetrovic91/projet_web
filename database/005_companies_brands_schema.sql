-- AutoSAV - LIVRABLE 5 - Entreprises, structures, marques

CREATE TABLE IF NOT EXISTS sav_company_types (
  cty_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cty_code VARCHAR(50) NOT NULL,
  cty_label VARCHAR(120) NOT NULL,
  cty_description VARCHAR(255) NULL,
  cty_sort_order INT UNSIGNED NOT NULL DEFAULT 100,
  cty_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  cty_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cty_id),
  UNIQUE KEY uq_cty_code (cty_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_companies (
  com_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  com_uuid CHAR(36) NOT NULL,
  com_type_id BIGINT UNSIGNED NOT NULL,
  com_holding_id BIGINT UNSIGNED NULL,
  com_name VARCHAR(191) NOT NULL,
  com_legal_name VARCHAR(191) NULL,
  com_siret VARCHAR(30) NULL,
  com_address VARCHAR(255) NULL,
  com_zipcode VARCHAR(20) NULL,
  com_city VARCHAR(120) NULL,
  com_country VARCHAR(80) NOT NULL DEFAULT 'France',
  com_phone VARCHAR(30) NULL,
  com_email VARCHAR(191) NULL,
  com_logo_url VARCHAR(255) NULL,
  com_status VARCHAR(30) NOT NULL DEFAULT 'active',
  com_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  com_created_by BIGINT UNSIGNED NULL,
  com_updated_by BIGINT UNSIGNED NULL,
  com_deleted_by BIGINT UNSIGNED NULL,
  com_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  com_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  com_deleted_at DATETIME NULL,
  PRIMARY KEY (com_id),
  UNIQUE KEY uq_com_uuid (com_uuid),
  KEY idx_com_type (com_type_id),
  KEY idx_com_holding (com_holding_id),
  KEY idx_com_name (com_name),
  KEY idx_com_status (com_status, com_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_company_relations (
  cor_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cor_parent_company_id BIGINT UNSIGNED NOT NULL,
  cor_child_company_id BIGINT UNSIGNED NOT NULL,
  cor_relation_type VARCHAR(50) NOT NULL,
  cor_started_at DATE NULL,
  cor_ended_at DATE NULL,
  cor_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  cor_created_by BIGINT UNSIGNED NULL,
  cor_updated_by BIGINT UNSIGNED NULL,
  cor_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cor_updated_at DATETIME NULL,
  PRIMARY KEY (cor_id),
  UNIQUE KEY uq_cor_active_relation (cor_parent_company_id, cor_child_company_id, cor_relation_type, cor_is_active),
  KEY idx_cor_parent (cor_parent_company_id),
  KEY idx_cor_child (cor_child_company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_brands (
  brd_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  brd_uuid CHAR(36) NOT NULL,
  brd_code VARCHAR(80) NOT NULL,
  brd_name VARCHAR(120) NOT NULL,
  brd_logo_url VARCHAR(255) NULL,
  brd_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  brd_created_by BIGINT UNSIGNED NULL,
  brd_updated_by BIGINT UNSIGNED NULL,
  brd_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  brd_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  brd_deleted_at DATETIME NULL,
  PRIMARY KEY (brd_id),
  UNIQUE KEY uq_brd_uuid (brd_uuid),
  UNIQUE KEY uq_brd_code (brd_code),
  KEY idx_brd_name (brd_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_company_brands (
  cbr_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cbr_company_id BIGINT UNSIGNED NOT NULL,
  cbr_brand_id BIGINT UNSIGNED NOT NULL,
  cbr_is_primary TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  cbr_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  cbr_created_by BIGINT UNSIGNED NULL,
  cbr_updated_by BIGINT UNSIGNED NULL,
  cbr_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cbr_updated_at DATETIME NULL,
  PRIMARY KEY (cbr_id),
  UNIQUE KEY uq_cbr_company_brand (cbr_company_id, cbr_brand_id),
  KEY idx_cbr_company (cbr_company_id),
  KEY idx_cbr_brand (cbr_brand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sav_company_types (cty_code, cty_label, cty_description, cty_sort_order, cty_is_active)
VALUES
  ('CONSTRUCTEUR', 'Constructeur', 'Constructeur automobile ou marque mere.', 10, 1),
  ('IMPORTATEUR', 'Importateur', 'Structure nationale ou regionale importatrice.', 20, 1),
  ('CONCESSIONNAIRE', 'Concessionnaire', 'Concession ou distributeur officiel.', 30, 1),
  ('REPARATEUR', 'Reparateur', 'Reparateur agree ou independant.', 40, 1),
  ('INDEPENDANT', 'Independant', 'Structure autonome non rattachee a un reseau.', 50, 1),
  ('HOLDING', 'Holding', 'Societe de regroupement capitalistique.', 60, 1)
ON DUPLICATE KEY UPDATE
  cty_label = VALUES(cty_label),
  cty_description = VALUES(cty_description),
  cty_sort_order = VALUES(cty_sort_order),
  cty_is_active = VALUES(cty_is_active);

ALTER TABLE sav_company_brands
  ADD COLUMN IF NOT EXISTS cbr_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER cbr_is_primary,
  ADD COLUMN IF NOT EXISTS cbr_created_by BIGINT UNSIGNED NULL AFTER cbr_is_active,
  ADD COLUMN IF NOT EXISTS cbr_updated_by BIGINT UNSIGNED NULL AFTER cbr_created_by,
  ADD COLUMN IF NOT EXISTS cbr_updated_at DATETIME NULL AFTER cbr_created_at;
