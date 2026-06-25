-- ============================================================
-- AUTOSAV — Migration 0004 : workflow de validation des standards
-- ------------------------------------------------------------
-- Cahier des charges (workflows.creation_standard_par_chef_departement,
-- ACC-008) : "Un chef_departement ne peut pas publier ou valider
-- directement ses propres standards. Tout standard créé par
-- chef_departement doit être validé par un niveau supérieur avant
-- d'être actif." Etats attendus : draft -> pending_validation ->
-- approved | rejected -> archived.
--
-- Avant cette migration, sav_versions_standards ne portait qu'un
-- statut générique (vst_statut_id, domaine 'general' : actif/inactif),
-- sans aucune notion de brouillon ni de validation par un tiers — une
-- version créée était immédiatement considérée comme applicable.
--
-- Les colonnes sont ajoutées séparément du statut générique existant
-- pour ne pas perturber les autres usages de vst_statut_id.
-- ============================================================

ALTER TABLE sav_versions_standards
    ADD COLUMN IF NOT EXISTS vst_etat_validation ENUM('draft','pending_validation','approved','rejected','archived') NOT NULL DEFAULT 'draft' AFTER vst_statut_id,
    ADD COLUMN IF NOT EXISTS vst_soumis_le DATETIME DEFAULT NULL AFTER vst_etat_validation,
    ADD COLUMN IF NOT EXISTS vst_valide_par_utilisateur_id BIGINT UNSIGNED DEFAULT NULL AFTER vst_soumis_le,
    ADD COLUMN IF NOT EXISTS vst_valide_le DATETIME DEFAULT NULL AFTER vst_valide_par_utilisateur_id,
    ADD COLUMN IF NOT EXISTS vst_rejete_le DATETIME DEFAULT NULL AFTER vst_valide_le,
    ADD COLUMN IF NOT EXISTS vst_motif_rejet TEXT DEFAULT NULL AFTER vst_rejete_le;

-- Les versions déjà existantes en base (créées avant ce workflow) sont
-- considérées comme déjà approuvées : elles étaient affichées comme
-- actives/applicables jusqu'ici, on ne retire rétroactivement l'accès à
-- personne. Seules les NOUVELLES versions passeront désormais par le
-- circuit draft -> pending_validation -> approved.
UPDATE sav_versions_standards
SET vst_etat_validation = 'approved', vst_valide_le = vst_cree_le
WHERE vst_etat_validation = 'draft' AND vst_supprime_le IS NULL;
