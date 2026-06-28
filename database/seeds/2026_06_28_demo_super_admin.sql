-- ============================================================
-- AUTOSAV — Compte démo super_administrateur
-- ------------------------------------------------------------
-- Aucun des lots démo existants (lot35, MotorGroup/ImportAuto/NeoVolt,
-- Groupe/Concession démo) ne crée de compte super_administrateur :
-- tous sont scopés à une société. Le rôle super_administrateur
-- (rol_id = 1) est un rôle PLATEFORME, sans société associée
-- (rcu_societe_id = NULL) — il ne fait pas partie des 11 rôles
-- "métier" utilisés par les autres lots démo.
--
-- Mot de passe : Demo2026! (même hash que les 54 autres comptes démo,
-- voir database/seeds/2026_06_27_unifier_mot_de_passe_demo.sql).
--
-- uti_est_systeme = 1 et uti_societe_active_id = 1 reproduisent
-- exactement le schéma du compte réel super.admin@autosav.local
-- (vérifié sur un export de production) : un super-administrateur n'a
-- pas besoin d'adhésion à une société tenant pour se connecter
-- (cf. Modules/Auth/Services/AuthService.php:63, qui dispense
-- explicitement les comptes uti_est_systeme=1 de cette exigence).
-- societe_id = 1 = "AUTOSAV" (société interne de la plateforme,
-- jamais une société tenant démo) — référencée en lecture seule,
-- jamais modifiée par ce script.
--
-- À exécuter manuellement (phpMyAdmin ou client SQL), comme tout fichier
-- de database/seeds/ : jamais appliqué automatiquement par
-- bin/migrate.php (qui ne scanne que database/migrations/).
-- ============================================================

INSERT INTO `sav_utilisateurs`
    (`uti_uuid`, `uti_identifiant`, `uti_email`, `uti_email_normalise`,
     `uti_mot_de_passe_hash`, `uti_doit_changer_mot_de_passe`,
     `uti_statut_id`, `uti_est_systeme`, `uti_est_verrouille`,
     `uti_societe_active_id`, `uti_langue`, `uti_fuseau_horaire_id`,
     `uti_cree_le`, `uti_modifie_le`, `uti_acl_version`)
VALUES
    (UUID(), 'super.admin.demo', 'super.admin.demo@autosav.demo', 'super.admin.demo@autosav.demo',
     '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c',
     1, 16, 1, 0, 1, 'fr', 1, NOW(), NOW(), 1);

SET @super_admin_demo_id = LAST_INSERT_ID();

INSERT INTO `sav_roles_contextuels_utilisateurs`
    (`rcu_utilisateur_id`, `rcu_role_id`, `rcu_societe_id`, `rcu_debute_le`, `rcu_statut_id`, `rcu_cree_le`)
VALUES
    (@super_admin_demo_id, 1, NULL, CURDATE(), 1, NOW()); -- 1 = super_administrateur
