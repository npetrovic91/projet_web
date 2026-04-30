-- AutoSAV - LIVRABLE 8 - Profil utilisateur, competences, qualifications, premiers elements RGPD
-- Cible : MySQL/MariaDB Hostinger, InnoDB, utf8mb4.

CREATE TABLE IF NOT EXISTS sav_skills (
  skl_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  skl_uuid CHAR(36) NOT NULL,
  skl_code VARCHAR(80) NOT NULL,
  skl_label VARCHAR(150) NOT NULL,
  skl_description VARCHAR(255) NULL,
  skl_company_id BIGINT UNSIGNED NULL,
  skl_is_global TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  skl_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  skl_created_by BIGINT UNSIGNED NULL,
  skl_updated_by BIGINT UNSIGNED NULL,
  skl_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  skl_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (skl_id),
  UNIQUE KEY uq_skl_uuid (skl_uuid),
  UNIQUE KEY uq_skl_code (skl_code),
  KEY idx_skl_company_active (skl_company_id, skl_is_active),
  KEY idx_skl_global_active (skl_is_global, skl_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_user_skills (
  usk_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usk_user_id BIGINT UNSIGNED NOT NULL,
  usk_skill_id BIGINT UNSIGNED NOT NULL,
  usk_level VARCHAR(30) NOT NULL DEFAULT 'intermediate',
  usk_validated_by BIGINT UNSIGNED NULL,
  usk_validated_at DATETIME NULL,
  usk_created_by BIGINT UNSIGNED NULL,
  usk_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usk_id),
  UNIQUE KEY uq_usk_user_skill (usk_user_id, usk_skill_id),
  KEY idx_usk_user (usk_user_id),
  KEY idx_usk_skill (usk_skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_qualifications (
  qua_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  qua_uuid CHAR(36) NOT NULL,
  qua_code VARCHAR(80) NOT NULL,
  qua_label VARCHAR(150) NOT NULL,
  qua_description VARCHAR(255) NULL,
  qua_issuer VARCHAR(150) NULL,
  qua_validity_months INT UNSIGNED NULL,
  qua_company_id BIGINT UNSIGNED NULL,
  qua_is_global TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  qua_is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  qua_created_by BIGINT UNSIGNED NULL,
  qua_updated_by BIGINT UNSIGNED NULL,
  qua_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  qua_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (qua_id),
  UNIQUE KEY uq_qua_uuid (qua_uuid),
  UNIQUE KEY uq_qua_code (qua_code),
  KEY idx_qua_company_active (qua_company_id, qua_is_active),
  KEY idx_qua_global_active (qua_is_global, qua_is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_user_qualifications (
  uqu_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uqu_user_id BIGINT UNSIGNED NOT NULL,
  uqu_qualification_id BIGINT UNSIGNED NOT NULL,
  uqu_obtained_at DATE NULL,
  uqu_expires_at DATE NULL,
  uqu_certificate_ref VARCHAR(120) NULL,
  uqu_document_url VARCHAR(255) NULL,
  uqu_validated_by BIGINT UNSIGNED NULL,
  uqu_validated_at DATETIME NULL,
  uqu_created_by BIGINT UNSIGNED NULL,
  uqu_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (uqu_id),
  UNIQUE KEY uq_uqu_user_qualification (uqu_user_id, uqu_qualification_id),
  KEY idx_uqu_user (uqu_user_id),
  KEY idx_uqu_qualification (uqu_qualification_id),
  KEY idx_uqu_expiry (uqu_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sav_gdpr_requests (
  grq_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  grq_uuid CHAR(36) NOT NULL,
  grq_user_id BIGINT UNSIGNED NOT NULL,
  grq_type VARCHAR(40) NOT NULL,
  grq_status VARCHAR(40) NOT NULL DEFAULT 'submitted',
  grq_message TEXT NULL,
  grq_response TEXT NULL,
  grq_requested_ip VARCHAR(45) NULL,
  grq_handled_by BIGINT UNSIGNED NULL,
  grq_handled_at DATETIME NULL,
  grq_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  grq_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (grq_id),
  UNIQUE KEY uq_grq_uuid (grq_uuid),
  KEY idx_grq_user_status (grq_user_id, grq_status),
  KEY idx_grq_type_created (grq_type, grq_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sav_skills (skl_uuid, skl_code, skl_label, skl_description, skl_is_global, skl_is_active)
VALUES
  (UUID(), 'RELATION_CLIENT', 'Relation client', 'Competence de relation et suivi client.', 1, 1),
  (UUID(), 'DIAGNOSTIC_TECHNIQUE', 'Diagnostic technique', 'Analyse technique et identification de panne.', 1, 1),
  (UUID(), 'GESTION_EQUIPE', 'Gestion equipe', 'Animation et coordination d une equipe.', 1, 1)
ON DUPLICATE KEY UPDATE
  skl_label = VALUES(skl_label),
  skl_description = VALUES(skl_description),
  skl_is_active = VALUES(skl_is_active);

INSERT INTO sav_qualifications (qua_uuid, qua_code, qua_label, qua_description, qua_issuer, qua_validity_months, qua_is_global, qua_is_active)
VALUES
  (UUID(), 'HABILITATION_HV', 'Habilitation haute tension', 'Qualification de securite vehicules electrifies.', 'Constructeur ou organisme habilite', 36, 1, 1),
  (UUID(), 'CERTIFICATION_DIAG', 'Certification diagnostic', 'Qualification diagnostic avance.', 'Constructeur ou centre de formation', 24, 1, 1),
  (UUID(), 'FORMATION_APV', 'Formation apres-vente', 'Parcours de formation apres-vente.', 'Importateur ou constructeur', NULL, 1, 1)
ON DUPLICATE KEY UPDATE
  qua_label = VALUES(qua_label),
  qua_description = VALUES(qua_description),
  qua_issuer = VALUES(qua_issuer),
  qua_validity_months = VALUES(qua_validity_months),
  qua_is_active = VALUES(qua_is_active);

INSERT INTO sav_permissions (per_code, per_label, per_module, per_description, per_is_active)
VALUES
  ('profile.read', 'Consulter son profil', 'Profile', 'Acces a la page profil personnelle.', 1),
  ('profile.update', 'Modifier son profil', 'Profile', 'Modification controlee des donnees personnelles.', 1),
  ('skills.read', 'Consulter les competences', 'Skills', 'Acces aux competences utilisateur.', 1),
  ('qualifications.read', 'Consulter les qualifications', 'Qualifications', 'Acces aux qualifications utilisateur.', 1)
ON DUPLICATE KEY UPDATE
  per_label = VALUES(per_label),
  per_module = VALUES(per_module),
  per_description = VALUES(per_description),
  per_is_active = VALUES(per_is_active);
