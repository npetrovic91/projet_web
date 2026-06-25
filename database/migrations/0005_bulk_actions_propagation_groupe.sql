-- ============================================================
-- AUTOSAV — Migration 0005 : propagation groupe -> concessions (ACC-009)
-- ------------------------------------------------------------
-- Cahier des charges (workflows.propagation_groupe) : "Une propagation
-- massive de groupe vers ses concessions nécessite prévisualisation,
-- confirmation, journalisation et rollback possible." La table
-- sav_bulk_actions évoquée dans le cahier des charges n'existait nulle
-- part dans le schéma réel (vérifié sur le dump de production du
-- 2026-06-25) : la fonctionnalité n'avait jamais été implémentée.
-- ============================================================

CREATE TABLE IF NOT EXISTS sav_bulk_actions (
    bac_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    bac_groupe_societe_id BIGINT UNSIGNED NOT NULL COMMENT 'Groupe de concessions initiateur',
    bac_type_cible ENUM('role','fonction','competence','certification') NOT NULL,
    bac_cible_id BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant de l''element catalogue (role/fonction/competence/certification) detenu par le groupe',
    bac_concession_ids JSON NOT NULL COMMENT 'Concessions cibles selectionnees pour cette propagation',
    bac_statut ENUM('preview','validee','executee','rollback','erreur') NOT NULL DEFAULT 'preview',
    bac_preview_json LONGTEXT DEFAULT NULL COMMENT 'Resultat du calcul de previsualisation : conflits detectes, creations prevues',
    bac_resultat_json LONGTEXT DEFAULT NULL COMMENT 'Resultat de l''execution : IDs crees par concession, erreurs',
    bac_cree_par_utilisateur_id BIGINT UNSIGNED DEFAULT NULL,
    bac_valide_par_utilisateur_id BIGINT UNSIGNED DEFAULT NULL,
    bac_valide_le DATETIME DEFAULT NULL,
    bac_execute_le DATETIME DEFAULT NULL,
    bac_rollback_le DATETIME DEFAULT NULL,
    bac_rollback_par_utilisateur_id BIGINT UNSIGNED DEFAULT NULL,
    bac_cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (bac_id),
    KEY idx_bac_groupe (bac_groupe_societe_id),
    KEY idx_bac_statut (bac_statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Propagation massive groupe->concessions : preview/confirmation/journalisation/rollback (ACC-009)';
