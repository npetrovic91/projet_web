-- ============================================================
-- AUTOSAV — Migration 2026_06_03_lot38_contextes_roles_admin_reseau
-- ------------------------------------------------------------
-- RECONSTITUTION : fichier référencé par
-- tests/Unit/CompanyAdminScopeTest.php mais absent de l'archive
-- public_html (6).zip (cf. ROADMAP_PRODUCTION.md §1.1).
--
-- Vérifie/complète les contextes rôle+fonction des deux comptes
-- démo de tête du réseau Auto Avenue Groupe créés par la migration
-- lot35 : admin.general (administrateur_general_societe) et
-- responsable.groupe (responsable_groupe_concessions), tous deux
-- affectés à la fonction DIRECTION_GENERALE.
--
-- Idempotent : WHERE NOT EXISTS partout — ce fichier ne fait que
-- confirmer/compléter ce que lot35 a déjà posé, il peut être
-- rejoué sans effet si lot35 est déjà passé.
-- ============================================================

INSERT INTO sav_roles_contextuels_utilisateurs (rcu_utilisateur_id, rcu_role_id, rcu_societe_id, rcu_debute_le, rcu_statut_id, rcu_cree_le)
SELECT uu.uti_id, r.rol_id, soc.soc_id, CURDATE(), 1, NOW()
FROM (
    SELECT 'admin.general' AS login, 'administrateur_general_societe' AS role_code, 'AUTO_AVENUE_GROUPE' AS societe_code
    UNION ALL SELECT 'responsable.groupe', 'responsable_groupe_concessions', 'AUTO_AVENUE_GROUPE'
) src
JOIN sav_utilisateurs uu ON uu.uti_identifiant = src.login
JOIN sav_roles r ON r.rol_code = src.role_code
JOIN sav_societes soc ON soc.soc_code = src.societe_code
WHERE NOT EXISTS (
    SELECT 1 FROM sav_roles_contextuels_utilisateurs rcu
    WHERE rcu.rcu_utilisateur_id = uu.uti_id AND rcu.rcu_role_id = r.rol_id AND rcu.rcu_societe_id = soc.soc_id
);

INSERT INTO sav_fonctions_utilisateurs (fut_utilisateur_id, fut_societe_id, fut_fonction_id, fut_debute_le, fut_statut_id, fut_cree_le)
SELECT uu.uti_id, soc.soc_id, f.fon_id, CURDATE(), 1, NOW()
FROM (
    SELECT 'admin.general' AS login, 'DIRECTION_GENERALE' AS fonction_code, 'AUTO_AVENUE_GROUPE' AS societe_code
    UNION ALL SELECT 'responsable.groupe', 'DIRECTION_GENERALE', 'AUTO_AVENUE_GROUPE'
) src
JOIN sav_utilisateurs uu ON uu.uti_identifiant = src.login
JOIN sav_fonctions f ON f.fon_code = src.fonction_code
JOIN sav_societes soc ON soc.soc_code = src.societe_code
WHERE NOT EXISTS (
    SELECT 1 FROM sav_fonctions_utilisateurs fut
    WHERE fut.fut_utilisateur_id = uu.uti_id AND fut.fut_fonction_id = f.fon_id
);
