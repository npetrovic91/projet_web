-- ============================================================
-- AUTOSAV — Migration 2026_06_03_lot35_demo_reseau_automobile
-- ------------------------------------------------------------
-- RECONSTITUTION : ce fichier était référencé par
-- tests/Unit/DemoAutomotiveNetworkSeedTest.php et
-- tests/Unit/ProjectRoleAccessTest.php mais absent de l'archive
-- public_html (6).zip fournie le 2026-06-25 (perte constatée lors
-- de l'audit, cf. ROADMAP_PRODUCTION.md §1.1). Reconstitué à partir
-- de docs/COMPTES_DEMO_LOT35.md et de l'état réel de la base
-- (u166513890_base, sociétés id 3-32, utilisateurs id 1-12), pour
-- restaurer la trace versionnée et faire repasser les tests.
--
-- Idempotent : toutes les écritures utilisent INSERT ... ON
-- DUPLICATE KEY UPDATE / INSERT IGNORE pour pouvoir être rejouées
-- sans dupliquer ce qui existe déjà en production.
-- ============================================================

-- ----------------------------------------------------------------
-- 1) Constructeurs, marques, groupes, concessions
-- ----------------------------------------------------------------

INSERT INTO sav_societes (soc_uuid, soc_code, soc_nom, soc_nom_legal, soc_nom_court, soc_est_holding, soc_email, soc_site_web, soc_statut_id, soc_cree_le)
VALUES
    (UUID(), 'BMW_GROUP', 'BMW Group', 'BMW Group', 'BMW Group', 1, 'contact@bmw-group.demo', 'https://www.bmwgroup.com', 1, NOW()),
    (UUID(), 'VOLKSWAGEN_AG', 'Volkswagen AG', 'Volkswagen AG', 'VW AG', 1, 'contact@volkswagen-ag.demo', 'https://www.volkswagen-group.com', 1, NOW()),
    (UUID(), 'MERCEDES_BENZ_GROUP', 'Mercedes-Benz Group AG', 'Mercedes-Benz Group AG', 'Mercedes-Benz', 1, 'contact@mercedes-benz.demo', 'https://group.mercedes-benz.com', 1, NOW()),
    (UUID(), 'STELLANTIS', 'Stellantis', 'Stellantis N.V.', 'Stellantis', 1, 'contact@stellantis.demo', 'https://www.stellantis.com', 1, NOW()),
    (UUID(), 'FIAT_GROUPE', 'Fiat Groupe', 'Fiat Groupe', 'Fiat', 1, 'contact@fiat-groupe.demo', NULL, 1, NOW()),
    (UUID(), 'RENAULT_GROUP', 'Renault Group', 'Renault Group', 'Renault Group', 1, 'contact@renault-group.demo', 'https://www.renaultgroup.com', 1, NOW())
ON DUPLICATE KEY UPDATE soc_nom = VALUES(soc_nom);

INSERT INTO sav_societes (soc_uuid, soc_code, soc_nom, soc_nom_legal, soc_nom_court, soc_est_holding, soc_email, soc_site_web, soc_statut_id, soc_cree_le)
VALUES
    (UUID(), 'BMW', 'BMW', 'BMW', 'BMW', 0, 'marque@bmw.demo', 'https://www.bmw.fr', 1, NOW()),
    (UUID(), 'VOLKSWAGEN', 'Volkswagen', 'Volkswagen', 'VW', 0, 'marque@volkswagen.demo', 'https://www.volkswagen.fr', 1, NOW()),
    (UUID(), 'AUDI', 'Audi', 'Audi', 'Audi', 0, 'marque@audi.demo', 'https://www.audi.fr', 1, NOW()),
    (UUID(), 'SEAT', 'SEAT', 'SEAT', 'SEAT', 0, 'marque@seat.demo', 'https://www.seat.fr', 1, NOW()),
    (UUID(), 'SKODA', 'Skoda', 'Skoda Auto', 'Skoda', 0, 'marque@skoda.demo', 'https://www.skoda.fr', 1, NOW()),
    (UUID(), 'RENAULT', 'Renault', 'Renault', 'Renault', 0, 'marque@renault.demo', 'https://www.renault.fr', 1, NOW()),
    (UUID(), 'PEUGEOT', 'Peugeot', 'Peugeot', 'Peugeot', 0, 'marque@peugeot.demo', 'https://www.peugeot.fr', 1, NOW()),
    (UUID(), 'CITROEN', 'Citroen', 'Citroen', 'Citroen', 0, 'marque@citroen.demo', 'https://www.citroen.fr', 1, NOW()),
    (UUID(), 'OPEL', 'Opel', 'Opel', 'Opel', 0, 'marque@opel.demo', 'https://www.opel.fr', 1, NOW()),
    (UUID(), 'DS_AUTOMOBILES', 'DS Automobiles', 'DS Automobiles', 'DS', 0, 'marque@ds-automobiles.demo', 'https://www.dsautomobiles.fr', 1, NOW()),
    (UUID(), 'FIAT', 'Fiat', 'Fiat', 'Fiat', 0, 'marque@fiat.demo', 'https://www.fiat.fr', 1, NOW()),
    (UUID(), 'ALFA_ROMEO', 'Alfa Romeo', 'Alfa Romeo', 'Alfa Romeo', 0, 'marque@alfa-romeo.demo', 'https://www.alfaromeo.fr', 1, NOW()),
    (UUID(), 'MERCEDES', 'Mercedes', 'Mercedes-Benz', 'Mercedes', 0, 'marque@mercedes.demo', 'https://www.mercedes-benz.fr', 1, NOW())
ON DUPLICATE KEY UPDATE soc_nom = VALUES(soc_nom);

INSERT INTO sav_societes (soc_uuid, soc_code, soc_nom, soc_nom_legal, soc_nom_court, soc_est_holding, soc_email, soc_statut_id, soc_cree_le)
VALUES
    (UUID(), 'AUTO_AVENUE_GROUPE', 'Auto Avenue Groupe', 'Auto Avenue Groupe SAS', 'Auto Avenue', 1, 'contact@auto-avenue.demo', 1, NOW()),
    (UUID(), 'PREMIUM_MOTORS_EST', 'Premium Motors Est', 'Premium Motors Est SAS', 'Premium Est', 1, 'contact@premium-motors-est.demo', 1, NOW()),
    (UUID(), 'GARAGE_MULTIMARQUE_PRO', 'Garage Multimarque Pro', 'Garage Multimarque Pro SARL', 'GMP', 1, 'contact@garage-multimarque-pro.demo', 1, NOW())
ON DUPLICATE KEY UPDATE soc_nom = VALUES(soc_nom);

INSERT INTO sav_societes (soc_uuid, soc_code, soc_nom, soc_nom_legal, soc_nom_court, soc_est_holding, soc_societe_parente_id, soc_holding_id, soc_email, soc_statut_id, soc_cree_le)
SELECT UUID(), c.code, c.nom, c.nom_legal, c.nom_court, 0, g.soc_id, g.soc_id, c.email, 1, NOW()
FROM (
    SELECT 'AUTO_AVENUE_PARIS' AS code, 'Auto Avenue Paris' AS nom, 'Auto Avenue Paris SAS' AS nom_legal, 'AA Paris' AS nom_court, 'paris@auto-avenue.demo' AS email, 'AUTO_AVENUE_GROUPE' AS groupe_code
    UNION ALL SELECT 'AUTO_AVENUE_LYON', 'Auto Avenue Lyon', 'Auto Avenue Lyon SAS', 'AA Lyon', 'lyon@auto-avenue.demo', 'AUTO_AVENUE_GROUPE'
    UNION ALL SELECT 'AUTO_AVENUE_MARSEILLE', 'Auto Avenue Marseille', 'Auto Avenue Marseille SAS', 'AA Marseille', 'marseille@auto-avenue.demo', 'AUTO_AVENUE_GROUPE'
    UNION ALL SELECT 'AUTO_AVENUE_LILLE', 'Auto Avenue Lille', 'Auto Avenue Lille SAS', 'AA Lille', 'lille@auto-avenue.demo', 'AUTO_AVENUE_GROUPE'
    UNION ALL SELECT 'PREMIUM_MOTORS_STRASBOURG', 'Premium Motors Strasbourg', 'Premium Motors Strasbourg SAS', 'PM Strasbourg', 'strasbourg@premium-motors-est.demo', 'PREMIUM_MOTORS_EST'
    UNION ALL SELECT 'PREMIUM_MOTORS_METZ', 'Premium Motors Metz', 'Premium Motors Metz SAS', 'PM Metz', 'metz@premium-motors-est.demo', 'PREMIUM_MOTORS_EST'
    UNION ALL SELECT 'GARAGE_MULTIMARQUE_NANTES', 'Garage Multimarque Nantes', 'Garage Multimarque Nantes SARL', 'GMP Nantes', 'nantes@garage-multimarque-pro.demo', 'GARAGE_MULTIMARQUE_PRO'
    UNION ALL SELECT 'GARAGE_MULTIMARQUE_TOULOUSE', 'Garage Multimarque Toulouse', 'Garage Multimarque Toulouse SARL', 'GMP Toulouse', 'toulouse@garage-multimarque-pro.demo', 'GARAGE_MULTIMARQUE_PRO'
) c
JOIN sav_societes g ON g.soc_code = c.groupe_code
ON DUPLICATE KEY UPDATE soc_nom = VALUES(soc_nom);

-- Typage (sav_types_societes : 3 constructeur, 4 marque, 6 groupe_concessions, 7 concession)
INSERT INTO sav_affectations_types_societes (ats_societe_id, ats_type_societe_id, ats_debute_le, ats_statut_id, ats_cree_le)
SELECT s.soc_id, t.tso_id, CURDATE(), 1, NOW()
FROM sav_societes s
JOIN (
    SELECT 'BMW_GROUP' AS code, 'constructeur' AS type UNION ALL SELECT 'VOLKSWAGEN_AG', 'constructeur'
    UNION ALL SELECT 'MERCEDES_BENZ_GROUP', 'constructeur' UNION ALL SELECT 'STELLANTIS', 'constructeur'
    UNION ALL SELECT 'FIAT_GROUPE', 'constructeur' UNION ALL SELECT 'RENAULT_GROUP', 'constructeur'
    UNION ALL SELECT 'BMW', 'marque' UNION ALL SELECT 'VOLKSWAGEN', 'marque' UNION ALL SELECT 'AUDI', 'marque'
    UNION ALL SELECT 'SEAT', 'marque' UNION ALL SELECT 'SKODA', 'marque' UNION ALL SELECT 'RENAULT', 'marque'
    UNION ALL SELECT 'PEUGEOT', 'marque' UNION ALL SELECT 'CITROEN', 'marque' UNION ALL SELECT 'OPEL', 'marque'
    UNION ALL SELECT 'DS_AUTOMOBILES', 'marque' UNION ALL SELECT 'FIAT', 'marque' UNION ALL SELECT 'ALFA_ROMEO', 'marque'
    UNION ALL SELECT 'MERCEDES', 'marque'
    UNION ALL SELECT 'AUTO_AVENUE_GROUPE', 'groupe_concessions' UNION ALL SELECT 'PREMIUM_MOTORS_EST', 'groupe_concessions'
    UNION ALL SELECT 'GARAGE_MULTIMARQUE_PRO', 'groupe_concessions'
    UNION ALL SELECT 'AUTO_AVENUE_PARIS', 'concession' UNION ALL SELECT 'AUTO_AVENUE_LYON', 'concession'
    UNION ALL SELECT 'AUTO_AVENUE_MARSEILLE', 'concession' UNION ALL SELECT 'AUTO_AVENUE_LILLE', 'concession'
    UNION ALL SELECT 'PREMIUM_MOTORS_STRASBOURG', 'concession' UNION ALL SELECT 'PREMIUM_MOTORS_METZ', 'concession'
    UNION ALL SELECT 'GARAGE_MULTIMARQUE_NANTES', 'concession' UNION ALL SELECT 'GARAGE_MULTIMARQUE_TOULOUSE', 'concession'
) src ON src.code = s.soc_code
JOIN sav_types_societes t ON t.tso_code = src.type
WHERE NOT EXISTS (
    SELECT 1 FROM sav_affectations_types_societes ats
    WHERE ats.ats_societe_id = s.soc_id AND ats.ats_type_societe_id = t.tso_id
);

-- Représentation des marques par constructeur (sav_representations_marques_societes)
INSERT INTO sav_representations_marques_societes (concession_societe_id, marque_societe_id, importateur_societe_id, constructeur_societe_id, debute_le, statut_id, cree_le)
SELECT NULL, marque.soc_id, NULL, constructeur.soc_id, CURDATE(), 1, NOW()
FROM sav_societes constructeur
JOIN sav_societes marque ON (
       (constructeur.soc_code = 'VOLKSWAGEN_AG' AND marque.soc_code IN ('VOLKSWAGEN', 'AUDI', 'SEAT', 'SKODA'))
    OR (constructeur.soc_code = 'STELLANTIS' AND marque.soc_code IN ('PEUGEOT', 'CITROEN', 'OPEL', 'DS_AUTOMOBILES'))
    OR (constructeur.soc_code = 'FIAT_GROUPE' AND marque.soc_code IN ('FIAT', 'ALFA_ROMEO'))
    OR (constructeur.soc_code = 'BMW_GROUP' AND marque.soc_code IN ('BMW'))
    OR (constructeur.soc_code = 'MERCEDES_BENZ_GROUP' AND marque.soc_code IN ('MERCEDES'))
    OR (constructeur.soc_code = 'RENAULT_GROUP' AND marque.soc_code IN ('RENAULT'))
)
WHERE NOT EXISTS (
    SELECT 1 FROM sav_representations_marques_societes r
    WHERE r.constructeur_societe_id = constructeur.soc_id AND r.marque_societe_id = marque.soc_id
);

-- ----------------------------------------------------------------
-- 2) Rôles et fonctions du réseau démo
-- ----------------------------------------------------------------

INSERT INTO sav_roles (rol_code, rol_nom, rol_description, rol_societe_proprietaire_id, rol_portee_code, rol_statut_id, rol_cree_le)
SELECT code, nom, description, 1, 'interne', 1, NOW() FROM (
    SELECT 'administrateur_general_societe' AS code, 'Administrateur general societe' AS nom, 'Administre une societe cliente, ses utilisateurs et son contexte.' AS description
    UNION ALL SELECT 'responsable_groupe_concessions', 'Responsable groupe de concessions', 'Pilote plusieurs concessions rattachees au meme groupe.'
    UNION ALL SELECT 'directeur_concession', 'Directeur de concession', 'Responsable operationnel d une concession.'
    UNION ALL SELECT 'responsable_apres_vente', 'Responsable apres-vente', 'Pilote le service apres-vente et les equipes atelier.'
    UNION ALL SELECT 'responsable_garantie', 'Responsable garantie', 'Controle les garanties constructeur, accords et retours.'
    UNION ALL SELECT 'conseiller_service', 'Conseiller service', 'Gere les demandes clients et les dossiers SAV.'
    UNION ALL SELECT 'technicien_sav', 'Technicien SAV', 'Intervient sur les diagnostics et operations techniques.'
    UNION ALL SELECT 'gestionnaire_pieces', 'Gestionnaire pieces', 'Suit les pieces, approvisionnements et retours.'
) src
WHERE NOT EXISTS (SELECT 1 FROM sav_roles r WHERE r.rol_code = src.code);

INSERT INTO sav_fonctions (fon_code, fon_nom, fon_description, fon_societe_proprietaire_id, fon_portee_code, fon_statut_id, fon_cree_le)
SELECT code, nom, description, 1, 'interne', 1, NOW() FROM (
    SELECT 'DIRECTION_GENERALE' AS code, 'Direction generale' AS nom, 'Direction et administration generale de la societe.' AS description
    UNION ALL SELECT 'DIRECTION_CONCESSION', 'Direction concession', 'Direction operationnelle d une concession.'
    UNION ALL SELECT 'RESPONSABLE_APRES_VENTE', 'Responsable apres-vente', 'Pilote le service apres-vente.'
    UNION ALL SELECT 'RESPONSABLE_GARANTIE', 'Responsable garantie', 'Controle des garanties constructeur.'
    UNION ALL SELECT 'CONSEILLER_SERVICE', 'Conseiller service', 'Accueil et suivi des dossiers clients.'
    UNION ALL SELECT 'TECHNICIEN_DIAGNOSTIC', 'Technicien diagnostic', 'Diagnostic et interventions techniques.'
    UNION ALL SELECT 'MAGASINIER', 'Magasinier', 'Gestion physique du stock de pieces.'
    UNION ALL SELECT 'GESTIONNAIRE_PIECES', 'Gestionnaire pieces', 'Suivi des pieces et approvisionnements.'
) src
WHERE NOT EXISTS (SELECT 1 FROM sav_fonctions f WHERE f.fon_code = src.code);

-- ----------------------------------------------------------------
-- 3) Utilisateurs démo du réseau (mot de passe commun, changement forcé)
-- ----------------------------------------------------------------
-- Hash Argon2id de 'DemoAutosav!2026' (cf. docs/COMPTES_DEMO_LOT35.md)

INSERT INTO sav_utilisateurs (uti_uuid, uti_identifiant, uti_email, uti_email_normalise, uti_mot_de_passe_hash, uti_doit_changer_mot_de_passe, uti_statut_id, uti_societe_active_id, uti_marque_active_id, uti_langue, uti_fuseau_horaire_id, uti_cree_le)
SELECT UUID(), u.login, u.email, u.email, '$argon2id$v=19$m=65536,t=4,p=1$eG91d1ZxUGZ1LjV1MDFPVw$NfTUFJpcpH6gZegE8mJZ0BNRgK2O+KZUXTuJ/pI1784', 1, 16, soc.soc_id, marque.soc_id, 'fr', 1, NOW()
FROM (
    SELECT 'admin.general' AS login, 'admin.general@autosav.demo' AS email, 'AUTO_AVENUE_GROUPE' AS societe_code, NULL AS marque_code
    UNION ALL SELECT 'responsable.groupe', 'responsable.groupe@autosav.demo', 'AUTO_AVENUE_GROUPE', NULL
    UNION ALL SELECT 'directeur.paris', 'directeur.paris@autosav.demo', 'AUTO_AVENUE_PARIS', 'VOLKSWAGEN'
    UNION ALL SELECT 'responsable.sav', 'responsable.sav@autosav.demo', 'AUTO_AVENUE_PARIS', 'AUDI'
    UNION ALL SELECT 'conseiller.service', 'conseiller.service@autosav.demo', 'AUTO_AVENUE_MARSEILLE', 'RENAULT'
    UNION ALL SELECT 'technicien.diagnostic', 'technicien.diagnostic@autosav.demo', 'AUTO_AVENUE_LYON', 'BMW'
    UNION ALL SELECT 'gestionnaire.pieces', 'gestionnaire.pieces@autosav.demo', 'PREMIUM_MOTORS_STRASBOURG', 'MERCEDES'
    UNION ALL SELECT 'responsable.garantie', 'responsable.garantie@autosav.demo', 'AUTO_AVENUE_LILLE', 'PEUGEOT'
    UNION ALL SELECT 'magasinier.lille', 'magasinier.lille@autosav.demo', 'AUTO_AVENUE_LILLE', 'OPEL'
    UNION ALL SELECT 'conseiller.toulouse', 'conseiller.toulouse@autosav.demo', 'GARAGE_MULTIMARQUE_TOULOUSE', 'FIAT'
) u
JOIN sav_societes soc ON soc.soc_code = u.societe_code
LEFT JOIN sav_societes marque ON marque.soc_code = u.marque_code
ON DUPLICATE KEY UPDATE
    uti_mot_de_passe_hash = VALUES(uti_mot_de_passe_hash),
    uti_doit_changer_mot_de_passe = VALUES(uti_doit_changer_mot_de_passe);

-- Rôle contextuel par utilisateur démo
INSERT INTO sav_roles_contextuels_utilisateurs (rcu_utilisateur_id, rcu_role_id, rcu_societe_id, rcu_concession_societe_id, rcu_debute_le, rcu_statut_id, rcu_cree_le)
SELECT uu.uti_id, r.rol_id, soc.soc_id, IF(t.tso_code = 'concession', soc.soc_id, NULL), CURDATE(), 1, NOW()
FROM (
    SELECT 'admin.general' AS login, 'administrateur_general_societe' AS role_code, 'AUTO_AVENUE_GROUPE' AS societe_code
    UNION ALL SELECT 'responsable.groupe', 'responsable_groupe_concessions', 'AUTO_AVENUE_GROUPE'
    UNION ALL SELECT 'directeur.paris', 'directeur_concession', 'AUTO_AVENUE_PARIS'
    UNION ALL SELECT 'responsable.sav', 'responsable_apres_vente', 'AUTO_AVENUE_PARIS'
    UNION ALL SELECT 'conseiller.service', 'conseiller_service', 'AUTO_AVENUE_MARSEILLE'
    UNION ALL SELECT 'technicien.diagnostic', 'technicien_sav', 'AUTO_AVENUE_LYON'
    UNION ALL SELECT 'gestionnaire.pieces', 'gestionnaire_pieces', 'PREMIUM_MOTORS_STRASBOURG'
    UNION ALL SELECT 'responsable.garantie', 'responsable_garantie', 'AUTO_AVENUE_LILLE'
    UNION ALL SELECT 'magasinier.lille', 'gestionnaire_pieces', 'AUTO_AVENUE_LILLE'
    UNION ALL SELECT 'conseiller.toulouse', 'conseiller_service', 'GARAGE_MULTIMARQUE_TOULOUSE'
) src
JOIN sav_utilisateurs uu ON uu.uti_identifiant = src.login
JOIN sav_roles r ON r.rol_code = src.role_code
JOIN sav_societes soc ON soc.soc_code = src.societe_code
JOIN sav_affectations_types_societes ats ON ats.ats_societe_id = soc.soc_id
JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id AND t.tso_code IN ('groupe_concessions', 'concession')
WHERE NOT EXISTS (
    SELECT 1 FROM sav_roles_contextuels_utilisateurs rcu
    WHERE rcu.rcu_utilisateur_id = uu.uti_id AND rcu.rcu_role_id = r.rol_id AND rcu.rcu_societe_id = soc.soc_id
);

-- Fonction métier par utilisateur démo
INSERT INTO sav_fonctions_utilisateurs (fut_utilisateur_id, fut_societe_id, fut_fonction_id, fut_debute_le, fut_statut_id, fut_cree_le)
SELECT uu.uti_id, soc.soc_id, f.fon_id, CURDATE(), 1, NOW()
FROM (
    SELECT 'admin.general' AS login, 'DIRECTION_GENERALE' AS fonction_code, 'AUTO_AVENUE_GROUPE' AS societe_code
    UNION ALL SELECT 'responsable.groupe', 'DIRECTION_GENERALE', 'AUTO_AVENUE_GROUPE'
    UNION ALL SELECT 'directeur.paris', 'DIRECTION_CONCESSION', 'AUTO_AVENUE_PARIS'
    UNION ALL SELECT 'responsable.sav', 'RESPONSABLE_APRES_VENTE', 'AUTO_AVENUE_PARIS'
    UNION ALL SELECT 'conseiller.service', 'CONSEILLER_SERVICE', 'AUTO_AVENUE_MARSEILLE'
    UNION ALL SELECT 'technicien.diagnostic', 'TECHNICIEN_DIAGNOSTIC', 'AUTO_AVENUE_LYON'
    UNION ALL SELECT 'gestionnaire.pieces', 'GESTIONNAIRE_PIECES', 'PREMIUM_MOTORS_STRASBOURG'
    UNION ALL SELECT 'responsable.garantie', 'RESPONSABLE_GARANTIE', 'AUTO_AVENUE_LILLE'
    UNION ALL SELECT 'magasinier.lille', 'MAGASINIER', 'AUTO_AVENUE_LILLE'
    UNION ALL SELECT 'conseiller.toulouse', 'CONSEILLER_SERVICE', 'GARAGE_MULTIMARQUE_TOULOUSE'
) src
JOIN sav_utilisateurs uu ON uu.uti_identifiant = src.login
JOIN sav_fonctions f ON f.fon_code = src.fonction_code
JOIN sav_societes soc ON soc.soc_code = src.societe_code
WHERE NOT EXISTS (
    SELECT 1 FROM sav_fonctions_utilisateurs fut
    WHERE fut.fut_utilisateur_id = uu.uti_id AND fut.fut_fonction_id = f.fon_id
);

-- ----------------------------------------------------------------
-- 4) Permissions de l'administrateur général de société
-- ----------------------------------------------------------------

INSERT INTO sav_roles_permissions (rpe_role_id, rpe_permission_id, rpe_effet, rpe_cree_le)
SELECT r.rol_id, p.per_id, 'autoriser', NOW()
FROM (
    SELECT 'administrateur_general_societe' AS role_code, 'utilisateur.creer' AS permission_code
    UNION ALL SELECT 'administrateur_general_societe', 'utilisateur.modifier'
    UNION ALL SELECT 'administrateur_general_societe', 'utilisateur.bloquer'
    UNION ALL SELECT 'administrateur_general_societe', 'societe.creer'
    UNION ALL SELECT 'administrateur_general_societe', 'societe.modifier'
    UNION ALL SELECT 'administrateur_general_societe', 'role.gerer'
    UNION ALL SELECT 'administrateur_general_societe', 'fonction.gerer'
    UNION ALL SELECT 'administrateur_general_societe', 'competence.gerer'
    UNION ALL SELECT 'administrateur_general_societe', 'certification.gerer'
    UNION ALL SELECT 'administrateur_general_societe', 'module.acceder'
    UNION ALL SELECT 'administrateur_general_societe', 'notification.consulter'
    UNION ALL SELECT 'administrateur_general_societe', 'validation.gerer'
) src
JOIN sav_roles r ON r.rol_code = src.role_code
JOIN sav_permissions p ON p.per_code = src.permission_code
WHERE NOT EXISTS (
    SELECT 1 FROM sav_roles_permissions rp
    WHERE rp.rpe_role_id = r.rol_id AND rp.rpe_permission_id = p.per_id
);
