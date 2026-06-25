-- ============================================================
-- AUTOSAV — Migration 2026_06_03_lot37_admin_societe_permissions_referentiels
-- ------------------------------------------------------------
-- RECONSTITUTION : fichier référencé par
-- tests/Unit/CompanyAdminScopeTest.php et
-- tests/Unit/ProjectRoleAccessTest.php mais absent de l'archive
-- public_html (6).zip (cf. ROADMAP_PRODUCTION.md §1.1). Reconstitué
-- à partir des permissions réellement présentes en base
-- (sav_permissions per_id 47/48/49 « fonction.gerer »,
-- « competence.gerer », « certification.gerer », créées le
-- 2026-06-03 dans le dump de production), pour restaurer la trace
-- versionnée et faire repasser les tests.
--
-- Catalogue des permissions de gestion de référentiels métier
-- (fonctions, compétences, certifications) attribuées au rôle
-- administrateur_general_societe, dans le périmètre de sa société.
--
-- Idempotent : INSERT IGNORE / WHERE NOT EXISTS partout.
-- ============================================================

INSERT IGNORE INTO sav_permissions (per_code, per_description, per_statut_id, per_cree_le)
VALUES
    ('fonction.gerer', 'Gerer les fonctions metier de sa societe', 1, NOW()),
    ('competence.gerer', 'Gerer les competences propres a sa societe', 1, NOW()),
    ('certification.gerer', 'Gerer les certifications propres a sa societe', 1, NOW());

-- Rattache administrateur_general_societe à ces permissions de référentiels
-- (en plus des permissions transverses déjà posées par la migration lot35).
INSERT INTO sav_roles_permissions (rpe_role_id, rpe_permission_id, rpe_effet, rpe_cree_le)
SELECT r.rol_id, p.per_id, 'autoriser', NOW() FROM (
    SELECT 'administrateur_general_societe' AS role_code, 'fonction.gerer' AS permission_code
    UNION ALL SELECT 'administrateur_general_societe', 'competence.gerer'
    UNION ALL SELECT 'administrateur_general_societe', 'certification.gerer'
) src
JOIN sav_roles r ON r.rol_code = src.role_code
JOIN sav_permissions p ON p.per_code = src.permission_code
WHERE NOT EXISTS (
    SELECT 1 FROM sav_roles_permissions rp
    WHERE rp.rpe_role_id = r.rol_id AND rp.rpe_permission_id = p.per_id
);
