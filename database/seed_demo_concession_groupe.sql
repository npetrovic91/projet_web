-- ============================================================
-- AUTOSAV — Jeu de démonstration : Groupe de concessions + Concession
-- ------------------------------------------------------------
-- Complète le jeu de comptes démo existant (MotorGroup = constructeur,
-- ImportAuto France = importateur, NeoVolt = marque) avec les deux
-- niveaux organisationnels manquants : Groupe de concessions et
-- Concession. Même échelle de rôles que les 3 sociétés démo
-- existantes : pdg, chef_de_service, chef_de_departement,
-- chef_d_equipe, technicien, expert_technique, carrossier, peintre,
-- receptionnaire, administrateur_groupe_concessions, administrateur_marque
-- (rôles 31 à 41 de sav_roles, déjà présents en base).
--
-- Mot de passe pour tous les comptes créés ici : Demo2026!
-- (même hash Argon2id que pdg.motorgroup / pdg.importauto, déjà
-- vérifié fonctionnel pour ce mot de passe).
--
-- Tous les identifiants (soc_id, uti_id, rcu_id...) sont attribués
-- par AUTO_INCREMENT : ce script ne fige aucun ID en dur et peut être
-- rejoué sur la base de prod sans collision.
-- ============================================================

START TRANSACTION;

-- ----------------------------------------------------------------
-- 1) Sociétés démo : Groupe de concessions + Concession rattachée
-- ----------------------------------------------------------------

INSERT INTO `sav_societes`
    (`soc_uuid`, `soc_code`, `soc_nom`, `soc_nom_legal`, `soc_nom_court`,
     `soc_est_holding`, `soc_societe_parente_id`, `soc_holding_id`,
     `soc_pays_id`, `soc_email`, `soc_statut_id`, `soc_cree_le`)
VALUES
    ('3beab5fe-893d-4830-bac6-619650bc0b7b', 'DEMO_GROUPE_CONCESSIONS',
     'Groupe Démo AutoSAV', 'Groupe Démo AutoSAV SAS', 'GroupeDemo',
     1, NULL, NULL, 1, 'contact@groupe-demo.autosav.demo', 1, NOW());

SET @groupe_id = LAST_INSERT_ID();

INSERT INTO `sav_societes`
    (`soc_uuid`, `soc_code`, `soc_nom`, `soc_nom_legal`, `soc_nom_court`,
     `soc_est_holding`, `soc_societe_parente_id`, `soc_holding_id`,
     `soc_pays_id`, `soc_email`, `soc_statut_id`, `soc_cree_le`)
VALUES
    ('34b26f60-b2be-4437-89a3-09bbdb42e061', 'DEMO_CONCESSION',
     'Concession Démo AutoSAV', 'Concession Démo AutoSAV SAS', 'ConcessionDemo',
     0, @groupe_id, @groupe_id, 1, 'contact@concession-demo.autosav.demo', 1, NOW());

SET @concession_id = LAST_INSERT_ID();

-- Typage des sociétés (sav_types_societes : 6 = groupe_concessions, 7 = concession)
INSERT INTO `sav_affectations_types_societes`
    (`ats_societe_id`, `ats_type_societe_id`, `ats_debute_le`, `ats_statut_id`, `ats_cree_le`)
VALUES
    (@groupe_id, 6, CURDATE(), 1, NOW()),
    (@concession_id, 7, CURDATE(), 1, NOW());

-- ----------------------------------------------------------------
-- 2) Utilisateurs démo — Groupe de concessions
-- ----------------------------------------------------------------

INSERT INTO `sav_utilisateurs`
    (`uti_uuid`, `uti_identifiant`, `uti_email`, `uti_email_normalise`,
     `uti_mot_de_passe_hash`, `uti_doit_changer_mot_de_passe`,
     `uti_statut_id`, `uti_est_systeme`, `uti_est_verrouille`,
     `uti_societe_active_id`, `uti_langue`, `uti_fuseau_horaire_id`,
     `uti_cree_le`, `uti_modifie_le`, `uti_acl_version`)
VALUES
    ('4b0154a1-f6a2-4bdf-bbba-dd4d310224c8', 'pdg.demogroupe', 'pdg.demogroupe@autosav.demo', 'pdg.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1),
    ('f4e3ce99-ae0b-44e7-99f1-e48ec69027c6', 'chef.service.demogroupe', 'chef.service.demogroupe@autosav.demo', 'chef.service.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1),
    ('c8fb12da-3883-422a-9d88-7df301f5f0f8', 'chef.dept.demogroupe', 'chef.dept.demogroupe@autosav.demo', 'chef.dept.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1),
    ('6383012c-5ed3-4e4e-9b76-9cbd120e090b', 'chef.equipe.demogroupe', 'chef.equipe.demogroupe@autosav.demo', 'chef.equipe.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1),
    ('7bf21939-391b-4a85-a17c-1f07181ddacc', 'technicien.demogroupe', 'technicien.demogroupe@autosav.demo', 'technicien.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1),
    ('ff30a185-de53-4917-89a8-5d3aed66b596', 'expert.demogroupe', 'expert.demogroupe@autosav.demo', 'expert.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1),
    ('fdb92ad7-1c51-4b5b-a19b-967fcc351627', 'carrossier.demogroupe', 'carrossier.demogroupe@autosav.demo', 'carrossier.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1),
    ('71db63b1-1837-4dab-a1da-42b1e416fe31', 'peintre.demogroupe', 'peintre.demogroupe@autosav.demo', 'peintre.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1),
    ('32315629-5dc1-41a9-80c8-07c5ca91e844', 'receptioniste.demogroupe', 'receptioniste.demogroupe@autosav.demo', 'receptioniste.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1),
    ('f770c44a-e270-45ef-9eb2-5f3987e34338', 'admin.groupe.demogroupe', 'admin.groupe.demogroupe@autosav.demo', 'admin.groupe.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1),
    ('874cd7a2-fdee-4a54-b64f-426ce087da6f', 'admin.marque.demogroupe', 'admin.marque.demogroupe@autosav.demo', 'admin.marque.demogroupe@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @groupe_id, 'fr', 1, NOW(), NOW(), 1);

SET @groupe_user_first_id = LAST_INSERT_ID();

INSERT INTO `sav_roles_contextuels_utilisateurs`
    (`rcu_utilisateur_id`, `rcu_role_id`, `rcu_societe_id`, `rcu_debute_le`, `rcu_statut_id`, `rcu_cree_le`)
VALUES
    (@groupe_user_first_id + 0,  31, @groupe_id, CURDATE(), 1, NOW()), -- pdg
    (@groupe_user_first_id + 1,  32, @groupe_id, CURDATE(), 1, NOW()), -- chef_de_service
    (@groupe_user_first_id + 2,  33, @groupe_id, CURDATE(), 1, NOW()), -- chef_de_departement
    (@groupe_user_first_id + 3,  34, @groupe_id, CURDATE(), 1, NOW()), -- chef_d_equipe
    (@groupe_user_first_id + 4,  35, @groupe_id, CURDATE(), 1, NOW()), -- technicien
    (@groupe_user_first_id + 5,  36, @groupe_id, CURDATE(), 1, NOW()), -- expert_technique
    (@groupe_user_first_id + 6,  37, @groupe_id, CURDATE(), 1, NOW()), -- carrossier
    (@groupe_user_first_id + 7,  38, @groupe_id, CURDATE(), 1, NOW()), -- peintre
    (@groupe_user_first_id + 8,  39, @groupe_id, CURDATE(), 1, NOW()), -- receptionnaire
    (@groupe_user_first_id + 9,  40, @groupe_id, CURDATE(), 1, NOW()), -- administrateur_groupe_concessions
    (@groupe_user_first_id + 10, 41, @groupe_id, CURDATE(), 1, NOW()); -- administrateur_marque

-- ----------------------------------------------------------------
-- 3) Utilisateurs démo — Concession
-- ----------------------------------------------------------------

INSERT INTO `sav_utilisateurs`
    (`uti_uuid`, `uti_identifiant`, `uti_email`, `uti_email_normalise`,
     `uti_mot_de_passe_hash`, `uti_doit_changer_mot_de_passe`,
     `uti_statut_id`, `uti_est_systeme`, `uti_est_verrouille`,
     `uti_societe_active_id`, `uti_langue`, `uti_fuseau_horaire_id`,
     `uti_cree_le`, `uti_modifie_le`, `uti_acl_version`)
VALUES
    ('e77fdc5a-8709-4e2a-bf8d-9524e9bcfd58', 'pdg.democoncession', 'pdg.democoncession@autosav.demo', 'pdg.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1),
    ('2f4c7f1a-7387-45d2-a8cc-7819284c6fe4', 'chef.service.democoncession', 'chef.service.democoncession@autosav.demo', 'chef.service.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1),
    ('d1033d13-8877-42b9-a77e-81281e1b451b', 'chef.dept.democoncession', 'chef.dept.democoncession@autosav.demo', 'chef.dept.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1),
    ('774fe0ac-5c0f-4bd4-9008-606306b2cd70', 'chef.equipe.democoncession', 'chef.equipe.democoncession@autosav.demo', 'chef.equipe.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1),
    ('ab8328f6-a9d9-474c-bc6c-3c619ac945d2', 'technicien.democoncession', 'technicien.democoncession@autosav.demo', 'technicien.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1),
    ('69774dfa-3ca0-48f0-a257-c976b1fb090a', 'expert.democoncession', 'expert.democoncession@autosav.demo', 'expert.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1),
    ('074b1382-26ac-4cbd-83c2-8b1a6a11abb8', 'carrossier.democoncession', 'carrossier.democoncession@autosav.demo', 'carrossier.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1),
    ('ff923c25-2d9c-4bed-ad60-2a107aa15799', 'peintre.democoncession', 'peintre.democoncession@autosav.demo', 'peintre.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1),
    ('86ced9d9-2e15-4b3d-b071-873399ebf603', 'receptioniste.democoncession', 'receptioniste.democoncession@autosav.demo', 'receptioniste.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1),
    ('01afb753-4acd-48f9-b26d-2cc72ee9a058', 'admin.groupe.democoncession', 'admin.groupe.democoncession@autosav.demo', 'admin.groupe.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1),
    ('69896498-4e7a-4e2c-a10a-a351aa44d4e7', 'admin.marque.democoncession', 'admin.marque.democoncession@autosav.demo', 'admin.marque.democoncession@autosav.demo', '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c', 1, 16, 0, 0, @concession_id, 'fr', 1, NOW(), NOW(), 1);

SET @concession_user_first_id = LAST_INSERT_ID();

-- rcu_concession_societe_id renseigné : périmètre CONCESSION (contrairement
-- au groupe ci-dessus, où le rôle ne pointe que vers la société elle-même).
INSERT INTO `sav_roles_contextuels_utilisateurs`
    (`rcu_utilisateur_id`, `rcu_role_id`, `rcu_societe_id`, `rcu_concession_societe_id`, `rcu_debute_le`, `rcu_statut_id`, `rcu_cree_le`)
VALUES
    (@concession_user_first_id + 0,  31, @concession_id, @concession_id, CURDATE(), 1, NOW()), -- pdg
    (@concession_user_first_id + 1,  32, @concession_id, @concession_id, CURDATE(), 1, NOW()), -- chef_de_service
    (@concession_user_first_id + 2,  33, @concession_id, @concession_id, CURDATE(), 1, NOW()), -- chef_de_departement
    (@concession_user_first_id + 3,  34, @concession_id, @concession_id, CURDATE(), 1, NOW()), -- chef_d_equipe
    (@concession_user_first_id + 4,  35, @concession_id, @concession_id, CURDATE(), 1, NOW()), -- technicien
    (@concession_user_first_id + 5,  36, @concession_id, @concession_id, CURDATE(), 1, NOW()), -- expert_technique
    (@concession_user_first_id + 6,  37, @concession_id, @concession_id, CURDATE(), 1, NOW()), -- carrossier
    (@concession_user_first_id + 7,  38, @concession_id, @concession_id, CURDATE(), 1, NOW()), -- peintre
    (@concession_user_first_id + 8,  39, @concession_id, @concession_id, CURDATE(), 1, NOW()), -- receptionnaire
    (@concession_user_first_id + 9,  40, @concession_id, @concession_id, CURDATE(), 1, NOW()), -- administrateur_groupe_concessions
    (@concession_user_first_id + 10, 41, @concession_id, @concession_id, CURDATE(), 1, NOW()); -- administrateur_marque

COMMIT;

-- ============================================================
-- Récapitulatif des comptes créés (mot de passe : Demo2026!)
--
-- Groupe Démo AutoSAV (groupe de concessions) :
--   pdg.demogroupe, chef.service.demogroupe, chef.dept.demogroupe,
--   chef.equipe.demogroupe, technicien.demogroupe, expert.demogroupe,
--   carrossier.demogroupe, peintre.demogroupe, receptioniste.demogroupe,
--   admin.groupe.demogroupe, admin.marque.demogroupe
--
-- Concession Démo AutoSAV (concession, rattachée au groupe ci-dessus) :
--   pdg.democoncession, chef.service.democoncession, chef.dept.democoncession,
--   chef.equipe.democoncession, technicien.democoncession, expert.democoncession,
--   carrossier.democoncession, peintre.democoncession, receptioniste.democoncession,
--   admin.groupe.democoncession, admin.marque.democoncession
-- ============================================================
