-- ============================================================
-- AUTOSAV — Migration 0002 : type de relation importateur->concession
-- ------------------------------------------------------------
-- Cahier des charges : "Chaque concession doit avoir un
-- importateur_id obligatoire." Le schéma sav_societes ne porte pas
-- cette relation comme colonne directe (seuls
-- soc_societe_parente_id/soc_holding_id existent, pour le
-- rattachement groupe->concession). On modélise donc le lien
-- importateur->concession comme une sav_relations_societes, sur le
-- même principe que la relation existante 'groupe_pilote_concession'.
-- ============================================================

INSERT IGNORE INTO sav_types_relations_societes
    (tre_code, tre_nom, tre_description, tre_est_directionnel, tre_statut_id, tre_cree_le)
VALUES
    ('importateur_distribue_concession', 'Importateur distribue concession',
     'Relation obligatoire entre un importateur et une concession qu''il distribue.',
     1, 1, NOW());
