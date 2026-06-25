-- ============================================================
-- AUTOSAV — Migration 0003 : journal d'accès support/audit du super_admin
-- ------------------------------------------------------------
-- Contrainte non négociable du cahier des charges : "Le super_admin
-- ne doit accéder aux données métier qu'en mode support/audit/
-- sécurité/incident/maintenance avec justification, et tout accès
-- doit enregistrer utilisateur, justification, date/heure,
-- organisation, périmètre, données, durée et actions." (ACC-007)
--
-- Cette table porte l'intégralité de ces champs. Aucun code
-- applicatif n'implémentait ce flux avant cette migration (audit du
-- 2026-06-25) : Modules/SuperAdmin se limitait à un tableau de bord
-- en lecture, sans aucune justification exigée pour consulter les
-- données d'une société cliente.
-- ============================================================

CREATE TABLE IF NOT EXISTS sav_acces_donnees_superadmin (
    asd_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    asd_utilisateur_id BIGINT UNSIGNED NOT NULL,
    asd_societe_id BIGINT UNSIGNED NOT NULL,
    asd_motif ENUM('support','audit','securite','incident','maintenance') NOT NULL,
    asd_justification TEXT NOT NULL,
    asd_perimetre VARCHAR(255) DEFAULT NULL COMMENT 'Page/route initiale ayant declenche la justification',
    asd_actions_json LONGTEXT DEFAULT NULL COMMENT 'Journal JSON des actions effectuees pendant cet acces justifie',
    asd_adresse_ip VARBINARY(16) DEFAULT NULL,
    asd_user_agent VARCHAR(255) DEFAULT NULL,
    asd_debute_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    asd_derniere_activite_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    asd_termine_le DATETIME DEFAULT NULL COMMENT 'Cloture explicite (deconnexion ou changement de societe consultee)',
    asd_cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (asd_id),
    KEY idx_asd_utilisateur (asd_utilisateur_id),
    KEY idx_asd_societe (asd_societe_id),
    KEY idx_asd_termine (asd_termine_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Journal d''acces justifie du super_admin aux donnees metier (ACC-007)';
