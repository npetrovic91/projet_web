-- ============================================================
-- AUTOSAV — Seeds L7 : Fonctions et Métiers
-- Fichier : database/seeds/functions_jobs_seed.sql
-- Rôle    : Données initiales pour sav_functions, sav_jobs,
--           sav_job_company_types
-- Convention : préfixe fnc_, job_, jct_
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. FONCTIONS GLOBALES (système — toutes structures)
-- ============================================================
INSERT INTO `sav_functions`
  (`fnc_code`, `fnc_label`, `fnc_description`, `fnc_company_id`, `fnc_is_global`, `fnc_is_active`, `fnc_created_by`, `fnc_created_at`, `fnc_updated_at`)
VALUES
  ('DIRECTEUR_GENERAL',     'Directeur Général',             'Direction générale de la structure',                NULL, 1, 1, NULL, NOW(), NOW()),
  ('DIRECTEUR_COMMERCIAL',  'Directeur Commercial',          'Responsable de l\'activité commerciale',            NULL, 1, 1, NULL, NOW(), NOW()),
  ('DIRECTEUR_APRES_VENTE', 'Directeur Après-Vente',         'Responsable de l\'activité après-vente',            NULL, 1, 1, NULL, NOW(), NOW()),
  ('RESPONSABLE_RH',        'Responsable Ressources Humaines','Gestion des ressources humaines',                  NULL, 1, 1, NULL, NOW(), NOW()),
  ('RESPONSABLE_MARKETING', 'Responsable Marketing',         'Stratégie et actions marketing',                    NULL, 1, 1, NULL, NOW(), NOW()),
  ('RESPONSABLE_COMPTABLE', 'Responsable Comptable',         'Gestion de la comptabilité et finances',            NULL, 1, 1, NULL, NOW(), NOW()),
  ('RESPONSABLE_QUALITE',   'Responsable Qualité',           'Gestion de la qualité et conformité',               NULL, 1, 1, NULL, NOW(), NOW()),
  ('CHEF_ATELIER',          'Chef d\'Atelier',               'Responsable de l\'atelier mécanique',               NULL, 1, 1, NULL, NOW(), NOW()),
  ('CHEF_VENTES',           'Chef des Ventes',               'Responsable de l\'équipe commerciale',              NULL, 1, 1, NULL, NOW(), NOW()),
  ('GESTIONNAIRE_PARC',     'Gestionnaire de Parc',          'Gestion du parc de véhicules',                      NULL, 1, 1, NULL, NOW(), NOW()),
  ('CHARGE_FORMATION',      'Chargé(e) de Formation',        'Organisation et suivi des formations',              NULL, 1, 1, NULL, NOW(), NOW()),
  ('ADMINISTRATEUR_RESEAU', 'Administrateur Réseau',         'Administration technique réseau informatique',       NULL, 1, 1, NULL, NOW(), NOW()),
  ('CHARGE_COMMUNICATION',  'Chargé(e) de Communication',   'Communication interne et externe',                   NULL, 1, 1, NULL, NOW(), NOW()),
  ('SECRETAIRE_DIRECTION',  'Secrétaire de Direction',       'Assistanat de la direction',                        NULL, 1, 1, NULL, NOW(), NOW()),
  ('COORDINATEUR',          'Coordinateur / Coordinatrice',  'Coordination des activités inter-services',          NULL, 1, 1, NULL, NOW(), NOW());

-- ============================================================
-- 2. MÉTIERS GLOBAUX (communs à tous les types d'entreprises)
-- ============================================================
INSERT INTO `sav_jobs`
  (`job_code`, `job_label`, `job_description`, `job_is_global`, `job_created_by_company_id`, `job_is_active`, `job_created_at`, `job_updated_at`)
VALUES
  -- Métiers communs tous types
  ('COMMERCIAL_VN',         'Commercial Véhicules Neufs',        'Vente de véhicules neufs',                    1, NULL, 1, NOW(), NOW()),
  ('COMMERCIAL_VO',         'Commercial Véhicules Occasion',     'Vente de véhicules d\'occasion',              1, NULL, 1, NOW(), NOW()),
  ('CONSEILLER_SERVICE',    'Conseiller Service / Réceptionnaire','Accueil et prise en charge des clients à l\'atelier', 1, NULL, 1, NOW(), NOW()),
  ('MECANICIEN_VL',         'Mécanicien Véhicules Légers',       'Réparation et entretien de véhicules légers', 1, NULL, 1, NOW(), NOW()),
  ('ELECTRICIEN_AUTO',      'Électricien Automobile',            'Diagnostic et réparation électronique/électrique', 1, NULL, 1, NOW(), NOW()),
  ('CARROSSIER_PEINTRE',    'Carrossier-Peintre',                'Remise en état carrosserie et peinture',      1, NULL, 1, NOW(), NOW()),
  ('TECHNICIEN_DIAG',       'Technicien Diagnostic',             'Diagnostic et maintenance électronique avancée', 1, NULL, 1, NOW(), NOW()),
  ('MAGASINIER_PIECES',     'Magasinier Pièces de Rechange',     'Gestion du stock de pièces détachées',        1, NULL, 1, NOW(), NOW()),
  ('COMPTABLE',             'Comptable',                         'Tenue de la comptabilité',                    1, NULL, 1, NOW(), NOW()),
  ('ADMINISTRATIF',         'Agent Administratif',               'Gestion administrative courante',             1, NULL, 1, NOW(), NOW()),
  ('CHARGE_GARANTIE',       'Chargé(e) de Garantie',            'Gestion et suivi des dossiers de garantie',   1, NULL, 1, NOW(), NOW()),
  ('ANIMATEUR_VENTES',      'Animateur des Ventes',              'Animation commerciale et incentives',         1, NULL, 1, NOW(), NOW()),
  ('FORMATEUR_TECHNIQUE',   'Formateur Technique',               'Formation technique des équipes',             1, NULL, 1, NOW(), NOW()),
  ('INFORMATICIEN',         'Informaticien / Technicien IT',     'Support et maintenance informatique',         1, NULL, 1, NOW(), NOW());

-- ============================================================
-- 3. MÉTIERS SPÉCIFIQUES (restreints à certains types)
-- ============================================================

-- 3.1 Métiers spécifiques CONSTRUCTEUR / IMPORTATEUR
INSERT INTO `sav_jobs`
  (`job_code`, `job_label`, `job_description`, `job_is_global`, `job_created_by_company_id`, `job_is_active`, `job_created_at`, `job_updated_at`)
VALUES
  ('CHEF_PRODUIT',          'Chef de Produit',                   'Gestion et développement d\'une ligne de produits', 0, NULL, 1, NOW(), NOW()),
  ('ANALYSTE_RESEAU',       'Analyste Réseau',                   'Analyse et développement du réseau de distribution', 0, NULL, 1, NOW(), NOW()),
  ('RESPONSABLE_HOMOLOG',   'Responsable Homologation',          'Gestion des homologations et certifications produits', 0, NULL, 1, NOW(), NOW()),
  ('CHARGE_STANDARD',       'Chargé(e) Standards et Process',   'Définition et contrôle des standards opérationnels', 0, NULL, 1, NOW(), NOW());

-- 3.2 Métiers spécifiques CONCESSIONNAIRE
INSERT INTO `sav_jobs`
  (`job_code`, `job_label`, `job_description`, `job_is_global`, `job_created_by_company_id`, `job_is_active`, `job_created_at`, `job_updated_at`)
VALUES
  ('PREPARATEUR_VH',        'Préparateur de Véhicules',          'Préparation des véhicules avant livraison',   0, NULL, 1, NOW(), NOW()),
  ('LIVREUR_VH',            'Livreur Automobile',                'Livraison des véhicules aux clients',         0, NULL, 1, NOW(), NOW()),
  ('GESTIONNAIRE_STOCK',    'Gestionnaire de Stock',             'Gestion du stock VO/VN',                      0, NULL, 1, NOW(), NOW()),
  ('TELECONSEILLER_APV',    'Téléconseiller APV',               'Prise de rendez-vous et suivi atelier',       0, NULL, 1, NOW(), NOW());

-- 3.3 Métiers spécifiques RÉPARATEUR / INDÉPENDANT
INSERT INTO `sav_jobs`
  (`job_code`, `job_label`, `job_description`, `job_is_global`, `job_created_by_company_id`, `job_is_active`, `job_created_at`, `job_updated_at`)
VALUES
  ('MECANICIEN_PL',         'Mécanicien Poids Lourds',           'Réparation et entretien de poids lourds',     0, NULL, 1, NOW(), NOW()),
  ('TECHNICIEN_CLIMATISATION','Technicien Climatisation',        'Maintenance climatisation et systèmes de froid', 0, NULL, 1, NOW(), NOW()),
  ('MONTEUR_PNEUMATIQUES',  'Monteur Pneumatiques',              'Montage et équilibrage de pneumatiques',      0, NULL, 1, NOW(), NOW());

-- ============================================================
-- 4. RESTRICTIONS PAR TYPE D'ENTREPRISE (sav_job_company_types)
--    Récupère les IDs des types depuis sav_company_types
--    et les IDs des jobs depuis sav_jobs
-- ============================================================

-- Correspondances types (à adapter si les IDs diffèrent) :
-- ctp_code = CONSTRUCTEUR  → à récupérer dynamiquement
-- ctp_code = IMPORTATEUR
-- ctp_code = CONCESSIONNAIRE
-- ctp_code = REPARATEUR
-- ctp_code = INDEPENDANT

-- 4.1 CHEF_PRODUIT → CONSTRUCTEUR, IMPORTATEUR
INSERT INTO `sav_job_company_types` (`jct_job_id`, `jct_company_type_id`, `jct_created_at`)
SELECT j.job_id, ct.ctp_id, NOW()
FROM sav_jobs j
CROSS JOIN sav_company_types ct
WHERE j.job_code = 'CHEF_PRODUIT'
  AND ct.ctp_code IN ('CONSTRUCTEUR', 'IMPORTATEUR');

-- 4.2 ANALYSTE_RESEAU → CONSTRUCTEUR, IMPORTATEUR
INSERT INTO `sav_job_company_types` (`jct_job_id`, `jct_company_type_id`, `jct_created_at`)
SELECT j.job_id, ct.ctp_id, NOW()
FROM sav_jobs j
CROSS JOIN sav_company_types ct
WHERE j.job_code = 'ANALYSTE_RESEAU'
  AND ct.ctp_code IN ('CONSTRUCTEUR', 'IMPORTATEUR');

-- 4.3 RESPONSABLE_HOMOLOG → CONSTRUCTEUR, IMPORTATEUR
INSERT INTO `sav_job_company_types` (`jct_job_id`, `jct_company_type_id`, `jct_created_at`)
SELECT j.job_id, ct.ctp_id, NOW()
FROM sav_jobs j
CROSS JOIN sav_company_types ct
WHERE j.job_code = 'RESPONSABLE_HOMOLOG'
  AND ct.ctp_code IN ('CONSTRUCTEUR', 'IMPORTATEUR');

-- 4.4 CHARGE_STANDARD → CONSTRUCTEUR, IMPORTATEUR
INSERT INTO `sav_job_company_types` (`jct_job_id`, `jct_company_type_id`, `jct_created_at`)
SELECT j.job_id, ct.ctp_id, NOW()
FROM sav_jobs j
CROSS JOIN sav_company_types ct
WHERE j.job_code = 'CHARGE_STANDARD'
  AND ct.ctp_code IN ('CONSTRUCTEUR', 'IMPORTATEUR');

-- 4.5 PREPARATEUR_VH, LIVREUR_VH, GESTIONNAIRE_STOCK, TELECONSEILLER_APV → CONCESSIONNAIRE
INSERT INTO `sav_job_company_types` (`jct_job_id`, `jct_company_type_id`, `jct_created_at`)
SELECT j.job_id, ct.ctp_id, NOW()
FROM sav_jobs j
CROSS JOIN sav_company_types ct
WHERE j.job_code IN ('PREPARATEUR_VH', 'LIVREUR_VH', 'GESTIONNAIRE_STOCK', 'TELECONSEILLER_APV')
  AND ct.ctp_code = 'CONCESSIONNAIRE';

-- 4.6 Métiers réparateur → REPARATEUR, INDEPENDANT
INSERT INTO `sav_job_company_types` (`jct_job_id`, `jct_company_type_id`, `jct_created_at`)
SELECT j.job_id, ct.ctp_id, NOW()
FROM sav_jobs j
CROSS JOIN sav_company_types ct
WHERE j.job_code IN ('MECANICIEN_PL', 'TECHNICIEN_CLIMATISATION', 'MONTEUR_PNEUMATIQUES')
  AND ct.ctp_code IN ('REPARATEUR', 'INDEPENDANT');

-- ============================================================
-- 5. MIGRATION TRACKING
-- ============================================================
INSERT INTO `sav_migrations` (`mgr_filename`, `mgr_batch`, `mgr_applied_at`)
VALUES ('L7_functions_jobs_seed.sql', 7, NOW())
ON DUPLICATE KEY UPDATE mgr_batch = mgr_batch;

SET FOREIGN_KEY_CHECKS = 1;
