-- ============================================================
-- AUTOSAV — Migration 0001 : schéma de référence (baseline)
-- ------------------------------------------------------------
-- Schéma uniquement (139 tables, contraintes, déclencheurs),
-- sans aucune donnée : extrait du dump de production
-- u166513890_base au 2026-06-25, qui était jusqu'ici la seule
-- trace versionnée du schéma (database/migrations/ était vide).
--
-- Ce fichier marque le point de départ du suivi de version. Toute
-- évolution future du schéma doit passer par un nouveau fichier
-- numéroté (0002_xxx.sql, 0003_xxx.sql...) appliqué via
-- `php bin/migrate.php`, jamais par modification directe en prod
-- sans migration correspondante.
--
-- Ne PAS modifier ce fichier une fois appliqué quelque part : créer
-- une migration corrective à la place (bin/migrate.php avertit si
-- le contenu d'une migration déjà appliquée a changé).
-- ============================================================

-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 25 juin 2026 à 15:18
-- Version du serveur : 11.8.6-MariaDB-log
-- Version de PHP : 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `u166513890_base`
--

-- --------------------------------------------------------

--
-- Structure de la table `sav_abonnements_evenements`
--

CREATE TABLE `sav_abonnements_evenements` (
  `abe_id` bigint(20) UNSIGNED NOT NULL,
  `abe_code_evenement` varchar(120) NOT NULL,
  `abe_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abe_nom` varchar(160) NOT NULL,
  `abe_action_type` varchar(60) NOT NULL,
  `abe_action_configuration_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`abe_action_configuration_json`)),
  `abe_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `abe_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abe_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `abe_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `abe_supprime_le` datetime DEFAULT NULL,
  `abe_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abe_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abe_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Abonnements internes aux événements applicatifs';

-- --------------------------------------------------------

--
-- Structure de la table `sav_abonnements_societes`
--

CREATE TABLE `sav_abonnements_societes` (
  `abo_id` bigint(20) UNSIGNED NOT NULL,
  `abo_societe_id` bigint(20) UNSIGNED NOT NULL,
  `abo_formule_abonnement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abo_statut_abonnement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abo_statut_paiement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abo_debute_le` datetime DEFAULT NULL,
  `abo_termine_le` datetime DEFAULT NULL,
  `abo_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `abo_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `abo_supprime_le` datetime DEFAULT NULL,
  `abo_archive_le` datetime DEFAULT NULL,
  `abo_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abo_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abo_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `abo_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Abonnements des sociétés, distincts de la fiche société';

--
-- Déchargement des données de la table `sav_abonnements_societes`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_acceptations_documents_juridiques_utilisateurs`
--

CREATE TABLE `sav_acceptations_documents_juridiques_utilisateurs` (
  `adj_id` bigint(20) UNSIGNED NOT NULL,
  `adj_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `adj_document_juridique_id` bigint(20) UNSIGNED NOT NULL,
  `adj_version` varchar(30) NOT NULL,
  `adj_accepte_le` datetime NOT NULL,
  `adj_adresse_ip` varbinary(16) DEFAULT NULL,
  `adj_user_agent` varchar(255) DEFAULT NULL,
  `adj_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Acceptations historisées des documents juridiques';

-- --------------------------------------------------------

--
-- Structure de la table `sav_adhesions_utilisateurs_societes`
--

CREATE TABLE `sav_adhesions_utilisateurs_societes` (
  `aus_id` bigint(20) UNSIGNED NOT NULL,
  `aus_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `aus_societe_id` bigint(20) UNSIGNED NOT NULL,
  `aus_debute_le` date NOT NULL,
  `aus_termine_le` date DEFAULT NULL,
  `aus_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `aus_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `aus_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `aus_supprime_le` datetime DEFAULT NULL,
  `aus_archive_le` datetime DEFAULT NULL,
  `aus_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `aus_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `aus_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `aus_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Appartenances des utilisateurs aux sociétés';

--
-- Déchargement des données de la table `sav_adhesions_utilisateurs_societes`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_affectations_etiquettes`
--

CREATE TABLE `sav_affectations_etiquettes` (
  `afe_id` bigint(20) UNSIGNED NOT NULL,
  `afe_etiquette_id` bigint(20) UNSIGNED NOT NULL,
  `afe_cible_type` varchar(120) NOT NULL,
  `afe_cible_id` bigint(20) UNSIGNED NOT NULL,
  `afe_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `afe_supprime_le` datetime DEFAULT NULL,
  `afe_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `afe_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Affectations génériques des étiquettes';

-- --------------------------------------------------------

--
-- Structure de la table `sav_affectations_types_societes`
--

CREATE TABLE `sav_affectations_types_societes` (
  `ats_id` bigint(20) UNSIGNED NOT NULL,
  `ats_societe_id` bigint(20) UNSIGNED NOT NULL,
  `ats_type_societe_id` bigint(20) UNSIGNED NOT NULL,
  `ats_debute_le` date NOT NULL,
  `ats_termine_le` date DEFAULT NULL,
  `ats_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ats_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `ats_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ats_supprime_le` datetime DEFAULT NULL,
  `ats_archive_le` datetime DEFAULT NULL,
  `ats_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ats_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ats_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ats_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Affectation historisée des types aux sociétés';

--
-- Déchargement des données de la table `sav_affectations_types_societes`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_evenements_application`
--

CREATE TABLE `sav_archives_evenements_application` (
  `eva_id` bigint(20) UNSIGNED NOT NULL,
  `eva_uuid` char(36) NOT NULL,
  `eva_code` varchar(120) NOT NULL,
  `eva_nom` varchar(160) NOT NULL,
  `eva_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eva_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eva_emetteur_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eva_cible_type` varchar(120) DEFAULT NULL,
  `eva_cible_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eva_donnees_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`eva_donnees_json`)),
  `eva_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eva_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `eva_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `eva_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journal applicatif des événements métier et noyau';

-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_historique_contextes_utilisateurs`
--

CREATE TABLE `sav_archives_historique_contextes_utilisateurs` (
  `hcu_id` bigint(20) UNSIGNED NOT NULL,
  `hcu_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `hcu_session_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_concession_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_marque_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_service_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_equipe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_action` varchar(80) NOT NULL,
  `hcu_metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hcu_metadata_json`)),
  `hcu_adresse_ip` varbinary(16) DEFAULT NULL,
  `hcu_user_agent` varchar(255) DEFAULT NULL,
  `hcu_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des changements de contexte utilisateur';

-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_journaux_acces_donnees_sensibles`
--

CREATE TABLE `sav_archives_journaux_acces_donnees_sensibles` (
  `jad_id` bigint(20) UNSIGNED NOT NULL,
  `jad_utilisateur_source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jad_utilisateur_cible_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jad_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jad_donnee_sensible_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jad_action` varchar(80) NOT NULL,
  `jad_champs_consultes_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jad_champs_consultes_json`)),
  `jad_raison` text DEFAULT NULL,
  `jad_adresse_ip` varbinary(16) DEFAULT NULL,
  `jad_user_agent` varchar(500) DEFAULT NULL,
  `jad_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit spécifique des accès aux données sensibles';

-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_journaux_audit`
--

CREATE TABLE `sav_archives_journaux_audit` (
  `jau_id` bigint(20) UNSIGNED NOT NULL,
  `jau_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jau_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jau_action` varchar(120) NOT NULL,
  `jau_table_cible` varchar(120) DEFAULT NULL,
  `jau_id_cible` bigint(20) UNSIGNED DEFAULT NULL,
  `jau_raison` varchar(255) DEFAULT NULL,
  `jau_adresse_ip` varbinary(16) DEFAULT NULL,
  `jau_user_agent` varchar(255) DEFAULT NULL,
  `jau_metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jau_metadata_json`)),
  `jau_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journaux d’audit actifs';

-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_journaux_emails`
--

CREATE TABLE `sav_archives_journaux_emails` (
  `jme_id` bigint(20) UNSIGNED NOT NULL,
  `jme_modele_email_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jme_societe_expediteur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jme_societe_destinataire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jme_utilisateur_destinataire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jme_email_destinataire` varchar(191) DEFAULT NULL,
  `jme_type_evenement` varchar(120) DEFAULT NULL,
  `jme_sujet` varchar(255) NOT NULL,
  `jme_corps` mediumtext DEFAULT NULL,
  `jme_envoye_le` datetime DEFAULT NULL,
  `jme_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jme_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des emails envoyés';

-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_journaux_rgpd`
--

CREATE TABLE `sav_archives_journaux_rgpd` (
  `jrg_id` bigint(20) UNSIGNED NOT NULL,
  `jrg_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jrg_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jrg_action` varchar(120) NOT NULL,
  `jrg_base_legale` varchar(120) DEFAULT NULL,
  `jrg_table_cible` varchar(120) DEFAULT NULL,
  `jrg_id_cible` bigint(20) UNSIGNED DEFAULT NULL,
  `jrg_metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jrg_metadata_json`)),
  `jrg_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journal RGPD';

-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_journaux_systeme`
--

CREATE TABLE `sav_archives_journaux_systeme` (
  `jsy_id` bigint(20) UNSIGNED NOT NULL,
  `jsy_niveau` varchar(30) NOT NULL,
  `jsy_categorie` varchar(80) NOT NULL,
  `jsy_message` text NOT NULL,
  `jsy_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jsy_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jsy_adresse_ip` varbinary(16) DEFAULT NULL,
  `jsy_user_agent` varchar(255) DEFAULT NULL,
  `jsy_metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jsy_metadata_json`)),
  `jsy_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journaux système actifs';

-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_journaux_webhooks`
--

CREATE TABLE `sav_archives_journaux_webhooks` (
  `jwh_id` bigint(20) UNSIGNED NOT NULL,
  `jwh_webhook_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jwh_evenement_application_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jwh_url` varchar(500) NOT NULL,
  `jwh_methode` varchar(10) NOT NULL,
  `jwh_code_http` smallint(5) UNSIGNED DEFAULT NULL,
  `jwh_requete_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jwh_requete_json`)),
  `jwh_reponse_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jwh_reponse_json`)),
  `jwh_succes` tinyint(1) UNSIGNED DEFAULT NULL,
  `jwh_message_erreur` text DEFAULT NULL,
  `jwh_duree_ms` int(10) UNSIGNED DEFAULT NULL,
  `jwh_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journaux d’exécution des webhooks';

-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_sessions_utilisateurs`
--

CREATE TABLE `sav_archives_sessions_utilisateurs` (
  `seu_id` bigint(20) UNSIGNED NOT NULL,
  `seu_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `seu_identifiant_session_hash` char(64) NOT NULL,
  `seu_societe_active_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_concession_active_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_marque_active_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_service_actif_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_equipe_active_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_adresse_ip` varbinary(16) DEFAULT NULL,
  `seu_user_agent` varchar(255) DEFAULT NULL,
  `seu_derniere_activite_le` datetime NOT NULL,
  `seu_expire_le` datetime NOT NULL,
  `seu_revoquee_le` datetime DEFAULT NULL,
  `seu_motif_revocation` varchar(255) DEFAULT NULL,
  `seu_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `seu_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `seu_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions applicatives persistantes';

-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_tentatives_connexion`
--

CREATE TABLE `sav_archives_tentatives_connexion` (
  `tcn_id` bigint(20) UNSIGNED NOT NULL,
  `tcn_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tcn_email_tente` varchar(191) DEFAULT NULL,
  `tcn_email_normalise` varchar(191) DEFAULT NULL,
  `tcn_adresse_ip` varbinary(16) DEFAULT NULL,
  `tcn_user_agent` varchar(255) DEFAULT NULL,
  `tcn_navigateur` varchar(120) DEFAULT NULL,
  `tcn_appareil` varchar(120) DEFAULT NULL,
  `tcn_succes` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `tcn_raison_echec` varchar(255) DEFAULT NULL,
  `tcn_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tentatives de connexion';

-- --------------------------------------------------------

--
-- Structure de la table `sav_archives_traitements_evenements`
--

CREATE TABLE `sav_archives_traitements_evenements` (
  `tev_id` bigint(20) UNSIGNED NOT NULL,
  `tev_file_evenement_id` bigint(20) UNSIGNED NOT NULL,
  `tev_demarre_le` datetime NOT NULL DEFAULT current_timestamp(),
  `tev_termine_le` datetime DEFAULT NULL,
  `tev_succes` tinyint(1) UNSIGNED DEFAULT NULL,
  `tev_message` text DEFAULT NULL,
  `tev_duree_ms` int(10) UNSIGNED DEFAULT NULL,
  `tev_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des traitements de la file d’événements';

-- --------------------------------------------------------

--
-- Structure de la table `sav_blocages_securite`
--

CREATE TABLE `sav_blocages_securite` (
  `bse_id` bigint(20) UNSIGNED NOT NULL,
  `bse_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bse_adresse_ip` varbinary(16) DEFAULT NULL,
  `bse_niveau_blocage` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `bse_raison` varchar(255) NOT NULL,
  `bse_commence_le` datetime NOT NULL,
  `bse_termine_le` datetime DEFAULT NULL,
  `bse_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bse_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `bse_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `bse_supprime_le` datetime DEFAULT NULL,
  `bse_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bse_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bse_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Blocages de sécurité progressifs';

-- --------------------------------------------------------

--
-- Structure de la table `sav_canaux_notifications`
--

CREATE TABLE `sav_canaux_notifications` (
  `cno_id` bigint(20) UNSIGNED NOT NULL,
  `cno_code` varchar(60) NOT NULL,
  `cno_nom` varchar(120) NOT NULL,
  `cno_description` text DEFAULT NULL,
  `cno_est_systeme` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `cno_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cno_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cno_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cno_supprime_le` datetime DEFAULT NULL,
  `cno_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cno_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cno_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Canaux disponibles pour les notifications';

--
-- Déchargement des données de la table `sav_canaux_notifications`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_certifications`
--

CREATE TABLE `sav_certifications` (
  `cer_id` bigint(20) UNSIGNED NOT NULL,
  `cer_code` varchar(100) NOT NULL,
  `cer_nom` varchar(160) NOT NULL,
  `cer_description` text DEFAULT NULL,
  `cer_prerequis_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cer_prerequis_json`)),
  `cer_societe_proprietaire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cer_portee_code` enum('interne','reseau','plateforme') NOT NULL DEFAULT 'interne',
  `cer_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cer_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `cer_supprime_le` is null and `cer_archive_le` is null then 1 else NULL end) VIRTUAL,
  `cer_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cer_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cer_supprime_le` datetime DEFAULT NULL,
  `cer_archive_le` datetime DEFAULT NULL,
  `cer_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cer_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cer_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cer_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certifications';

--
-- Déchargement des données de la table `sav_certifications`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_certifications_utilisateurs`
--

CREATE TABLE `sav_certifications_utilisateurs` (
  `ceu_id` bigint(20) UNSIGNED NOT NULL,
  `ceu_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `ceu_certification_id` bigint(20) UNSIGNED NOT NULL,
  `ceu_societe_id` bigint(20) UNSIGNED NOT NULL,
  `ceu_marque_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ceu_delivree_par_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ceu_delivree_le` date DEFAULT NULL,
  `ceu_expire_le` date DEFAULT NULL,
  `ceu_preuve_fichier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ceu_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ceu_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `ceu_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ceu_supprime_le` datetime DEFAULT NULL,
  `ceu_archive_le` datetime DEFAULT NULL,
  `ceu_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ceu_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ceu_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ceu_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certifications des utilisateurs';

-- --------------------------------------------------------

--
-- Structure de la table `sav_cles_api`
--

CREATE TABLE `sav_cles_api` (
  `cap_id` bigint(20) UNSIGNED NOT NULL,
  `cap_uuid` char(36) NOT NULL,
  `cap_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cap_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cap_connecteur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cap_nom` varchar(160) NOT NULL,
  `cap_prefixe_public` varchar(40) NOT NULL,
  `cap_hash_secret` char(64) NOT NULL,
  `cap_portees_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cap_portees_json`)),
  `cap_derniere_utilisation_le` datetime DEFAULT NULL,
  `cap_expire_le` datetime DEFAULT NULL,
  `cap_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cap_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cap_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cap_revoque_le` datetime DEFAULT NULL,
  `cap_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cap_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cap_revoque_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clés API hashées, jamais stockées en clair';

-- --------------------------------------------------------

--
-- Structure de la table `sav_codes_remise`
--

CREATE TABLE `sav_codes_remise` (
  `cre_id` bigint(20) UNSIGNED NOT NULL,
  `cre_code` varchar(20) NOT NULL,
  `cre_libelle` varchar(150) NOT NULL,
  `cre_taux_pourcentage` decimal(5,2) DEFAULT NULL,
  `cre_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `cre_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cre_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cre_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Référentiel : code remise (ex. 0 / STANDARD)';

--
-- Déchargement des données de la table `sav_codes_remise`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_competences`
--

CREATE TABLE `sav_competences` (
  `cmp_id` bigint(20) UNSIGNED NOT NULL,
  `cmp_code` varchar(100) NOT NULL,
  `cmp_nom` varchar(160) NOT NULL,
  `cmp_description` text DEFAULT NULL,
  `cmp_societe_proprietaire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cmp_portee_code` enum('interne','reseau','plateforme') NOT NULL DEFAULT 'interne',
  `cmp_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cmp_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `cmp_supprime_le` is null and `cmp_archive_le` is null then 1 else NULL end) VIRTUAL,
  `cmp_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cmp_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cmp_supprime_le` datetime DEFAULT NULL,
  `cmp_archive_le` datetime DEFAULT NULL,
  `cmp_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cmp_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cmp_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cmp_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Compétences métier';

--
-- Déchargement des données de la table `sav_competences`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_competences_utilisateurs`
--

CREATE TABLE `sav_competences_utilisateurs` (
  `cut_id` bigint(20) UNSIGNED NOT NULL,
  `cut_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `cut_competence_id` bigint(20) UNSIGNED NOT NULL,
  `cut_niveau_competence_id` bigint(20) UNSIGNED NOT NULL,
  `cut_societe_id` bigint(20) UNSIGNED NOT NULL,
  `cut_marque_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cut_delivree_par_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cut_debute_le` date DEFAULT NULL,
  `cut_termine_le` date DEFAULT NULL,
  `cut_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cut_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cut_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cut_supprime_le` datetime DEFAULT NULL,
  `cut_archive_le` datetime DEFAULT NULL,
  `cut_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cut_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cut_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cut_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Compétences affectées aux utilisateurs';

--
-- Déchargement des données de la table `sav_competences_utilisateurs`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_conditions_politiques_acces`
--

CREATE TABLE `sav_conditions_politiques_acces` (
  `cpa_id` bigint(20) UNSIGNED NOT NULL,
  `cpa_politique_acces_id` bigint(20) UNSIGNED NOT NULL,
  `cpa_attribut` varchar(120) NOT NULL,
  `cpa_operateur` varchar(40) NOT NULL,
  `cpa_valeur_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cpa_valeur_json`)),
  `cpa_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cpa_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cpa_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Conditions dynamiques des politiques ABAC';

-- --------------------------------------------------------

--
-- Structure de la table `sav_connecteurs`
--

CREATE TABLE `sav_connecteurs` (
  `con_id` bigint(20) UNSIGNED NOT NULL,
  `con_uuid` char(36) NOT NULL,
  `con_code` varchar(100) NOT NULL,
  `con_nom` varchar(160) NOT NULL,
  `con_type` varchar(80) NOT NULL,
  `con_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `con_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `con_configuration_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`con_configuration_json`)),
  `con_secret_chiffre` varbinary(4096) DEFAULT NULL,
  `con_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `con_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `con_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `con_supprime_le` datetime DEFAULT NULL,
  `con_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `con_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `con_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Connecteurs externes ou inter-applicatifs';

-- --------------------------------------------------------

--
-- Structure de la table `sav_connecteurs_modules`
--

CREATE TABLE `sav_connecteurs_modules` (
  `cmo_id` bigint(20) UNSIGNED NOT NULL,
  `cmo_module_source_id` bigint(20) UNSIGNED NOT NULL,
  `cmo_module_cible_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cmo_code` varchar(100) NOT NULL,
  `cmo_nom` varchar(160) NOT NULL,
  `cmo_configuration_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cmo_configuration_json`)),
  `cmo_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cmo_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cmo_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cmo_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Connecteurs entre modules';

-- --------------------------------------------------------

--
-- Structure de la table `sav_consentements_rgpd`
--

CREATE TABLE `sav_consentements_rgpd` (
  `crg_id` bigint(20) UNSIGNED NOT NULL,
  `crg_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `crg_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `crg_finalite` varchar(120) NOT NULL,
  `crg_version` varchar(30) NOT NULL,
  `crg_est_accepte` tinyint(1) UNSIGNED NOT NULL,
  `crg_accepte_le` datetime DEFAULT NULL,
  `crg_retire_le` datetime DEFAULT NULL,
  `crg_adresse_ip` varbinary(16) DEFAULT NULL,
  `crg_user_agent` varchar(255) DEFAULT NULL,
  `crg_preuve_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`crg_preuve_json`)),
  `crg_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `crg_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Consentements RGPD par finalité';

-- --------------------------------------------------------

--
-- Structure de la table `sav_contacts_societes`
--

CREATE TABLE `sav_contacts_societes` (
  `cts_id` bigint(20) UNSIGNED NOT NULL,
  `cts_societe_id` bigint(20) UNSIGNED NOT NULL,
  `cts_etablissement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cts_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cts_email_externe` varchar(191) DEFAULT NULL,
  `cts_civilite` varchar(10) DEFAULT NULL,
  `cts_prenom` varchar(100) DEFAULT NULL,
  `cts_nom` varchar(100) DEFAULT NULL,
  `cts_fonction` varchar(100) DEFAULT NULL,
  `cts_service` varchar(100) DEFAULT NULL,
  `cts_telephone` varchar(20) DEFAULT NULL,
  `cts_telephone_direct` varchar(20) DEFAULT NULL,
  `cts_mobile` varchar(20) DEFAULT NULL,
  `cts_est_principal` tinyint(1) NOT NULL DEFAULT 0,
  `cts_ordre` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `cts_type_contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cts_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cts_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cts_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cts_supprime_le` datetime DEFAULT NULL,
  `cts_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cts_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cts_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contacts internes ou externes des sociétés';

-- --------------------------------------------------------

--
-- Structure de la table `sav_contenus_fichiers`
--

CREATE TABLE `sav_contenus_fichiers` (
  `cfi_id` bigint(20) UNSIGNED NOT NULL,
  `cfi_fichier_id` bigint(20) UNSIGNED NOT NULL,
  `cfi_contenu_blob` longblob NOT NULL,
  `cfi_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cfi_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contenu binaire séparé des fichiers';

-- --------------------------------------------------------

--
-- Structure de la table `sav_controles_qualite_base`
--

CREATE TABLE `sav_controles_qualite_base` (
  `cqb_id` bigint(20) UNSIGNED NOT NULL,
  `cqb_code` varchar(100) NOT NULL,
  `cqb_nom` varchar(180) NOT NULL,
  `cqb_description` text DEFAULT NULL,
  `cqb_requete_sql` text NOT NULL,
  `cqb_severite` varchar(30) NOT NULL DEFAULT 'information',
  `cqb_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `cqb_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `cqb_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catalogue des controles qualite de la base';

--
-- Déchargement des données de la table `sav_controles_qualite_base`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_demandes_rgpd`
--

CREATE TABLE `sav_demandes_rgpd` (
  `drg_id` bigint(20) UNSIGNED NOT NULL,
  `drg_uuid` char(36) NOT NULL,
  `drg_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `drg_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `drg_type_demande` varchar(80) NOT NULL,
  `drg_email_contact` varchar(191) DEFAULT NULL,
  `drg_description` text DEFAULT NULL,
  `drg_reponse` text DEFAULT NULL,
  `drg_traitee_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `drg_traitee_le` datetime DEFAULT NULL,
  `drg_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `drg_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `drg_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `drg_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Demandes RGPD : accès, rectification, effacement, export, opposition';

--
-- Déchargement des données de la table `sav_demandes_rgpd`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_demandes_validation`
--

CREATE TABLE `sav_demandes_validation` (
  `dva_id` bigint(20) UNSIGNED NOT NULL,
  `dva_uuid` char(36) NOT NULL,
  `dva_type_demande` varchar(80) NOT NULL,
  `dva_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dva_demandeur_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `dva_validateur_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dva_table_cible` varchar(120) DEFAULT NULL,
  `dva_id_cible` bigint(20) UNSIGNED DEFAULT NULL,
  `dva_donnees_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dva_donnees_json`)),
  `dva_motif` text DEFAULT NULL,
  `dva_decision` varchar(30) DEFAULT NULL,
  `dva_decision_le` datetime DEFAULT NULL,
  `dva_commentaire_decision` text DEFAULT NULL,
  `dva_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dva_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `dva_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `dva_supprime_le` datetime DEFAULT NULL,
  `dva_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dva_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dva_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Demandes de validation des opérations sensibles';

-- --------------------------------------------------------

--
-- Structure de la table `sav_departements`
--

CREATE TABLE `sav_departements` (
  `dep_id` bigint(20) UNSIGNED NOT NULL,
  `dep_societe_id` bigint(20) UNSIGNED NOT NULL,
  `dep_code` varchar(50) DEFAULT NULL,
  `dep_nom` varchar(120) NOT NULL,
  `dep_description` text DEFAULT NULL,
  `dep_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dep_responsable_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dep_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `dep_code` is not null and `dep_supprime_le` is null and `dep_archive_le` is null then 1 else NULL end) VIRTUAL,
  `dep_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `dep_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `dep_supprime_le` datetime DEFAULT NULL,
  `dep_archive_le` datetime DEFAULT NULL,
  `dep_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dep_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dep_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dep_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Départements internes des sociétés';

-- --------------------------------------------------------

--
-- Structure de la table `sav_departements_secteurs`
--

CREATE TABLE `sav_departements_secteurs` (
  `dse_id` bigint(20) UNSIGNED NOT NULL,
  `dse_societe_id` bigint(20) UNSIGNED NOT NULL,
  `dse_departement_id` bigint(20) UNSIGNED NOT NULL,
  `dse_secteur_id` bigint(20) UNSIGNED NOT NULL,
  `dse_debute_le` date NOT NULL,
  `dse_termine_le` date DEFAULT NULL,
  `dse_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dse_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `dse_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `dse_supprime_le` datetime DEFAULT NULL,
  `dse_archive_le` datetime DEFAULT NULL,
  `dse_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dse_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dse_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dse_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lien département / secteur';

-- --------------------------------------------------------

--
-- Structure de la table `sav_departements_services`
--

CREATE TABLE `sav_departements_services` (
  `dsv_id` bigint(20) UNSIGNED NOT NULL,
  `dsv_societe_id` bigint(20) UNSIGNED NOT NULL,
  `dsv_departement_id` bigint(20) UNSIGNED NOT NULL,
  `dsv_service_id` bigint(20) UNSIGNED NOT NULL,
  `dsv_debute_le` date NOT NULL,
  `dsv_termine_le` date DEFAULT NULL,
  `dsv_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dsv_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `dsv_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `dsv_supprime_le` datetime DEFAULT NULL,
  `dsv_archive_le` datetime DEFAULT NULL,
  `dsv_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dsv_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dsv_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dsv_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lien département / service';

-- --------------------------------------------------------

--
-- Structure de la table `sav_destinataires_notifications`
--

CREATE TABLE `sav_destinataires_notifications` (
  `dno_id` bigint(20) UNSIGNED NOT NULL,
  `dno_notification_id` bigint(20) UNSIGNED NOT NULL,
  `dno_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `dno_canal_notification_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dno_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dno_lu_le` datetime DEFAULT NULL,
  `dno_envoye_le` datetime DEFAULT NULL,
  `dno_echec_message` varchar(500) DEFAULT NULL,
  `dno_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `dno_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `dno_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Destinataires des notifications';

-- --------------------------------------------------------

--
-- Structure de la table `sav_devises`
--

CREATE TABLE `sav_devises` (
  `dev_id` bigint(20) UNSIGNED NOT NULL,
  `dev_code_iso` char(3) NOT NULL,
  `dev_nom` varchar(100) NOT NULL,
  `dev_symbole` varchar(10) DEFAULT NULL,
  `dev_nombre_decimales` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `dev_est_active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `dev_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `dev_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `dev_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Référentiel des devises';

--
-- Déchargement des données de la table `sav_devises`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_dirigeants_societes`
--

CREATE TABLE `sav_dirigeants_societes` (
  `dso_id` bigint(20) UNSIGNED NOT NULL,
  `dso_societe_id` bigint(20) UNSIGNED NOT NULL,
  `dso_civilite` varchar(10) DEFAULT NULL,
  `dso_prenom` varchar(100) DEFAULT NULL,
  `dso_nom` varchar(100) NOT NULL,
  `dso_fonction` varchar(100) DEFAULT NULL,
  `dso_date_prise_de_poste` date DEFAULT NULL,
  `dso_date_fin_de_poste` date DEFAULT NULL,
  `dso_telephone` varchar(20) DEFAULT NULL,
  `dso_email` varchar(150) DEFAULT NULL,
  `dso_est_dirigeant_principal` tinyint(1) NOT NULL DEFAULT 0,
  `dso_ordre` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `dso_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `dso_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `dso_supprime_le` datetime DEFAULT NULL,
  `dso_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dso_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dso_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sav_documents_juridiques`
--

CREATE TABLE `sav_documents_juridiques` (
  `dju_id` bigint(20) UNSIGNED NOT NULL,
  `dju_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dju_marque_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dju_type_document` varchar(80) NOT NULL,
  `dju_titre` varchar(160) NOT NULL,
  `dju_version` varchar(30) NOT NULL,
  `dju_contenu` mediumtext NOT NULL,
  `dju_valide_du` date DEFAULT NULL,
  `dju_valide_au` date DEFAULT NULL,
  `dju_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dju_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `dju_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `dju_supprime_le` datetime DEFAULT NULL,
  `dju_archive_le` datetime DEFAULT NULL,
  `dju_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dju_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dju_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dju_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documents juridiques versionnés';

-- --------------------------------------------------------

--
-- Structure de la table `sav_donnees_sensibles_utilisateurs`
--

CREATE TABLE `sav_donnees_sensibles_utilisateurs` (
  `dsu_id` bigint(20) UNSIGNED NOT NULL,
  `dsu_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `dsu_numero_securite_sociale_chiffre` varbinary(512) DEFAULT NULL,
  `dsu_iban_chiffre` varbinary(512) DEFAULT NULL,
  `dsu_bic_chiffre` varbinary(255) DEFAULT NULL,
  `dsu_banque_chiffree` varbinary(512) DEFAULT NULL,
  `dsu_numero_employe` varchar(50) DEFAULT NULL,
  `dsu_date_embauche` date DEFAULT NULL,
  `dsu_date_anciennete` date DEFAULT NULL,
  `dsu_type_contrat` varchar(60) DEFAULT NULL,
  `dsu_contrat_fichier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dsu_contact_urgence_nom_chiffre` varbinary(512) DEFAULT NULL,
  `dsu_contact_urgence_telephone_chiffre` varbinary(255) DEFAULT NULL,
  `dsu_donnees_chiffrees_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dsu_donnees_chiffrees_json`)),
  `dsu_derniere_consultation_le` datetime DEFAULT NULL,
  `dsu_derniere_consultation_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dsu_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `dsu_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `dsu_supprime_le` datetime DEFAULT NULL,
  `dsu_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dsu_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dsu_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Données utilisateur sensibles, à chiffrer applicativement';

-- --------------------------------------------------------

--
-- Structure de la table `sav_elements_menus`
--

CREATE TABLE `sav_elements_menus` (
  `eme_id` bigint(20) UNSIGNED NOT NULL,
  `eme_menu_id` bigint(20) UNSIGNED NOT NULL,
  `eme_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eme_parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eme_libelle` varchar(120) NOT NULL,
  `eme_route` varchar(160) DEFAULT NULL,
  `eme_icone` varchar(120) DEFAULT NULL,
  `eme_position` int(10) UNSIGNED NOT NULL DEFAULT 100,
  `eme_permission_requise_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eme_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eme_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `eme_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `eme_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Éléments des menus dynamiques';

-- --------------------------------------------------------

--
-- Structure de la table `sav_equipes`
--

CREATE TABLE `sav_equipes` (
  `equ_id` bigint(20) UNSIGNED NOT NULL,
  `equ_societe_id` bigint(20) UNSIGNED NOT NULL,
  `equ_code` varchar(50) DEFAULT NULL,
  `equ_nom` varchar(120) NOT NULL,
  `equ_description` text DEFAULT NULL,
  `equ_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `equ_responsable_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `equ_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `equ_code` is not null and `equ_supprime_le` is null and `equ_archive_le` is null then 1 else NULL end) VIRTUAL,
  `equ_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `equ_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `equ_supprime_le` datetime DEFAULT NULL,
  `equ_archive_le` datetime DEFAULT NULL,
  `equ_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `equ_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `equ_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `equ_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Équipes internes des sociétés';

-- --------------------------------------------------------

--
-- Structure de la table `sav_equipes_services`
--

CREATE TABLE `sav_equipes_services` (
  `eqs_id` bigint(20) UNSIGNED NOT NULL,
  `eqs_societe_id` bigint(20) UNSIGNED NOT NULL,
  `eqs_equipe_id` bigint(20) UNSIGNED NOT NULL,
  `eqs_service_id` bigint(20) UNSIGNED NOT NULL,
  `eqs_debute_le` date NOT NULL,
  `eqs_termine_le` date DEFAULT NULL,
  `eqs_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eqs_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `eqs_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `eqs_supprime_le` datetime DEFAULT NULL,
  `eqs_archive_le` datetime DEFAULT NULL,
  `eqs_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eqs_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eqs_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eqs_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lien équipe / service';

-- --------------------------------------------------------

--
-- Structure de la table `sav_espaces_applicatifs`
--

CREATE TABLE `sav_espaces_applicatifs` (
  `eap_id` bigint(20) UNSIGNED NOT NULL,
  `eap_societe_id` bigint(20) UNSIGNED NOT NULL,
  `eap_abonnement_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eap_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eap_bloque_le` datetime DEFAULT NULL,
  `eap_motif_blocage` varchar(255) DEFAULT NULL,
  `eap_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `eap_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `eap_supprime_le` datetime DEFAULT NULL,
  `eap_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eap_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eap_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Espaces applicatifs actifs des sociétés abonnées';

--
-- Déchargement des données de la table `sav_espaces_applicatifs`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_etablissements_societes`
--

CREATE TABLE `sav_etablissements_societes` (
  `ets_id` bigint(20) UNSIGNED NOT NULL,
  `ets_societe_id` bigint(20) UNSIGNED NOT NULL,
  `ets_siret` char(14) DEFAULT NULL,
  `ets_nom` varchar(150) DEFAULT NULL,
  `ets_adresse` varchar(255) DEFAULT NULL,
  `ets_code_postal` varchar(10) DEFAULT NULL,
  `ets_ville` varchar(100) DEFAULT NULL,
  `ets_pays_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ets_telephone` varchar(20) DEFAULT NULL,
  `ets_email` varchar(150) DEFAULT NULL,
  `ets_est_siege` tinyint(1) NOT NULL DEFAULT 0,
  `ets_est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `ets_ordre` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `ets_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `ets_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ets_supprime_le` datetime DEFAULT NULL,
  `ets_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ets_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ets_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sav_etiquettes`
--

CREATE TABLE `sav_etiquettes` (
  `eti_id` bigint(20) UNSIGNED NOT NULL,
  `eti_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eti_code` varchar(80) NOT NULL,
  `eti_libelle` varchar(120) NOT NULL,
  `eti_couleur` varchar(20) DEFAULT NULL,
  `eti_description` text DEFAULT NULL,
  `eti_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eti_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `eti_supprime_le` is null and `eti_archive_le` is null then 1 else NULL end) VIRTUAL,
  `eti_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `eti_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `eti_supprime_le` datetime DEFAULT NULL,
  `eti_archive_le` datetime DEFAULT NULL,
  `eti_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eti_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eti_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eti_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Étiquettes administrables';

-- --------------------------------------------------------

--
-- Structure de la table `sav_evenements_application`
--

CREATE TABLE `sav_evenements_application` (
  `eva_id` bigint(20) UNSIGNED NOT NULL,
  `eva_uuid` char(36) NOT NULL,
  `eva_code` varchar(120) NOT NULL,
  `eva_nom` varchar(160) NOT NULL,
  `eva_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eva_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eva_emetteur_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eva_cible_type` varchar(120) DEFAULT NULL,
  `eva_cible_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eva_donnees_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`eva_donnees_json`)),
  `eva_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eva_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `eva_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `eva_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journal applicatif des événements métier et noyau';

-- --------------------------------------------------------

--
-- Structure de la table `sav_exceptions_horaires_travail`
--

CREATE TABLE `sav_exceptions_horaires_travail` (
  `eht_id` bigint(20) UNSIGNED NOT NULL,
  `eht_societe_id` bigint(20) UNSIGNED NOT NULL,
  `eht_portee_type` varchar(30) NOT NULL,
  `eht_portee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `eht_date` date NOT NULL,
  `eht_ouvre_a` time DEFAULT NULL,
  `eht_ferme_a` time DEFAULT NULL,
  `eht_est_ferme` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `eht_raison` varchar(255) DEFAULT NULL,
  `eht_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `eht_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `eht_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Exceptions d’horaires';

-- --------------------------------------------------------

--
-- Structure de la table `sav_executions_maintenance`
--

CREATE TABLE `sav_executions_maintenance` (
  `exm_id` bigint(20) UNSIGNED NOT NULL,
  `exm_uuid` char(36) NOT NULL,
  `exm_politique_maintenance_id` bigint(20) UNSIGNED DEFAULT NULL,
  `exm_code_politique` varchar(100) DEFAULT NULL,
  `exm_type_action` varchar(40) NOT NULL,
  `exm_table_source` varchar(128) DEFAULT NULL,
  `exm_table_archive` varchar(128) DEFAULT NULL,
  `exm_date_limite` datetime DEFAULT NULL,
  `exm_mode_simulation` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `exm_statut` varchar(30) NOT NULL DEFAULT 'demarree' COMMENT 'demarree|terminee|echouee|partielle',
  `exm_lignes_archivees` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `exm_lignes_purgees` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `exm_lignes_analysees` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `exm_message` text DEFAULT NULL,
  `exm_erreur` text DEFAULT NULL,
  `exm_debut_le` datetime NOT NULL DEFAULT current_timestamp(),
  `exm_fin_le` datetime DEFAULT NULL,
  `exm_duree_ms` int(10) UNSIGNED DEFAULT NULL,
  `exm_lancee_par` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des executions de maintenance';

-- --------------------------------------------------------

--
-- Structure de la table `sav_exigences_versions_standards`
--

CREATE TABLE `sav_exigences_versions_standards` (
  `evs_id` bigint(20) UNSIGNED NOT NULL,
  `evs_version_standard_id` bigint(20) UNSIGNED NOT NULL,
  `evs_type_exigence` varchar(80) NOT NULL,
  `evs_competence_id` bigint(20) UNSIGNED DEFAULT NULL,
  `evs_certification_id` bigint(20) UNSIGNED DEFAULT NULL,
  `evs_niveau_competence_minimum_id` bigint(20) UNSIGNED DEFAULT NULL,
  `evs_est_obligatoire` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `evs_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `evs_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `evs_supprime_le` datetime DEFAULT NULL,
  `evs_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `evs_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `evs_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Exigences rattachées aux versions de standards';

-- --------------------------------------------------------

--
-- Structure de la table `sav_fichiers`
--

CREATE TABLE `sav_fichiers` (
  `fic_id` bigint(20) UNSIGNED NOT NULL,
  `fic_uuid` char(36) NOT NULL,
  `fic_nom_original` varchar(255) NOT NULL,
  `fic_nom_stockage` varchar(255) DEFAULT NULL,
  `fic_mime_type` varchar(120) NOT NULL,
  `fic_taille_octets` bigint(20) UNSIGNED NOT NULL,
  `fic_checksum_sha256` char(64) NOT NULL,
  `fic_est_chiffre` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `fic_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fic_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `fic_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `fic_supprime_le` datetime DEFAULT NULL,
  `fic_archive_le` datetime DEFAULT NULL,
  `fic_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fic_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fic_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fic_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Métadonnées des fichiers';

-- --------------------------------------------------------

--
-- Structure de la table `sav_file_evenements`
--

CREATE TABLE `sav_file_evenements` (
  `fev_id` bigint(20) UNSIGNED NOT NULL,
  `fev_evenement_id` bigint(20) UNSIGNED NOT NULL,
  `fev_abonnement_evenement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fev_priorite` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `fev_nombre_tentatives` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `fev_traitement_apres_le` datetime DEFAULT NULL,
  `fev_verrouille_jusqua` datetime DEFAULT NULL,
  `fev_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fev_derniere_erreur` text DEFAULT NULL,
  `fev_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `fev_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='File de traitement asynchrone des événements';

-- --------------------------------------------------------

--
-- Structure de la table `sav_fonctions`
--

CREATE TABLE `sav_fonctions` (
  `fon_id` bigint(20) UNSIGNED NOT NULL,
  `fon_code` varchar(80) NOT NULL,
  `fon_nom` varchar(120) NOT NULL,
  `fon_description` text DEFAULT NULL,
  `fon_societe_proprietaire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fon_type_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fon_portee_code` enum('interne','reseau','plateforme') NOT NULL DEFAULT 'interne',
  `fon_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fon_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `fon_supprime_le` is null and `fon_archive_le` is null then 1 else NULL end) VIRTUAL,
  `fon_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `fon_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `fon_supprime_le` datetime DEFAULT NULL,
  `fon_archive_le` datetime DEFAULT NULL,
  `fon_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fon_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fon_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fon_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fonctions métier réelles';

--
-- Déchargement des données de la table `sav_fonctions`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_fonctions_utilisateurs`
--

CREATE TABLE `sav_fonctions_utilisateurs` (
  `fut_id` bigint(20) UNSIGNED NOT NULL,
  `fut_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `fut_societe_id` bigint(20) UNSIGNED NOT NULL,
  `fut_fonction_id` bigint(20) UNSIGNED NOT NULL,
  `fut_marque_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fut_concession_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fut_service_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fut_equipe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fut_debute_le` date NOT NULL,
  `fut_termine_le` date DEFAULT NULL,
  `fut_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fut_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `fut_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `fut_supprime_le` datetime DEFAULT NULL,
  `fut_archive_le` datetime DEFAULT NULL,
  `fut_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fut_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fut_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fut_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fonctions métier affectées aux utilisateurs';

--
-- Déchargement des données de la table `sav_fonctions_utilisateurs`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_formules_abonnement`
--

CREATE TABLE `sav_formules_abonnement` (
  `fab_id` bigint(20) UNSIGNED NOT NULL,
  `fab_code` varchar(80) NOT NULL,
  `fab_nom` varchar(120) NOT NULL,
  `fab_description` text DEFAULT NULL,
  `fab_mode_tarif` varchar(50) NOT NULL DEFAULT 'personnalise',
  `fab_fonctions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fab_fonctions_json`)),
  `fab_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fab_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `fab_supprime_le` is null and `fab_archive_le` is null then 1 else NULL end) VIRTUAL,
  `fab_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `fab_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `fab_supprime_le` datetime DEFAULT NULL,
  `fab_archive_le` datetime DEFAULT NULL,
  `fab_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fab_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fab_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fab_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Formules d’abonnement personnalisables';

--
-- Déchargement des données de la table `sav_formules_abonnement`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_fuseaux_horaires`
--

CREATE TABLE `sav_fuseaux_horaires` (
  `fuh_id` bigint(20) UNSIGNED NOT NULL,
  `fuh_nom_iana` varchar(100) NOT NULL,
  `fuh_libelle` varchar(120) DEFAULT NULL,
  `fuh_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `fuh_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `fuh_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `fuh_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Référentiel des fuseaux horaires';

--
-- Déchargement des données de la table `sav_fuseaux_horaires`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_hierarchie_utilisateurs`
--

CREATE TABLE `sav_hierarchie_utilisateurs` (
  `hiu_id` bigint(20) UNSIGNED NOT NULL,
  `hiu_societe_id` bigint(20) UNSIGNED NOT NULL,
  `hiu_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `hiu_superieur_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `hiu_debute_le` date NOT NULL,
  `hiu_termine_le` date DEFAULT NULL,
  `hiu_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hiu_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `hiu_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `hiu_supprime_le` datetime DEFAULT NULL,
  `hiu_archive_le` datetime DEFAULT NULL,
  `hiu_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hiu_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hiu_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hiu_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hiérarchie utilisateur historisée';

--
-- Déchargement des données de la table `sav_hierarchie_utilisateurs`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_historique_contextes_utilisateurs`
--

CREATE TABLE `sav_historique_contextes_utilisateurs` (
  `hcu_id` bigint(20) UNSIGNED NOT NULL,
  `hcu_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `hcu_session_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_concession_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_marque_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_service_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_equipe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hcu_action` varchar(80) NOT NULL,
  `hcu_metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hcu_metadata_json`)),
  `hcu_adresse_ip` varbinary(16) DEFAULT NULL,
  `hcu_user_agent` varchar(255) DEFAULT NULL,
  `hcu_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des changements de contexte utilisateur';

--
-- Déchargement des données de la table `sav_historique_contextes_utilisateurs`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_horaires_travail`
--

CREATE TABLE `sav_horaires_travail` (
  `htr_id` bigint(20) UNSIGNED NOT NULL,
  `htr_societe_id` bigint(20) UNSIGNED NOT NULL,
  `htr_portee_type` varchar(30) NOT NULL,
  `htr_portee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `htr_jour_semaine` tinyint(3) UNSIGNED NOT NULL,
  `htr_ouvre_a` time DEFAULT NULL,
  `htr_ferme_a` time DEFAULT NULL,
  `htr_est_ferme` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `htr_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `htr_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `htr_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Horaires de travail par portée';

-- --------------------------------------------------------

--
-- Structure de la table `sav_invitations_utilisateurs`
--

CREATE TABLE `sav_invitations_utilisateurs` (
  `inv_id` bigint(20) UNSIGNED NOT NULL,
  `inv_uuid` char(36) NOT NULL,
  `inv_email` varchar(191) NOT NULL,
  `inv_email_normalise` varchar(191) NOT NULL,
  `inv_societe_id` bigint(20) UNSIGNED NOT NULL,
  `inv_role_prevu_id` bigint(20) UNSIGNED DEFAULT NULL,
  `inv_fonction_prevue_id` bigint(20) UNSIGNED DEFAULT NULL,
  `inv_jeton_hash` char(64) NOT NULL,
  `inv_message` text DEFAULT NULL,
  `inv_expire_le` datetime NOT NULL,
  `inv_acceptee_le` datetime DEFAULT NULL,
  `inv_annulee_le` datetime DEFAULT NULL,
  `inv_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `inv_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `inv_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `inv_supprime_le` datetime DEFAULT NULL,
  `inv_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `inv_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `inv_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Invitations contrôlées des utilisateurs';

-- --------------------------------------------------------

--
-- Structure de la table `sav_journaux_acces_donnees_sensibles`
--

CREATE TABLE `sav_journaux_acces_donnees_sensibles` (
  `jad_id` bigint(20) UNSIGNED NOT NULL,
  `jad_utilisateur_source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jad_utilisateur_cible_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jad_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jad_donnee_sensible_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jad_action` varchar(80) NOT NULL,
  `jad_champs_consultes_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jad_champs_consultes_json`)),
  `jad_raison` text DEFAULT NULL,
  `jad_adresse_ip` varbinary(16) DEFAULT NULL,
  `jad_user_agent` varchar(500) DEFAULT NULL,
  `jad_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit spécifique des accès aux données sensibles';

-- --------------------------------------------------------

--
-- Structure de la table `sav_journaux_audit`
--

CREATE TABLE `sav_journaux_audit` (
  `jau_id` bigint(20) UNSIGNED NOT NULL,
  `jau_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jau_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jau_action` varchar(120) NOT NULL,
  `jau_table_cible` varchar(120) DEFAULT NULL,
  `jau_id_cible` bigint(20) UNSIGNED DEFAULT NULL,
  `jau_raison` varchar(255) DEFAULT NULL,
  `jau_adresse_ip` varbinary(16) DEFAULT NULL,
  `jau_user_agent` varchar(255) DEFAULT NULL,
  `jau_metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jau_metadata_json`)),
  `jau_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journaux d’audit actifs';

-- --------------------------------------------------------

--
-- Structure de la table `sav_journaux_emails`
--

CREATE TABLE `sav_journaux_emails` (
  `jme_id` bigint(20) UNSIGNED NOT NULL,
  `jme_modele_email_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jme_societe_expediteur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jme_societe_destinataire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jme_utilisateur_destinataire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jme_email_destinataire` varchar(191) DEFAULT NULL,
  `jme_type_evenement` varchar(120) DEFAULT NULL,
  `jme_sujet` varchar(255) NOT NULL,
  `jme_corps` mediumtext DEFAULT NULL,
  `jme_envoye_le` datetime DEFAULT NULL,
  `jme_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jme_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des emails envoyés';

-- --------------------------------------------------------

--
-- Structure de la table `sav_journaux_maintenance`
--

CREATE TABLE `sav_journaux_maintenance` (
  `jma_id` bigint(20) UNSIGNED NOT NULL,
  `jma_execution_maintenance_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jma_niveau` varchar(30) NOT NULL DEFAULT 'info',
  `jma_message` text NOT NULL,
  `jma_details_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jma_details_json`)),
  `jma_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journal detaille des operations de maintenance';

-- --------------------------------------------------------

--
-- Structure de la table `sav_journaux_rgpd`
--

CREATE TABLE `sav_journaux_rgpd` (
  `jrg_id` bigint(20) UNSIGNED NOT NULL,
  `jrg_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jrg_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jrg_action` varchar(120) NOT NULL,
  `jrg_base_legale` varchar(120) DEFAULT NULL,
  `jrg_table_cible` varchar(120) DEFAULT NULL,
  `jrg_id_cible` bigint(20) UNSIGNED DEFAULT NULL,
  `jrg_metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jrg_metadata_json`)),
  `jrg_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journal RGPD';

-- --------------------------------------------------------

--
-- Structure de la table `sav_journaux_systeme`
--

CREATE TABLE `sav_journaux_systeme` (
  `jsy_id` bigint(20) UNSIGNED NOT NULL,
  `jsy_niveau` varchar(30) NOT NULL,
  `jsy_categorie` varchar(80) NOT NULL,
  `jsy_message` text NOT NULL,
  `jsy_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jsy_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jsy_adresse_ip` varbinary(16) DEFAULT NULL,
  `jsy_user_agent` varchar(255) DEFAULT NULL,
  `jsy_metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jsy_metadata_json`)),
  `jsy_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journaux système actifs';

--
-- Déchargement des données de la table `sav_journaux_systeme`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_journaux_webhooks`
--

CREATE TABLE `sav_journaux_webhooks` (
  `jwh_id` bigint(20) UNSIGNED NOT NULL,
  `jwh_webhook_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jwh_evenement_application_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jwh_url` varchar(500) NOT NULL,
  `jwh_methode` varchar(10) NOT NULL,
  `jwh_code_http` smallint(5) UNSIGNED DEFAULT NULL,
  `jwh_requete_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jwh_requete_json`)),
  `jwh_reponse_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jwh_reponse_json`)),
  `jwh_succes` tinyint(1) UNSIGNED DEFAULT NULL,
  `jwh_message_erreur` text DEFAULT NULL,
  `jwh_duree_ms` int(10) UNSIGNED DEFAULT NULL,
  `jwh_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journaux d’exécution des webhooks';

-- --------------------------------------------------------

--
-- Structure de la table `sav_liaisons_fichiers`
--

CREATE TABLE `sav_liaisons_fichiers` (
  `lfi_id` bigint(20) UNSIGNED NOT NULL,
  `lfi_fichier_id` bigint(20) UNSIGNED NOT NULL,
  `lfi_cible_type` varchar(120) NOT NULL,
  `lfi_cible_id` bigint(20) UNSIGNED NOT NULL,
  `lfi_type_liaison` varchar(80) NOT NULL DEFAULT 'piece_jointe',
  `lfi_ordre` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `lfi_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lfi_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `lfi_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `lfi_supprime_le` datetime DEFAULT NULL,
  `lfi_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lfi_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lfi_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Liaisons génériques entre fichiers et entités';

-- --------------------------------------------------------

--
-- Structure de la table `sav_liens_documents_juridiques_societes`
--

CREATE TABLE `sav_liens_documents_juridiques_societes` (
  `ldj_id` bigint(20) UNSIGNED NOT NULL,
  `ldj_societe_id` bigint(20) UNSIGNED NOT NULL,
  `ldj_document_juridique_id` bigint(20) UNSIGNED NOT NULL,
  `ldj_priorite` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `ldj_est_obligatoire` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `ldj_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ldj_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `ldj_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ldj_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Liens entre sociétés et documents juridiques';

-- --------------------------------------------------------

--
-- Structure de la table `sav_menus`
--

CREATE TABLE `sav_menus` (
  `men_id` bigint(20) UNSIGNED NOT NULL,
  `men_code` varchar(80) NOT NULL,
  `men_nom` varchar(120) NOT NULL,
  `men_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `men_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `men_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `men_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `men_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Menus dynamiques';

-- --------------------------------------------------------

--
-- Structure de la table `sav_modeles_emails`
--

CREATE TABLE `sav_modeles_emails` (
  `mel_id` bigint(20) UNSIGNED NOT NULL,
  `mel_code` varchar(100) NOT NULL,
  `mel_sujet` varchar(255) NOT NULL,
  `mel_corps` text NOT NULL,
  `mel_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mel_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `mel_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `mel_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Modèles d’emails';

-- --------------------------------------------------------

--
-- Structure de la table `sav_modeles_notifications`
--

CREATE TABLE `sav_modeles_notifications` (
  `mno_id` bigint(20) UNSIGNED NOT NULL,
  `mno_code` varchar(100) NOT NULL,
  `mno_nom` varchar(160) NOT NULL,
  `mno_canal_notification_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mno_titre_modele` varchar(255) NOT NULL,
  `mno_corps_modele` text NOT NULL,
  `mno_variables_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mno_variables_json`)),
  `mno_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mno_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `mno_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `mno_supprime_le` datetime DEFAULT NULL,
  `mno_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mno_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mno_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Modèles de notifications applicatives';

-- --------------------------------------------------------

--
-- Structure de la table `sav_modes_reglement`
--

CREATE TABLE `sav_modes_reglement` (
  `mrg_id` bigint(20) UNSIGNED NOT NULL,
  `mrg_code` varchar(20) NOT NULL,
  `mrg_libelle` varchar(150) NOT NULL,
  `mrg_jours_echeance` smallint(5) UNSIGNED DEFAULT NULL,
  `mrg_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `mrg_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `mrg_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `mrg_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Référentiel : mode de règlement (ex. LC30 / LCR DIRECTE CLTS 30 JRS)';

--
-- Déchargement des données de la table `sav_modes_reglement`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_modules`
--

CREATE TABLE `sav_modules` (
  `mod_id` bigint(20) UNSIGNED NOT NULL,
  `mod_code` varchar(80) NOT NULL,
  `mod_nom` varchar(120) NOT NULL,
  `mod_description` text DEFAULT NULL,
  `mod_version` varchar(30) DEFAULT NULL,
  `mod_est_noyau` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `mod_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mod_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `mod_supprime_le` is null and `mod_archive_le` is null then 1 else NULL end) VIRTUAL,
  `mod_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `mod_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `mod_supprime_le` datetime DEFAULT NULL,
  `mod_archive_le` datetime DEFAULT NULL,
  `mod_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mod_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mod_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mod_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Modules installables ou noyau';

--
-- Déchargement des données de la table `sav_modules`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_modules_societes`
--

CREATE TABLE `sav_modules_societes` (
  `mos_id` bigint(20) UNSIGNED NOT NULL,
  `mos_societe_id` bigint(20) UNSIGNED NOT NULL,
  `mos_module_id` bigint(20) UNSIGNED NOT NULL,
  `mos_formule_abonnement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mos_active_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mos_debute_le` datetime NOT NULL,
  `mos_termine_le` datetime DEFAULT NULL,
  `mos_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mos_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `mos_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `mos_supprime_le` datetime DEFAULT NULL,
  `mos_archive_le` datetime DEFAULT NULL,
  `mos_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mos_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mos_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mos_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Modules activés pour chaque société';

--
-- Déchargement des données de la table `sav_modules_societes`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_natures_clients`
--

CREATE TABLE `sav_natures_clients` (
  `nac_id` bigint(20) UNSIGNED NOT NULL,
  `nac_code` varchar(20) NOT NULL,
  `nac_libelle` varchar(150) NOT NULL,
  `nac_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `nac_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `nac_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `nac_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Référentiel : nature de client (ex. INTERCO CONSO)';

--
-- Déchargement des données de la table `sav_natures_clients`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_niveaux_competences`
--

CREATE TABLE `sav_niveaux_competences` (
  `nco_id` bigint(20) UNSIGNED NOT NULL,
  `nco_code` varchar(50) NOT NULL,
  `nco_nom` varchar(120) NOT NULL,
  `nco_rang` tinyint(3) UNSIGNED NOT NULL,
  `nco_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nco_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `nco_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `nco_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Niveaux de compétences';

--
-- Déchargement des données de la table `sav_niveaux_competences`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_notes`
--

CREATE TABLE `sav_notes` (
  `nte_id` bigint(20) UNSIGNED NOT NULL,
  `nte_uuid` char(36) NOT NULL,
  `nte_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nte_cible_type` varchar(120) NOT NULL,
  `nte_cible_id` bigint(20) UNSIGNED NOT NULL,
  `nte_titre` varchar(255) DEFAULT NULL,
  `nte_contenu` text NOT NULL,
  `nte_est_privee` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `nte_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nte_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `nte_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `nte_supprime_le` datetime DEFAULT NULL,
  `nte_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nte_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nte_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notes génériques attachables aux entités';

-- --------------------------------------------------------

--
-- Structure de la table `sav_notifications`
--

CREATE TABLE `sav_notifications` (
  `not_id` bigint(20) UNSIGNED NOT NULL,
  `not_uuid` char(36) NOT NULL,
  `not_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `not_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `not_modele_notification_id` bigint(20) UNSIGNED DEFAULT NULL,
  `not_type` varchar(80) NOT NULL,
  `not_priorite` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `not_titre` varchar(255) NOT NULL,
  `not_message` text NOT NULL,
  `not_lien_url` varchar(500) DEFAULT NULL,
  `not_cible_type` varchar(120) DEFAULT NULL,
  `not_cible_id` bigint(20) UNSIGNED DEFAULT NULL,
  `not_donnees_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`not_donnees_json`)),
  `not_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `not_expire_le` datetime DEFAULT NULL,
  `not_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `not_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `not_supprime_le` datetime DEFAULT NULL,
  `not_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `not_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `not_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notifications applicatives internes';

-- --------------------------------------------------------

--
-- Structure de la table `sav_parametres_application`
--

CREATE TABLE `sav_parametres_application` (
  `pap_id` bigint(20) UNSIGNED NOT NULL,
  `pap_domaine` varchar(80) NOT NULL,
  `pap_cle` varchar(120) NOT NULL,
  `pap_valeur_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pap_valeur_json`)),
  `pap_valeur_chiffree` varbinary(4096) DEFAULT NULL,
  `pap_algorithme_chiffrement` varchar(80) DEFAULT NULL,
  `pap_reference_coffre_secret` varchar(255) DEFAULT NULL,
  `pap_description` text DEFAULT NULL,
  `pap_est_secret` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `pap_est_systeme` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `pap_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pap_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `pap_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `pap_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuration noyau paramétrable';

--
-- Déchargement des données de la table `sav_parametres_application`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_parametres_securite_utilisateurs`
--

CREATE TABLE `sav_parametres_securite_utilisateurs` (
  `psu_id` bigint(20) UNSIGNED NOT NULL,
  `psu_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `psu_2fa_active` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `psu_2fa_methode` varchar(20) DEFAULT NULL,
  `psu_2fa_secret_chiffre` varbinary(512) DEFAULT NULL,
  `psu_2fa_codes_secours_chiffres` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`psu_2fa_codes_secours_chiffres`)),
  `psu_2fa_activee_le` datetime DEFAULT NULL,
  `psu_historique_mots_de_passe_hash` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`psu_historique_mots_de_passe_hash`)),
  `psu_jeton_verification_email_hash` char(64) DEFAULT NULL,
  `psu_verification_email_envoyee_le` datetime DEFAULT NULL,
  `psu_tentatives_verification_email` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `psu_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `psu_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `psu_supprime_le` datetime DEFAULT NULL,
  `psu_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `psu_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `psu_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paramètres de sécurité séparés des données utilisateur';

--
-- Déchargement des données de la table `sav_parametres_securite_utilisateurs`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_pays`
--

CREATE TABLE `sav_pays` (
  `pay_id` bigint(20) UNSIGNED NOT NULL,
  `pay_code_iso2` char(2) NOT NULL,
  `pay_code_iso3` char(3) NOT NULL,
  `pay_nom` varchar(100) NOT NULL,
  `pay_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `pay_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `pay_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `pay_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Référentiel des pays';

--
-- Déchargement des données de la table `sav_pays`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_permissions`
--

CREATE TABLE `sav_permissions` (
  `per_id` bigint(20) UNSIGNED NOT NULL,
  `per_code` varchar(120) NOT NULL,
  `per_description` text DEFAULT NULL,
  `per_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `per_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `per_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `per_supprime_le` is null and `per_archive_le` is null then 1 else NULL end) VIRTUAL,
  `per_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `per_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `per_supprime_le` datetime DEFAULT NULL,
  `per_archive_le` datetime DEFAULT NULL,
  `per_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `per_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `per_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `per_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permissions techniques fines';

--
-- Déchargement des données de la table `sav_permissions`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_politiques_acces`
--

CREATE TABLE `sav_politiques_acces` (
  `pac_id` bigint(20) UNSIGNED NOT NULL,
  `pac_code` varchar(120) NOT NULL,
  `pac_nom` varchar(160) NOT NULL,
  `pac_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pac_permission_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pac_effet` varchar(10) NOT NULL,
  `pac_priorite` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `pac_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pac_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `pac_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `pac_supprime_le` datetime DEFAULT NULL,
  `pac_archive_le` datetime DEFAULT NULL,
  `pac_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pac_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pac_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pac_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Politiques ABAC';

-- --------------------------------------------------------

--
-- Structure de la table `sav_politiques_conservation_journaux`
--

CREATE TABLE `sav_politiques_conservation_journaux` (
  `pcj_id` bigint(20) UNSIGNED NOT NULL,
  `pcj_type_journal` varchar(80) NOT NULL,
  `pcj_duree_conservation_jours` int(10) UNSIGNED NOT NULL,
  `pcj_archivage_active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `pcj_table_archive` varchar(120) DEFAULT NULL,
  `pcj_base_archive` varchar(120) DEFAULT NULL,
  `pcj_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pcj_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `pcj_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `pcj_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Politiques de conservation et archivage des journaux';

--
-- Déchargement des données de la table `sav_politiques_conservation_journaux`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_politiques_maintenance`
--

CREATE TABLE `sav_politiques_maintenance` (
  `pmt_id` bigint(20) UNSIGNED NOT NULL,
  `pmt_code` varchar(100) NOT NULL,
  `pmt_nom` varchar(180) NOT NULL,
  `pmt_description` text DEFAULT NULL,
  `pmt_type_action` varchar(40) NOT NULL DEFAULT 'archiver_purger' COMMENT 'archiver_purger|purger|optimiser|controler',
  `pmt_table_source` varchar(128) NOT NULL,
  `pmt_table_archive` varchar(128) DEFAULT NULL,
  `pmt_colonne_date` varchar(128) NOT NULL,
  `pmt_duree_conservation_jours` int(10) UNSIGNED NOT NULL,
  `pmt_archivage_active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `pmt_purge_active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `pmt_taille_lot` int(10) UNSIGNED NOT NULL DEFAULT 5000,
  `pmt_frequence` varchar(30) NOT NULL DEFAULT 'quotidienne' COMMENT 'horaire|quotidienne|hebdomadaire|mensuelle|manuelle',
  `pmt_heure_execution` time DEFAULT '03:00:00',
  `pmt_derniere_execution_le` datetime DEFAULT NULL,
  `pmt_prochaine_execution_le` datetime DEFAULT NULL,
  `pmt_est_active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `pmt_mode_simulation` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `pmt_notes` text DEFAULT NULL,
  `pmt_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `pmt_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `pmt_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Politiques de maintenance : archivage, purge, optimisation et controle';

--
-- Déchargement des données de la table `sav_politiques_maintenance`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_preferences_notifications`
--

CREATE TABLE `sav_preferences_notifications` (
  `pno_id` bigint(20) UNSIGNED NOT NULL,
  `pno_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `pno_canal_notification_id` bigint(20) UNSIGNED NOT NULL,
  `pno_type_notification` varchar(80) NOT NULL,
  `pno_est_active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `pno_parametres_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pno_parametres_json`)),
  `pno_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `pno_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `pno_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Préférences de notifications par utilisateur';

-- --------------------------------------------------------

--
-- Structure de la table `sav_profils_utilisateurs`
--

CREATE TABLE `sav_profils_utilisateurs` (
  `pui_id` bigint(20) UNSIGNED NOT NULL,
  `pui_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `pui_nom` varchar(100) DEFAULT NULL,
  `pui_prenom` varchar(100) DEFAULT NULL,
  `pui_civilite` varchar(20) DEFAULT NULL,
  `pui_telephone` varchar(30) DEFAULT NULL,
  `pui_mobile` varchar(30) DEFAULT NULL,
  `pui_photo_fichier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pui_couleur_avatar` varchar(7) DEFAULT NULL,
  `pui_adresse_rue` varchar(255) DEFAULT NULL,
  `pui_adresse_ville` varchar(100) DEFAULT NULL,
  `pui_adresse_code_postal` varchar(20) DEFAULT NULL,
  `pui_pays_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pui_date_naissance` date DEFAULT NULL,
  `pui_lieu_naissance` varchar(120) DEFAULT NULL,
  `pui_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `pui_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `pui_supprime_le` datetime DEFAULT NULL,
  `pui_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pui_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pui_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Profil non sensible des utilisateurs';

--
-- Déchargement des données de la table `sav_profils_utilisateurs`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_regles_validation`
--

CREATE TABLE `sav_regles_validation` (
  `rva_id` bigint(20) UNSIGNED NOT NULL,
  `rva_code` varchar(100) NOT NULL,
  `rva_nom` varchar(160) NOT NULL,
  `rva_type_operation` varchar(100) NOT NULL,
  `rva_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rva_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rva_conditions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rva_conditions_json`)),
  `rva_nombre_validateurs_requis` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `rva_permission_validateur_code` varchar(120) DEFAULT NULL,
  `rva_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rva_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `rva_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `rva_supprime_le` datetime DEFAULT NULL,
  `rva_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rva_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rva_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Règles génériques de validation des opérations sensibles';

-- --------------------------------------------------------

--
-- Structure de la table `sav_relations_societes`
--

CREATE TABLE `sav_relations_societes` (
  `rso_id` bigint(20) UNSIGNED NOT NULL,
  `rso_societe_source_id` bigint(20) UNSIGNED NOT NULL,
  `rso_societe_cible_id` bigint(20) UNSIGNED NOT NULL,
  `rso_type_relation_societe_id` bigint(20) UNSIGNED NOT NULL,
  `rso_debute_le` date NOT NULL,
  `rso_termine_le` date DEFAULT NULL,
  `rso_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rso_cree_par_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rso_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `rso_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `rso_supprime_le` datetime DEFAULT NULL,
  `rso_archive_le` datetime DEFAULT NULL,
  `rso_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rso_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rso_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rso_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relations historisées entre sociétés';

--
-- Déchargement des données de la table `sav_relations_societes`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_representations_marques_societes`
--

CREATE TABLE `sav_representations_marques_societes` (
  `rma_id` bigint(20) UNSIGNED NOT NULL,
  `rma_concession_societe_id` bigint(20) UNSIGNED NOT NULL,
  `rma_marque_societe_id` bigint(20) UNSIGNED NOT NULL,
  `rma_importateur_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rma_constructeur_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rma_debute_le` date NOT NULL,
  `rma_termine_le` date DEFAULT NULL,
  `rma_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rma_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `rma_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `rma_supprime_le` datetime DEFAULT NULL,
  `rma_archive_le` datetime DEFAULT NULL,
  `rma_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rma_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rma_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rma_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rma_est_principale` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Marque principale de cette concession (1 = oui)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Représentations marque / concession / importateur / constructeur';

--
-- Déchargement des données de la table `sav_representations_marques_societes`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_resultats_controles_base`
--

CREATE TABLE `sav_resultats_controles_base` (
  `rcb_id` bigint(20) UNSIGNED NOT NULL,
  `rcb_controle_qualite_base_id` bigint(20) UNSIGNED NOT NULL,
  `rcb_statut` varchar(30) NOT NULL,
  `rcb_nombre_lignes` bigint(20) UNSIGNED DEFAULT NULL,
  `rcb_message` text DEFAULT NULL,
  `rcb_execute_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Resultats historises des controles qualite de la base';

-- --------------------------------------------------------

--
-- Structure de la table `sav_roles`
--

CREATE TABLE `sav_roles` (
  `rol_id` bigint(20) UNSIGNED NOT NULL,
  `rol_code` varchar(100) NOT NULL,
  `rol_nom` varchar(120) NOT NULL,
  `rol_description` text DEFAULT NULL,
  `rol_societe_proprietaire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol_type_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol_portee_code` enum('interne','reseau','plateforme') NOT NULL DEFAULT 'interne',
  `rol_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `rol_supprime_le` is null and `rol_archive_le` is null then 1 else NULL end) VIRTUAL,
  `rol_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `rol_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `rol_supprime_le` datetime DEFAULT NULL,
  `rol_archive_le` datetime DEFAULT NULL,
  `rol_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rôles applicatifs contextuels';

--
-- Déchargement des données de la table `sav_roles`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_roles_contextuels_utilisateurs`
--

CREATE TABLE `sav_roles_contextuels_utilisateurs` (
  `rcu_id` bigint(20) UNSIGNED NOT NULL,
  `rcu_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `rcu_role_id` bigint(20) UNSIGNED NOT NULL,
  `rcu_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_marque_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_concession_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_departement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_secteur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_service_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_equipe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_debute_le` date NOT NULL,
  `rcu_termine_le` date DEFAULT NULL,
  `rcu_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `rcu_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `rcu_supprime_le` datetime DEFAULT NULL,
  `rcu_archive_le` datetime DEFAULT NULL,
  `rcu_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rcu_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rôles utilisateur attachés à un contexte';

--
-- Déchargement des données de la table `sav_roles_contextuels_utilisateurs`
--


--
-- Déclencheurs `sav_roles_contextuels_utilisateurs`
--
DELIMITER $$
CREATE TRIGGER `trg_v43_rcu_acl_bump_after_delete` AFTER DELETE ON `sav_roles_contextuels_utilisateurs` FOR EACH ROW BEGIN
    UPDATE sav_utilisateurs
       SET uti_acl_version = uti_acl_version + 1,
           uti_modifie_le = NOW()
     WHERE uti_id = OLD.rcu_utilisateur_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_v43_rcu_acl_bump_after_insert` AFTER INSERT ON `sav_roles_contextuels_utilisateurs` FOR EACH ROW BEGIN
    UPDATE sav_utilisateurs
       SET uti_acl_version = uti_acl_version + 1,
           uti_modifie_le = NOW()
     WHERE uti_id = NEW.rcu_utilisateur_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_v43_rcu_acl_bump_after_update` AFTER UPDATE ON `sav_roles_contextuels_utilisateurs` FOR EACH ROW BEGIN
    UPDATE sav_utilisateurs
       SET uti_acl_version = uti_acl_version + 1,
           uti_modifie_le = NOW()
     WHERE uti_id IN (OLD.rcu_utilisateur_id, NEW.rcu_utilisateur_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `sav_roles_permissions`
--

CREATE TABLE `sav_roles_permissions` (
  `rpe_id` bigint(20) UNSIGNED NOT NULL,
  `rpe_role_id` bigint(20) UNSIGNED NOT NULL,
  `rpe_permission_id` bigint(20) UNSIGNED NOT NULL,
  `rpe_effet` enum('autoriser','refuser') NOT NULL COMMENT 'Effet explicite obligatoire : autoriser ou refuser ; aucun DEFAULT.',
  `rpe_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `rpe_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `rpe_supprime_le` datetime DEFAULT NULL,
  `rpe_cle_active` tinyint(1) GENERATED ALWAYS AS (case when `rpe_supprime_le` is null then 1 else NULL end) STORED COMMENT 'Clé active pour unicité avec suppression logique',
  `rpe_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rpe_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rpe_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permissions accordées ou refusées par rôle';

--
-- Déchargement des données de la table `sav_roles_permissions`
--


--
-- Déclencheurs `sav_roles_permissions`
--
DELIMITER $$
CREATE TRIGGER `trg_v43_rpe_acl_bump_after_delete` AFTER DELETE ON `sav_roles_permissions` FOR EACH ROW BEGIN
    UPDATE sav_utilisateurs u
    INNER JOIN sav_roles_contextuels_utilisateurs rcu
            ON rcu.rcu_utilisateur_id = u.uti_id
           AND rcu.rcu_role_id = OLD.rpe_role_id
           AND rcu.rcu_supprime_le IS NULL
       SET u.uti_acl_version = u.uti_acl_version + 1,
           u.uti_modifie_le = NOW();
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_v43_rpe_acl_bump_after_insert` AFTER INSERT ON `sav_roles_permissions` FOR EACH ROW BEGIN
    UPDATE sav_utilisateurs u
    INNER JOIN sav_roles_contextuels_utilisateurs rcu
            ON rcu.rcu_utilisateur_id = u.uti_id
           AND rcu.rcu_role_id = NEW.rpe_role_id
           AND rcu.rcu_supprime_le IS NULL
       SET u.uti_acl_version = u.uti_acl_version + 1,
           u.uti_modifie_le = NOW();
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_v43_rpe_acl_bump_after_update` AFTER UPDATE ON `sav_roles_permissions` FOR EACH ROW BEGIN
    UPDATE sav_utilisateurs u
    INNER JOIN sav_roles_contextuels_utilisateurs rcu
            ON rcu.rcu_utilisateur_id = u.uti_id
           AND rcu.rcu_role_id IN (OLD.rpe_role_id, NEW.rpe_role_id)
           AND rcu.rcu_supprime_le IS NULL
       SET u.uti_acl_version = u.uti_acl_version + 1,
           u.uti_modifie_le = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `sav_secteurs`
--

CREATE TABLE `sav_secteurs` (
  `sec_id` bigint(20) UNSIGNED NOT NULL,
  `sec_societe_id` bigint(20) UNSIGNED NOT NULL,
  `sec_code` varchar(50) DEFAULT NULL,
  `sec_nom` varchar(120) NOT NULL,
  `sec_description` text DEFAULT NULL,
  `sec_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sec_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `sec_code` is not null and `sec_supprime_le` is null and `sec_archive_le` is null then 1 else NULL end) VIRTUAL,
  `sec_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `sec_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `sec_supprime_le` datetime DEFAULT NULL,
  `sec_archive_le` datetime DEFAULT NULL,
  `sec_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sec_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sec_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sec_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Secteurs internes des sociétés';

-- --------------------------------------------------------

--
-- Structure de la table `sav_secteurs_services`
--

CREATE TABLE `sav_secteurs_services` (
  `ssv_id` bigint(20) UNSIGNED NOT NULL,
  `ssv_societe_id` bigint(20) UNSIGNED NOT NULL,
  `ssv_secteur_id` bigint(20) UNSIGNED NOT NULL,
  `ssv_service_id` bigint(20) UNSIGNED NOT NULL,
  `ssv_debute_le` date NOT NULL,
  `ssv_termine_le` date DEFAULT NULL,
  `ssv_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ssv_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `ssv_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ssv_supprime_le` datetime DEFAULT NULL,
  `ssv_archive_le` datetime DEFAULT NULL,
  `ssv_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ssv_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ssv_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ssv_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lien secteur / service';

-- --------------------------------------------------------

--
-- Structure de la table `sav_services`
--

CREATE TABLE `sav_services` (
  `srv_id` bigint(20) UNSIGNED NOT NULL,
  `srv_societe_id` bigint(20) UNSIGNED NOT NULL,
  `srv_code` varchar(50) DEFAULT NULL,
  `srv_nom` varchar(120) NOT NULL,
  `srv_description` text DEFAULT NULL,
  `srv_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `srv_responsable_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `srv_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `srv_code` is not null and `srv_supprime_le` is null and `srv_archive_le` is null then 1 else NULL end) VIRTUAL,
  `srv_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `srv_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `srv_supprime_le` datetime DEFAULT NULL,
  `srv_archive_le` datetime DEFAULT NULL,
  `srv_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `srv_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `srv_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `srv_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Services internes des sociétés';

-- --------------------------------------------------------

--
-- Structure de la table `sav_services_equipes`
--

CREATE TABLE `sav_services_equipes` (
  `seq_id` bigint(20) UNSIGNED NOT NULL,
  `seq_societe_id` bigint(20) UNSIGNED NOT NULL,
  `seq_service_id` bigint(20) UNSIGNED NOT NULL,
  `seq_equipe_id` bigint(20) UNSIGNED NOT NULL,
  `seq_debute_le` date NOT NULL,
  `seq_termine_le` date DEFAULT NULL,
  `seq_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seq_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `seq_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `seq_supprime_le` datetime DEFAULT NULL,
  `seq_archive_le` datetime DEFAULT NULL,
  `seq_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seq_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seq_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seq_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lien service / équipe';

-- --------------------------------------------------------

--
-- Structure de la table `sav_sessions_utilisateurs`
--

CREATE TABLE `sav_sessions_utilisateurs` (
  `seu_id` bigint(20) UNSIGNED NOT NULL,
  `seu_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `seu_identifiant_session_hash` char(64) NOT NULL,
  `seu_societe_active_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_concession_active_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_marque_active_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_service_actif_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_equipe_active_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_adresse_ip` varbinary(16) DEFAULT NULL,
  `seu_user_agent` varchar(255) DEFAULT NULL,
  `seu_derniere_activite_le` datetime NOT NULL,
  `seu_expire_le` datetime NOT NULL,
  `seu_revoquee_le` datetime DEFAULT NULL,
  `seu_motif_revocation` varchar(255) DEFAULT NULL,
  `seu_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seu_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `seu_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `seu_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions applicatives persistantes';

--
-- Déchargement des données de la table `sav_sessions_utilisateurs`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_societes`
--

CREATE TABLE `sav_societes` (
  `soc_id` bigint(20) UNSIGNED NOT NULL,
  `soc_uuid` char(36) NOT NULL,
  `soc_code` varchar(30) DEFAULT NULL,
  `soc_nom` varchar(191) NOT NULL,
  `soc_nom_legal` varchar(191) DEFAULT NULL,
  `soc_siren` char(9) DEFAULT NULL COMMENT 'N° SIREN (9 chiffres)',
  `soc_siret_siege` char(14) DEFAULT NULL COMMENT 'SIRET du siège social (14 ch.)',
  `soc_code_naf` varchar(6) DEFAULT NULL COMMENT 'Code NAF / APE (ex : 4511Z)',
  `soc_libelle_naf` varchar(200) DEFAULT NULL COMMENT 'Libellé de l activité NAF/APE',
  `soc_forme_juridique` varchar(60) DEFAULT NULL COMMENT 'Forme juridique (SAS, SARL, SA…)',
  `soc_date_creation` date DEFAULT NULL COMMENT 'Date de création légale',
  `soc_capital_social` decimal(15,2) DEFAULT NULL COMMENT 'Capital social en euros',
  `soc_rcs` varchar(100) DEFAULT NULL COMMENT 'Ville et n° RCS (ex : Paris B 123456789)',
  `soc_nom_court` varchar(60) DEFAULT NULL,
  `soc_siret` varchar(20) DEFAULT NULL,
  `soc_numero_tva` varchar(30) DEFAULT NULL,
  `soc_adresse` varchar(255) DEFAULT NULL,
  `soc_code_postal` varchar(20) DEFAULT NULL,
  `soc_ville` varchar(100) DEFAULT NULL,
  `soc_pays_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soc_telephone` varchar(30) DEFAULT NULL,
  `soc_email` varchar(191) DEFAULT NULL,
  `soc_site_web` varchar(255) DEFAULT NULL,
  `soc_logo_fichier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soc_est_holding` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `soc_societe_parente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soc_holding_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soc_cree_par_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soc_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soc_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `soc_code` is not null and `soc_supprime_le` is null and `soc_archive_le` is null then 1 else NULL end) VIRTUAL,
  `soc_cle_siret_active` tinyint(1) GENERATED ALWAYS AS (case when `soc_siret` is not null and `soc_supprime_le` is null and `soc_archive_le` is null then 1 else NULL end) VIRTUAL,
  `soc_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `soc_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `soc_supprime_le` datetime DEFAULT NULL,
  `soc_archive_le` datetime DEFAULT NULL,
  `soc_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soc_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soc_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soc_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sociétés connues, abonnées ou non abonnées';

--
-- Déchargement des données de la table `sav_societes`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_societes_comptes_bancaires`
--

CREATE TABLE `sav_societes_comptes_bancaires` (
  `scb_id` bigint(20) UNSIGNED NOT NULL,
  `scb_societe_id` bigint(20) UNSIGNED NOT NULL,
  `scb_code_banque` varchar(10) DEFAULT NULL,
  `scb_code_guichet` varchar(10) DEFAULT NULL,
  `scb_numero_compte` varchar(20) DEFAULT NULL,
  `scb_cle_rib` varchar(5) DEFAULT NULL,
  `scb_iban` varchar(34) DEFAULT NULL,
  `scb_bic` varchar(11) DEFAULT NULL,
  `scb_est_principal` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `scb_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `scb_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `scb_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `scb_supprime_le` datetime DEFAULT NULL,
  `scb_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `scb_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `scb_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comptes bancaires (RIB/IBAN/BIC) des sociétés/marques';

-- --------------------------------------------------------

--
-- Structure de la table `sav_societes_infos_complementaires`
--

CREATE TABLE `sav_societes_infos_complementaires` (
  `sic_id` bigint(20) UNSIGNED NOT NULL,
  `sic_societe_id` bigint(20) UNSIGNED NOT NULL,
  `sic_code_client` varchar(50) DEFAULT NULL,
  `sic_complement_nom` varchar(150) DEFAULT NULL,
  `sic_nature_client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sic_compte_collectif` varchar(50) DEFAULT NULL,
  `sic_partenaire_vgf` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `sic_kvps_mra_cld` varchar(100) DEFAULT NULL,
  `sic_flag_garantie` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `sic_flag_passager` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `sic_flag_releve` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `sic_flag_assureur` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `sic_flag_blocage_facture` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `sic_flag_douteux` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `sic_mode_reglement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sic_tva_specifique_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sic_banque_remise` varchar(150) DEFAULT NULL,
  `sic_plafond_credit` decimal(12,2) DEFAULT NULL,
  `sic_delai_paiement_mois` tinyint(3) UNSIGNED DEFAULT NULL,
  `sic_delai_paiement_jours` tinyint(3) UNSIGNED DEFAULT NULL,
  `sic_code_remise_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sic_compte_debiteur_cession_interne` varchar(50) DEFAULT NULL,
  `sic_zone_libre` text DEFAULT NULL,
  `sic_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `sic_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `sic_supprime_le` datetime DEFAULT NULL,
  `sic_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sic_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sic_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Infos générales complémentaires société/marque (conditions commerciales, drapeaux risque)';

-- --------------------------------------------------------

--
-- Structure de la table `sav_societes_mandats_prelevement`
--

CREATE TABLE `sav_societes_mandats_prelevement` (
  `smp_id` bigint(20) UNSIGNED NOT NULL,
  `smp_societe_id` bigint(20) UNSIGNED NOT NULL,
  `smp_compte_bancaire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `smp_reference_unique` varchar(35) NOT NULL,
  `smp_type` varchar(20) NOT NULL,
  `smp_date_signature` date DEFAULT NULL,
  `smp_date_premier_prelevement` date DEFAULT NULL,
  `smp_date_fin_validite` date DEFAULT NULL,
  `smp_date_derniere_utilisation` date DEFAULT NULL,
  `smp_est_defaut` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `smp_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `smp_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `smp_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `smp_supprime_le` datetime DEFAULT NULL,
  `smp_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `smp_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `smp_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mandats de prélèvement SEPA des sociétés/marques';

-- --------------------------------------------------------

--
-- Structure de la table `sav_societes_vehicules`
--

CREATE TABLE `sav_societes_vehicules` (
  `sve_id` bigint(20) UNSIGNED NOT NULL,
  `sve_societe_id` bigint(20) UNSIGNED NOT NULL,
  `sve_marque_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sve_code_marque` varchar(20) DEFAULT NULL,
  `sve_type_modele` varchar(100) DEFAULT NULL,
  `sve_chassis` varchar(30) DEFAULT NULL,
  `sve_immatriculation` varchar(20) DEFAULT NULL,
  `sve_numero_moteur` varchar(50) DEFAULT NULL,
  `sve_date_mise_en_circulation` date DEFAULT NULL,
  `sve_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `sve_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `sve_supprime_le` datetime DEFAULT NULL,
  `sve_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sve_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sve_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Parc SAV : véhicules appartenant à une société/marque cliente';

-- --------------------------------------------------------

--
-- Structure de la table `sav_standards`
--

CREATE TABLE `sav_standards` (
  `std_id` bigint(20) UNSIGNED NOT NULL,
  `std_code` varchar(100) NOT NULL,
  `std_nom` varchar(160) NOT NULL,
  `std_societe_proprietaire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `std_type_standard` varchar(80) NOT NULL,
  `std_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `std_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `std_supprime_le` is null and `std_archive_le` is null then 1 else NULL end) VIRTUAL,
  `std_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `std_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `std_supprime_le` datetime DEFAULT NULL,
  `std_archive_le` datetime DEFAULT NULL,
  `std_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `std_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `std_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `std_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Standards versionnés';

-- --------------------------------------------------------

--
-- Structure de la table `sav_statistiques_tables`
--

CREATE TABLE `sav_statistiques_tables` (
  `stb_id` bigint(20) UNSIGNED NOT NULL,
  `stb_table_nom` varchar(128) NOT NULL,
  `stb_lignes_estimees` bigint(20) UNSIGNED DEFAULT NULL,
  `stb_taille_donnees_mo` decimal(12,2) DEFAULT NULL,
  `stb_taille_index_mo` decimal(12,2) DEFAULT NULL,
  `stb_taille_totale_mo` decimal(12,2) DEFAULT NULL,
  `stb_moteur` varchar(40) DEFAULT NULL,
  `stb_collation` varchar(80) DEFAULT NULL,
  `stb_capture_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Captures de volume des tables pour surveillance';

--
-- Déchargement des données de la table `sav_statistiques_tables`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_statuts`
--

CREATE TABLE `sav_statuts` (
  `sta_id` bigint(20) UNSIGNED NOT NULL,
  `sta_domaine` varchar(80) NOT NULL,
  `sta_code` varchar(80) NOT NULL,
  `sta_libelle` varchar(120) NOT NULL,
  `sta_description` text DEFAULT NULL,
  `sta_couleur` varchar(20) DEFAULT NULL,
  `sta_icone` varchar(120) DEFAULT NULL,
  `sta_entite_table` varchar(120) DEFAULT NULL,
  `sta_est_initial` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `sta_est_final` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `sta_est_systeme` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `sta_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `sta_ordre` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `sta_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `sta_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `sta_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Statuts administrables par domaine fonctionnel';

--
-- Déchargement des données de la table `sav_statuts`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_taches`
--

CREATE TABLE `sav_taches` (
  `tac_id` bigint(20) UNSIGNED NOT NULL,
  `tac_uuid` char(36) NOT NULL,
  `tac_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tac_module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tac_titre` varchar(255) NOT NULL,
  `tac_description` text DEFAULT NULL,
  `tac_cible_type` varchar(120) DEFAULT NULL,
  `tac_cible_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tac_assignee_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tac_demandeur_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tac_priorite` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `tac_echeance_le` datetime DEFAULT NULL,
  `tac_terminee_le` datetime DEFAULT NULL,
  `tac_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tac_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `tac_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `tac_supprime_le` datetime DEFAULT NULL,
  `tac_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tac_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tac_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tâches génériques du noyau';

-- --------------------------------------------------------

--
-- Structure de la table `sav_taux_tva`
--

CREATE TABLE `sav_taux_tva` (
  `tva_id` bigint(20) UNSIGNED NOT NULL,
  `tva_pays_id` bigint(20) UNSIGNED NOT NULL,
  `tva_code` varchar(50) NOT NULL,
  `tva_nom` varchar(120) NOT NULL,
  `tva_taux` decimal(5,2) NOT NULL,
  `tva_debute_le` date NOT NULL,
  `tva_termine_le` date DEFAULT NULL,
  `tva_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tva_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `tva_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `tva_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Taux de TVA par pays et période';

-- --------------------------------------------------------

--
-- Structure de la table `sav_tentatives_connexion`
--

CREATE TABLE `sav_tentatives_connexion` (
  `tcn_id` bigint(20) UNSIGNED NOT NULL,
  `tcn_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tcn_email_tente` varchar(191) DEFAULT NULL,
  `tcn_email_normalise` varchar(191) DEFAULT NULL,
  `tcn_adresse_ip` varbinary(16) DEFAULT NULL,
  `tcn_user_agent` varchar(255) DEFAULT NULL,
  `tcn_navigateur` varchar(120) DEFAULT NULL,
  `tcn_appareil` varchar(120) DEFAULT NULL,
  `tcn_succes` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `tcn_raison_echec` varchar(255) DEFAULT NULL,
  `tcn_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tentatives de connexion';

--
-- Déchargement des données de la table `sav_tentatives_connexion`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_traitements_evenements`
--

CREATE TABLE `sav_traitements_evenements` (
  `tev_id` bigint(20) UNSIGNED NOT NULL,
  `tev_file_evenement_id` bigint(20) UNSIGNED NOT NULL,
  `tev_demarre_le` datetime NOT NULL DEFAULT current_timestamp(),
  `tev_termine_le` datetime DEFAULT NULL,
  `tev_succes` tinyint(1) UNSIGNED DEFAULT NULL,
  `tev_message` text DEFAULT NULL,
  `tev_duree_ms` int(10) UNSIGNED DEFAULT NULL,
  `tev_cree_le` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des traitements de la file d’événements';

-- --------------------------------------------------------

--
-- Structure de la table `sav_transitions_statuts`
--

CREATE TABLE `sav_transitions_statuts` (
  `tst_id` bigint(20) UNSIGNED NOT NULL,
  `tst_domaine` varchar(80) NOT NULL,
  `tst_statut_source_id` bigint(20) UNSIGNED NOT NULL,
  `tst_statut_cible_id` bigint(20) UNSIGNED NOT NULL,
  `tst_permission_code` varchar(120) DEFAULT NULL,
  `tst_motif_obligatoire` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `tst_validation_requise` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `tst_notification_requise` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `tst_est_active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `tst_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `tst_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `tst_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Transitions autorisées entre statuts administrables';

-- --------------------------------------------------------

--
-- Structure de la table `sav_types_contacts`
--

CREATE TABLE `sav_types_contacts` (
  `tco_id` bigint(20) UNSIGNED NOT NULL,
  `tco_code` varchar(80) NOT NULL,
  `tco_nom` varchar(120) NOT NULL,
  `tco_description` text DEFAULT NULL,
  `tco_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tco_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `tco_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `tco_supprime_le` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Types de contacts';

-- --------------------------------------------------------

--
-- Structure de la table `sav_types_relations_societes`
--

CREATE TABLE `sav_types_relations_societes` (
  `tre_id` bigint(20) UNSIGNED NOT NULL,
  `tre_code` varchar(80) NOT NULL,
  `tre_nom` varchar(120) NOT NULL,
  `tre_description` text DEFAULT NULL,
  `tre_est_directionnel` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `tre_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tre_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `tre_supprime_le` is null and `tre_archive_le` is null then 1 else NULL end) VIRTUAL,
  `tre_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `tre_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `tre_supprime_le` datetime DEFAULT NULL,
  `tre_archive_le` datetime DEFAULT NULL,
  `tre_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tre_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tre_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tre_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Types de relations entre sociétés';

--
-- Déchargement des données de la table `sav_types_relations_societes`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_types_societes`
--

CREATE TABLE `sav_types_societes` (
  `tso_id` bigint(20) UNSIGNED NOT NULL,
  `tso_code` varchar(80) NOT NULL,
  `tso_nom` varchar(120) NOT NULL,
  `tso_description` text DEFAULT NULL,
  `tso_icone` varchar(120) DEFAULT NULL,
  `tso_logo_fichier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tso_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tso_cle_code_active` tinyint(1) GENERATED ALWAYS AS (case when `tso_supprime_le` is null and `tso_archive_le` is null then 1 else NULL end) VIRTUAL,
  `tso_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `tso_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `tso_supprime_le` datetime DEFAULT NULL,
  `tso_archive_le` datetime DEFAULT NULL,
  `tso_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tso_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tso_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tso_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Types administrables de sociétés';

--
-- Déchargement des données de la table `sav_types_societes`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_utilisateurs`
--

CREATE TABLE `sav_utilisateurs` (
  `uti_id` bigint(20) UNSIGNED NOT NULL,
  `uti_uuid` char(36) NOT NULL,
  `uti_identifiant` varchar(100) DEFAULT NULL,
  `uti_email` varchar(191) NOT NULL,
  `uti_email_normalise` varchar(191) NOT NULL,
  `uti_mot_de_passe_hash` varchar(255) NOT NULL,
  `uti_email_verifie_le` datetime DEFAULT NULL,
  `uti_doit_changer_mot_de_passe` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `uti_mot_de_passe_modifie_le` datetime DEFAULT NULL,
  `uti_mot_de_passe_expire_le` datetime DEFAULT NULL,
  `uti_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uti_est_systeme` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `uti_est_verrouille` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `uti_verrouille_jusqua` datetime DEFAULT NULL,
  `uti_motif_verrouillage` varchar(255) DEFAULT NULL,
  `uti_societe_active_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uti_marque_active_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uti_langue` varchar(10) DEFAULT 'fr',
  `uti_fuseau_horaire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uti_derniere_connexion_le` datetime DEFAULT NULL,
  `uti_derniere_connexion_ip` varbinary(16) DEFAULT NULL,
  `uti_dernier_user_agent` varchar(255) DEFAULT NULL,
  `uti_echecs_connexion` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `uti_anonymise_le` datetime DEFAULT NULL,
  `uti_anonymise_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uti_motif_anonymisation` varchar(255) DEFAULT NULL,
  `uti_cle_email_active` tinyint(1) GENERATED ALWAYS AS (case when `uti_anonymise_le` is null then 1 else NULL end) VIRTUAL,
  `uti_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `uti_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `uti_supprime_le` datetime DEFAULT NULL,
  `uti_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uti_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uti_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uti_acl_version` bigint(20) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Compte utilisateur global minimal';

--
-- Déchargement des données de la table `sav_utilisateurs`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_utilisateurs_comptes_bancaires`
--

CREATE TABLE `sav_utilisateurs_comptes_bancaires` (
  `ucb_id` bigint(20) UNSIGNED NOT NULL,
  `ucb_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `ucb_code_banque` varchar(10) DEFAULT NULL,
  `ucb_code_guichet` varchar(10) DEFAULT NULL,
  `ucb_numero_compte` varchar(20) DEFAULT NULL,
  `ucb_cle_rib` varchar(5) DEFAULT NULL,
  `ucb_iban` varchar(34) DEFAULT NULL,
  `ucb_bic` varchar(11) DEFAULT NULL,
  `ucb_est_principal` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `ucb_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `ucb_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `ucb_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ucb_supprime_le` datetime DEFAULT NULL,
  `ucb_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ucb_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ucb_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comptes bancaires (RIB/IBAN/BIC) des utilisateurs/clients individuels';

-- --------------------------------------------------------

--
-- Structure de la table `sav_utilisateurs_departements`
--

CREATE TABLE `sav_utilisateurs_departements` (
  `udp_id` bigint(20) UNSIGNED NOT NULL,
  `udp_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `udp_societe_id` bigint(20) UNSIGNED NOT NULL,
  `udp_departement_id` bigint(20) UNSIGNED NOT NULL,
  `udp_debute_le` date NOT NULL,
  `udp_termine_le` date DEFAULT NULL,
  `udp_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `udp_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `udp_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `udp_supprime_le` datetime DEFAULT NULL,
  `udp_archive_le` datetime DEFAULT NULL,
  `udp_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `udp_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `udp_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `udp_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Affectation utilisateur / département';

-- --------------------------------------------------------

--
-- Structure de la table `sav_utilisateurs_equipes`
--

CREATE TABLE `sav_utilisateurs_equipes` (
  `ueq_id` bigint(20) UNSIGNED NOT NULL,
  `ueq_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `ueq_societe_id` bigint(20) UNSIGNED NOT NULL,
  `ueq_equipe_id` bigint(20) UNSIGNED NOT NULL,
  `ueq_debute_le` date NOT NULL,
  `ueq_termine_le` date DEFAULT NULL,
  `ueq_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ueq_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `ueq_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ueq_supprime_le` datetime DEFAULT NULL,
  `ueq_archive_le` datetime DEFAULT NULL,
  `ueq_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ueq_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ueq_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ueq_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Affectation utilisateur / équipe';

-- --------------------------------------------------------

--
-- Structure de la table `sav_utilisateurs_infos_complementaires`
--

CREATE TABLE `sav_utilisateurs_infos_complementaires` (
  `uic_id` bigint(20) UNSIGNED NOT NULL,
  `uic_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `uic_code_client` varchar(50) DEFAULT NULL,
  `uic_titre_identite` varchar(80) DEFAULT NULL,
  `uic_numero_piece_identite` varchar(80) DEFAULT NULL,
  `uic_date_delivrance_piece` date DEFAULT NULL,
  `uic_autorite_delivrance` varchar(150) DEFAULT NULL,
  `uic_categorie_socioprofessionnelle` varchar(120) DEFAULT NULL,
  `uic_nature_client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uic_flag_garantie` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `uic_flag_passager` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `uic_flag_releve` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `uic_flag_assureur` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `uic_flag_blocage_facture` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `uic_flag_douteux` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `uic_mode_reglement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uic_plafond_credit` decimal(12,2) DEFAULT NULL,
  `uic_delai_paiement_mois` tinyint(3) UNSIGNED DEFAULT NULL,
  `uic_delai_paiement_jours` tinyint(3) UNSIGNED DEFAULT NULL,
  `uic_code_remise_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uic_zone_libre` text DEFAULT NULL,
  `uic_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `uic_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `uic_supprime_le` datetime DEFAULT NULL,
  `uic_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uic_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uic_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Infos générales complémentaires utilisateur (client individuel, conditions commerciales)';

-- --------------------------------------------------------

--
-- Structure de la table `sav_utilisateurs_mandats_prelevement`
--

CREATE TABLE `sav_utilisateurs_mandats_prelevement` (
  `ump_id` bigint(20) UNSIGNED NOT NULL,
  `ump_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `ump_compte_bancaire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ump_reference_unique` varchar(35) NOT NULL,
  `ump_type` varchar(20) NOT NULL,
  `ump_date_signature` date DEFAULT NULL,
  `ump_date_premier_prelevement` date DEFAULT NULL,
  `ump_date_fin_validite` date DEFAULT NULL,
  `ump_date_derniere_utilisation` date DEFAULT NULL,
  `ump_est_defaut` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `ump_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `ump_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `ump_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ump_supprime_le` datetime DEFAULT NULL,
  `ump_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ump_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ump_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mandats de prélèvement SEPA des utilisateurs/clients individuels';

-- --------------------------------------------------------

--
-- Structure de la table `sav_utilisateurs_services`
--

CREATE TABLE `sav_utilisateurs_services` (
  `usv_id` bigint(20) UNSIGNED NOT NULL,
  `usv_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `usv_societe_id` bigint(20) UNSIGNED NOT NULL,
  `usv_service_id` bigint(20) UNSIGNED NOT NULL,
  `usv_debute_le` date NOT NULL,
  `usv_termine_le` date DEFAULT NULL,
  `usv_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `usv_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `usv_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usv_supprime_le` datetime DEFAULT NULL,
  `usv_archive_le` datetime DEFAULT NULL,
  `usv_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `usv_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `usv_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `usv_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Affectation utilisateur / service';

-- --------------------------------------------------------

--
-- Structure de la table `sav_utilisateurs_vehicules`
--

CREATE TABLE `sav_utilisateurs_vehicules` (
  `uve_id` bigint(20) UNSIGNED NOT NULL,
  `uve_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `uve_marque_societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uve_code_marque` varchar(20) DEFAULT NULL,
  `uve_type_modele` varchar(100) DEFAULT NULL,
  `uve_chassis` varchar(30) DEFAULT NULL,
  `uve_immatriculation` varchar(20) DEFAULT NULL,
  `uve_numero_moteur` varchar(50) DEFAULT NULL,
  `uve_date_mise_en_circulation` date DEFAULT NULL,
  `uve_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `uve_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `uve_supprime_le` datetime DEFAULT NULL,
  `uve_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uve_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uve_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Parc SAV : véhicules appartenant à un utilisateur/client individuel';

-- --------------------------------------------------------

--
-- Structure de la table `sav_verrous_entites`
--

CREATE TABLE `sav_verrous_entites` (
  `ven_id` bigint(20) UNSIGNED NOT NULL,
  `ven_cible_type` varchar(120) NOT NULL,
  `ven_cible_id` bigint(20) UNSIGNED NOT NULL,
  `ven_utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `ven_session_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ven_raison` varchar(255) DEFAULT NULL,
  `ven_verrouille_le` datetime NOT NULL DEFAULT current_timestamp(),
  `ven_expire_le` datetime NOT NULL,
  `ven_libere_le` datetime DEFAULT NULL,
  `ven_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ven_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `ven_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Verrous applicatifs temporaires sur entités sensibles';

-- --------------------------------------------------------

--
-- Structure de la table `sav_verrous_maintenance`
--

CREATE TABLE `sav_verrous_maintenance` (
  `vma_id` bigint(20) UNSIGNED NOT NULL,
  `vma_code` varchar(100) NOT NULL,
  `vma_execution_maintenance_id` bigint(20) UNSIGNED DEFAULT NULL,
  `vma_verrouille_par` varchar(120) DEFAULT NULL,
  `vma_verrouille_le` datetime NOT NULL DEFAULT current_timestamp(),
  `vma_expire_le` datetime NOT NULL,
  `vma_libere_le` datetime DEFAULT NULL,
  `vma_message` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Verrous pour eviter deux maintenances concurrentes';

-- --------------------------------------------------------

--
-- Structure de la table `sav_versions_schema`
--

CREATE TABLE `sav_versions_schema` (
  `vsc_id` bigint(20) UNSIGNED NOT NULL,
  `vsc_code` varchar(80) NOT NULL,
  `vsc_version` varchar(30) NOT NULL,
  `vsc_description` text DEFAULT NULL,
  `vsc_checksum` char(64) DEFAULT NULL,
  `vsc_applique_le` datetime NOT NULL DEFAULT current_timestamp(),
  `vsc_applique_par` varchar(120) DEFAULT NULL,
  `vsc_statut` varchar(30) NOT NULL DEFAULT 'appliquee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des versions de schema appliquees';

--
-- Déchargement des données de la table `sav_versions_schema`
--


-- --------------------------------------------------------

--
-- Structure de la table `sav_versions_standards`
--

CREATE TABLE `sav_versions_standards` (
  `vst_id` bigint(20) UNSIGNED NOT NULL,
  `vst_standard_id` bigint(20) UNSIGNED NOT NULL,
  `vst_version` varchar(40) NOT NULL,
  `vst_valide_du` date NOT NULL,
  `vst_valide_au` date DEFAULT NULL,
  `vst_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `vst_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `vst_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `vst_supprime_le` datetime DEFAULT NULL,
  `vst_archive_le` datetime DEFAULT NULL,
  `vst_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `vst_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `vst_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `vst_archive_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Versions de standards';

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `sav_vue_espaces_applicatifs_actifs`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `sav_vue_espaces_applicatifs_actifs` (
`eap_id` bigint(20) unsigned
,`soc_id` bigint(20) unsigned
,`soc_code` varchar(30)
,`soc_nom` varchar(191)
,`abo_id` bigint(20) unsigned
,`formule_code` varchar(80)
,`formule_nom` varchar(120)
,`statut_espace_code` varchar(80)
,`statut_abonnement_code` varchar(80)
,`eap_bloque_le` datetime
,`eap_motif_blocage` varchar(255)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `sav_vue_etat_maintenance`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `sav_vue_etat_maintenance` (
`pmt_code` varchar(100)
,`pmt_nom` varchar(180)
,`pmt_table_source` varchar(128)
,`pmt_frequence` varchar(30)
,`pmt_derniere_execution_le` datetime
,`pmt_prochaine_execution_le` datetime
,`dernier_statut` varchar(30)
,`exm_lignes_archivees` bigint(20) unsigned
,`exm_lignes_purgees` bigint(20) unsigned
,`derniere_execution_debut` datetime
,`derniere_execution_fin` datetime
,`exm_message` text
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `sav_vue_permissions_roles`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `sav_vue_permissions_roles` (
`rol_id` bigint(20) unsigned
,`rol_code` varchar(100)
,`rol_nom` varchar(120)
,`per_id` bigint(20) unsigned
,`per_code` varchar(120)
,`rpe_effet` enum('autoriser','refuser')
,`mod_code` varchar(80)
,`mod_nom` varchar(120)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `sav_vue_politiques_maintenance_actives`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `sav_vue_politiques_maintenance_actives` (
`pmt_id` bigint(20) unsigned
,`pmt_code` varchar(100)
,`pmt_nom` varchar(180)
,`pmt_type_action` varchar(40)
,`pmt_table_source` varchar(128)
,`pmt_table_archive` varchar(128)
,`pmt_colonne_date` varchar(128)
,`pmt_duree_conservation_jours` int(10) unsigned
,`pmt_archivage_active` tinyint(1) unsigned
,`pmt_purge_active` tinyint(1) unsigned
,`pmt_frequence` varchar(30)
,`pmt_derniere_execution_le` datetime
,`pmt_prochaine_execution_le` datetime
,`pmt_mode_simulation` tinyint(1) unsigned
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `sav_vue_roles_utilisateurs_actifs`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `sav_vue_roles_utilisateurs_actifs` (
`rcu_id` bigint(20) unsigned
,`rcu_utilisateur_id` bigint(20) unsigned
,`uti_email_normalise` varchar(191)
,`rcu_societe_id` bigint(20) unsigned
,`societe_code` varchar(30)
,`rol_code` varchar(100)
,`rol_nom` varchar(120)
,`mod_code` varchar(80)
,`rcu_debute_le` date
,`rcu_termine_le` date
,`statut_code` varchar(80)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `sav_vue_sessions_actives`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `sav_vue_sessions_actives` (
`seu_id` bigint(20) unsigned
,`seu_utilisateur_id` bigint(20) unsigned
,`uti_email_normalise` varchar(191)
,`seu_societe_active_id` bigint(20) unsigned
,`societe_active_code` varchar(30)
,`seu_derniere_activite_le` datetime
,`seu_expire_le` datetime
,`seu_revoquee_le` datetime
,`statut_code` varchar(80)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `sav_vue_societes_actives`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `sav_vue_societes_actives` (
`soc_id` bigint(20) unsigned
,`soc_uuid` char(36)
,`soc_code` varchar(30)
,`soc_nom` varchar(191)
,`soc_nom_legal` varchar(191)
,`soc_email` varchar(191)
,`soc_ville` varchar(100)
,`pay_code_iso2` char(2)
,`pays_nom` varchar(100)
,`statut_code` varchar(80)
,`statut_libelle` varchar(120)
,`soc_est_holding` tinyint(1) unsigned
,`soc_cree_le` datetime
,`soc_modifie_le` datetime
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `sav_vue_utilisateurs_actifs`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `sav_vue_utilisateurs_actifs` (
`uti_id` bigint(20) unsigned
,`uti_uuid` char(36)
,`uti_email_normalise` varchar(191)
,`uti_identifiant` varchar(100)
,`pui_nom` varchar(100)
,`pui_prenom` varchar(100)
,`statut_code` varchar(80)
,`statut_libelle` varchar(120)
,`uti_societe_active_id` bigint(20) unsigned
,`societe_active_code` varchar(30)
,`societe_active_nom` varchar(191)
,`uti_email_verifie_le` datetime
,`uti_est_verrouille` tinyint(1) unsigned
,`uti_derniere_connexion_le` datetime
,`uti_cree_le` datetime
);

-- --------------------------------------------------------

--
-- Structure de la table `sav_webhooks`
--

CREATE TABLE `sav_webhooks` (
  `whk_id` bigint(20) UNSIGNED NOT NULL,
  `whk_connecteur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `whk_code` varchar(100) NOT NULL,
  `whk_nom` varchar(160) NOT NULL,
  `whk_url` varchar(500) NOT NULL,
  `whk_methode` varchar(10) NOT NULL DEFAULT 'POST',
  `whk_evenements_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`whk_evenements_json`)),
  `whk_entetes_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`whk_entetes_json`)),
  `whk_secret_chiffre` varbinary(2048) DEFAULT NULL,
  `whk_est_actif` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `whk_statut_id` bigint(20) UNSIGNED DEFAULT NULL,
  `whk_cree_le` datetime NOT NULL DEFAULT current_timestamp(),
  `whk_modifie_le` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `whk_supprime_le` datetime DEFAULT NULL,
  `whk_cree_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `whk_modifie_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `whk_supprime_par_utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Webhooks sortants configurables';

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `sav_abonnements_evenements`
--
ALTER TABLE `sav_abonnements_evenements`
  ADD PRIMARY KEY (`abe_id`),
  ADD KEY `idx_sav_abe_evenement_actif` (`abe_code_evenement`,`abe_est_actif`),
  ADD KEY `fk_abe_module` (`abe_module_id`),
  ADD KEY `fk_abe_statut` (`abe_statut_id`),
  ADD KEY `fk_abe_cree_par` (`abe_cree_par_utilisateur_id`),
  ADD KEY `fk_abe_modifie_par` (`abe_modifie_par_utilisateur_id`),
  ADD KEY `fk_abe_supprime_par` (`abe_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_abonnements_societes`
--
ALTER TABLE `sav_abonnements_societes`
  ADD PRIMARY KEY (`abo_id`),
  ADD KEY `idx_sav_abo_societe_statut_date` (`abo_societe_id`,`abo_statut_abonnement_id`,`abo_debute_le`,`abo_termine_le`),
  ADD KEY `fk_abo_formule` (`abo_formule_abonnement_id`),
  ADD KEY `fk_abo_statut_abonnement` (`abo_statut_abonnement_id`),
  ADD KEY `fk_abo_statut_paiement` (`abo_statut_paiement_id`);

--
-- Index pour la table `sav_acceptations_documents_juridiques_utilisateurs`
--
ALTER TABLE `sav_acceptations_documents_juridiques_utilisateurs`
  ADD PRIMARY KEY (`adj_id`),
  ADD UNIQUE KEY `uk_sav_adj_acceptation` (`adj_utilisateur_id`,`adj_document_juridique_id`,`adj_version`),
  ADD KEY `fk_adj_document` (`adj_document_juridique_id`);

--
-- Index pour la table `sav_adhesions_utilisateurs_societes`
--
ALTER TABLE `sav_adhesions_utilisateurs_societes`
  ADD PRIMARY KEY (`aus_id`),
  ADD KEY `idx_sav_aus_utilisateur_societe_statut_date` (`aus_utilisateur_id`,`aus_societe_id`,`aus_statut_id`,`aus_debute_le`,`aus_termine_le`),
  ADD KEY `idx_sav_aus_societe_statut` (`aus_societe_id`,`aus_statut_id`),
  ADD KEY `fk_aus_statut` (`aus_statut_id`),
  ADD KEY `fk_aus_cree_par` (`aus_cree_par_utilisateur_id`),
  ADD KEY `idx_sav_aus_soc_stat_sup_arch` (`aus_societe_id`,`aus_statut_id`,`aus_supprime_le`,`aus_archive_le`),
  ADD KEY `idx_aus_utilisateur_societe` (`aus_utilisateur_id`,`aus_societe_id`);

--
-- Index pour la table `sav_affectations_etiquettes`
--
ALTER TABLE `sav_affectations_etiquettes`
  ADD PRIMARY KEY (`afe_id`),
  ADD UNIQUE KEY `uk_sav_afe_etiquette_cible` (`afe_etiquette_id`,`afe_cible_type`,`afe_cible_id`),
  ADD KEY `idx_sav_afe_cible` (`afe_cible_type`,`afe_cible_id`),
  ADD KEY `fk_afe_cree_par` (`afe_cree_par_utilisateur_id`),
  ADD KEY `fk_afe_supprime_par` (`afe_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_affectations_types_societes`
--
ALTER TABLE `sav_affectations_types_societes`
  ADD PRIMARY KEY (`ats_id`),
  ADD UNIQUE KEY `uk_sav_ats_active` (`ats_societe_id`,`ats_type_societe_id`,`ats_debute_le`),
  ADD KEY `idx_sav_ats_societe_statut_date` (`ats_societe_id`,`ats_statut_id`,`ats_debute_le`,`ats_termine_le`),
  ADD KEY `fk_ats_type` (`ats_type_societe_id`),
  ADD KEY `fk_ats_statut` (`ats_statut_id`);

--
-- Index pour la table `sav_archives_evenements_application`
--
ALTER TABLE `sav_archives_evenements_application`
  ADD PRIMARY KEY (`eva_id`),
  ADD UNIQUE KEY `uk_sav_eva_uuid` (`eva_uuid`),
  ADD KEY `idx_sav_eva_code_date` (`eva_code`,`eva_cree_le`),
  ADD KEY `idx_sav_eva_cible` (`eva_cible_type`,`eva_cible_id`),
  ADD KEY `idx_sav_eva_societe_date` (`eva_societe_id`,`eva_cree_le`),
  ADD KEY `fk_eva_module` (`eva_module_id`),
  ADD KEY `fk_eva_emetteur` (`eva_emetteur_utilisateur_id`),
  ADD KEY `fk_eva_statut` (`eva_statut_id`),
  ADD KEY `idx_sav_aea_date` (`eva_cree_le`);

--
-- Index pour la table `sav_archives_historique_contextes_utilisateurs`
--
ALTER TABLE `sav_archives_historique_contextes_utilisateurs`
  ADD PRIMARY KEY (`hcu_id`),
  ADD KEY `idx_sav_hcu_utilisateur_date` (`hcu_utilisateur_id`,`hcu_cree_le`),
  ADD KEY `idx_sav_hcu_societe_date` (`hcu_societe_id`,`hcu_cree_le`),
  ADD KEY `fk_hcu_session` (`hcu_session_utilisateur_id`),
  ADD KEY `idx_sav_ahu_date` (`hcu_cree_le`);

--
-- Index pour la table `sav_archives_journaux_acces_donnees_sensibles`
--
ALTER TABLE `sav_archives_journaux_acces_donnees_sensibles`
  ADD PRIMARY KEY (`jad_id`),
  ADD KEY `idx_sav_jad_source_date` (`jad_utilisateur_source_id`,`jad_cree_le`),
  ADD KEY `idx_sav_jad_cible_date` (`jad_utilisateur_cible_id`,`jad_cree_le`),
  ADD KEY `idx_sav_jad_societe_date` (`jad_societe_id`,`jad_cree_le`),
  ADD KEY `fk_jad_donnee` (`jad_donnee_sensible_utilisateur_id`),
  ADD KEY `idx_sav_jad_action_date` (`jad_action`,`jad_cree_le`),
  ADD KEY `idx_sav_ajsens_date` (`jad_cree_le`);

--
-- Index pour la table `sav_archives_journaux_audit`
--
ALTER TABLE `sav_archives_journaux_audit`
  ADD PRIMARY KEY (`jau_id`),
  ADD KEY `idx_sav_jau_date` (`jau_cree_le`),
  ADD KEY `idx_sav_jau_societe_date` (`jau_societe_id`,`jau_cree_le`),
  ADD KEY `idx_sav_jau_utilisateur_date` (`jau_utilisateur_id`,`jau_cree_le`),
  ADD KEY `idx_sav_jau_cible` (`jau_table_cible`,`jau_id_cible`),
  ADD KEY `idx_sav_aja_date` (`jau_cree_le`);

--
-- Index pour la table `sav_archives_journaux_emails`
--
ALTER TABLE `sav_archives_journaux_emails`
  ADD PRIMARY KEY (`jme_id`),
  ADD KEY `idx_sav_jme_destinataire_date` (`jme_email_destinataire`,`jme_cree_le`),
  ADD KEY `idx_sav_jme_societe_date` (`jme_societe_expediteur_id`,`jme_cree_le`),
  ADD KEY `fk_jme_modele` (`jme_modele_email_id`),
  ADD KEY `fk_jme_destinataire_societe` (`jme_societe_destinataire_id`),
  ADD KEY `fk_jme_destinataire_utilisateur` (`jme_utilisateur_destinataire_id`),
  ADD KEY `fk_jme_statut` (`jme_statut_id`),
  ADD KEY `idx_sav_aje_date` (`jme_cree_le`);

--
-- Index pour la table `sav_archives_journaux_rgpd`
--
ALTER TABLE `sav_archives_journaux_rgpd`
  ADD PRIMARY KEY (`jrg_id`),
  ADD KEY `idx_sav_jrg_date` (`jrg_cree_le`),
  ADD KEY `idx_sav_jrg_utilisateur_date` (`jrg_utilisateur_id`,`jrg_cree_le`),
  ADD KEY `idx_sav_jrg_societe_date` (`jrg_societe_id`,`jrg_cree_le`),
  ADD KEY `idx_sav_ajr_date` (`jrg_cree_le`);

--
-- Index pour la table `sav_archives_journaux_systeme`
--
ALTER TABLE `sav_archives_journaux_systeme`
  ADD PRIMARY KEY (`jsy_id`),
  ADD KEY `idx_sav_jsy_date` (`jsy_cree_le`),
  ADD KEY `idx_sav_jsy_niveau_date` (`jsy_niveau`,`jsy_cree_le`),
  ADD KEY `idx_sav_jsy_societe_date` (`jsy_societe_id`,`jsy_cree_le`),
  ADD KEY `idx_sav_ajs_date` (`jsy_cree_le`);

--
-- Index pour la table `sav_archives_journaux_webhooks`
--
ALTER TABLE `sav_archives_journaux_webhooks`
  ADD PRIMARY KEY (`jwh_id`),
  ADD KEY `idx_sav_jwh_webhook_date` (`jwh_webhook_id`,`jwh_cree_le`),
  ADD KEY `idx_sav_jwh_succes_date` (`jwh_succes`,`jwh_cree_le`),
  ADD KEY `fk_jwh_evenement` (`jwh_evenement_application_id`),
  ADD KEY `idx_sav_ajw_date` (`jwh_cree_le`);

--
-- Index pour la table `sav_archives_sessions_utilisateurs`
--
ALTER TABLE `sav_archives_sessions_utilisateurs`
  ADD PRIMARY KEY (`seu_id`),
  ADD UNIQUE KEY `uk_sav_seu_identifiant_hash` (`seu_identifiant_session_hash`),
  ADD KEY `idx_sav_seu_utilisateur_statut_activite` (`seu_utilisateur_id`,`seu_statut_id`,`seu_derniere_activite_le`),
  ADD KEY `idx_sav_seu_expiration` (`seu_expire_le`),
  ADD KEY `fk_seu_societe_active` (`seu_societe_active_id`),
  ADD KEY `fk_seu_concession_active` (`seu_concession_active_id`),
  ADD KEY `fk_seu_marque_active` (`seu_marque_active_id`),
  ADD KEY `fk_seu_service_actif` (`seu_service_actif_id`),
  ADD KEY `fk_seu_equipe_active` (`seu_equipe_active_id`),
  ADD KEY `fk_seu_statut` (`seu_statut_id`),
  ADD KEY `idx_sav_seu_hash_expire_revoq` (`seu_identifiant_session_hash`,`seu_expire_le`,`seu_revoquee_le`),
  ADD KEY `idx_sav_asu_expire` (`seu_expire_le`);

--
-- Index pour la table `sav_archives_tentatives_connexion`
--
ALTER TABLE `sav_archives_tentatives_connexion`
  ADD PRIMARY KEY (`tcn_id`),
  ADD KEY `idx_sav_tcn_email_date` (`tcn_email_normalise`,`tcn_cree_le`),
  ADD KEY `idx_sav_tcn_ip_date` (`tcn_adresse_ip`,`tcn_cree_le`),
  ADD KEY `idx_sav_tcn_utilisateur_date` (`tcn_utilisateur_id`,`tcn_cree_le`),
  ADD KEY `idx_sav_tcn_email_date_succes` (`tcn_email_normalise`,`tcn_cree_le`,`tcn_succes`),
  ADD KEY `idx_sav_tcn_ip_date_succes` (`tcn_adresse_ip`,`tcn_cree_le`,`tcn_succes`),
  ADD KEY `idx_sav_atc_date` (`tcn_cree_le`);

--
-- Index pour la table `sav_archives_traitements_evenements`
--
ALTER TABLE `sav_archives_traitements_evenements`
  ADD PRIMARY KEY (`tev_id`),
  ADD KEY `idx_sav_tev_file_date` (`tev_file_evenement_id`,`tev_demarre_le`),
  ADD KEY `idx_sav_ate_date` (`tev_cree_le`);

--
-- Index pour la table `sav_blocages_securite`
--
ALTER TABLE `sav_blocages_securite`
  ADD PRIMARY KEY (`bse_id`),
  ADD KEY `idx_sav_bse_utilisateur_statut_date` (`bse_utilisateur_id`,`bse_statut_id`,`bse_commence_le`,`bse_termine_le`),
  ADD KEY `idx_sav_bse_ip_statut_date` (`bse_adresse_ip`,`bse_statut_id`,`bse_commence_le`,`bse_termine_le`),
  ADD KEY `fk_bse_statut` (`bse_statut_id`);

--
-- Index pour la table `sav_canaux_notifications`
--
ALTER TABLE `sav_canaux_notifications`
  ADD PRIMARY KEY (`cno_id`),
  ADD UNIQUE KEY `uk_sav_canaux_notifications_code` (`cno_code`),
  ADD KEY `fk_cno_statut` (`cno_statut_id`),
  ADD KEY `fk_cno_cree_par` (`cno_cree_par_utilisateur_id`),
  ADD KEY `fk_cno_modifie_par` (`cno_modifie_par_utilisateur_id`),
  ADD KEY `fk_cno_supprime_par` (`cno_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_certifications`
--
ALTER TABLE `sav_certifications`
  ADD PRIMARY KEY (`cer_id`),
  ADD UNIQUE KEY `uk_sav_certifications_code_actif` (`cer_code`,`cer_cle_code_active`),
  ADD KEY `fk_cer_societe` (`cer_societe_proprietaire_id`),
  ADD KEY `fk_cer_statut` (`cer_statut_id`);

--
-- Index pour la table `sav_certifications_utilisateurs`
--
ALTER TABLE `sav_certifications_utilisateurs`
  ADD PRIMARY KEY (`ceu_id`),
  ADD KEY `idx_sav_ceu_utilisateur_societe_statut` (`ceu_utilisateur_id`,`ceu_societe_id`,`ceu_statut_id`),
  ADD KEY `idx_sav_ceu_expiration` (`ceu_expire_le`),
  ADD KEY `fk_ceu_certification` (`ceu_certification_id`),
  ADD KEY `fk_ceu_societe` (`ceu_societe_id`),
  ADD KEY `fk_ceu_statut` (`ceu_statut_id`),
  ADD KEY `fk_ceu_preuve_fichier` (`ceu_preuve_fichier_id`),
  ADD KEY `idx_ceu_utilisateur_societe` (`ceu_utilisateur_id`,`ceu_societe_id`);

--
-- Index pour la table `sav_cles_api`
--
ALTER TABLE `sav_cles_api`
  ADD PRIMARY KEY (`cap_id`),
  ADD UNIQUE KEY `uk_sav_cap_uuid` (`cap_uuid`),
  ADD UNIQUE KEY `uk_sav_cap_prefixe_public` (`cap_prefixe_public`),
  ADD KEY `idx_sav_cap_societe_statut` (`cap_societe_id`,`cap_statut_id`),
  ADD KEY `fk_cap_utilisateur` (`cap_utilisateur_id`),
  ADD KEY `fk_cap_connecteur` (`cap_connecteur_id`),
  ADD KEY `fk_cap_statut` (`cap_statut_id`),
  ADD KEY `fk_cap_cree_par` (`cap_cree_par_utilisateur_id`),
  ADD KEY `fk_cap_modifie_par` (`cap_modifie_par_utilisateur_id`),
  ADD KEY `fk_cap_revoque_par` (`cap_revoque_par_utilisateur_id`);

--
-- Index pour la table `sav_codes_remise`
--
ALTER TABLE `sav_codes_remise`
  ADD PRIMARY KEY (`cre_id`),
  ADD UNIQUE KEY `uk_cre_code` (`cre_code`);

--
-- Index pour la table `sav_competences`
--
ALTER TABLE `sav_competences`
  ADD PRIMARY KEY (`cmp_id`),
  ADD UNIQUE KEY `uk_sav_competences_code_actif` (`cmp_code`,`cmp_cle_code_active`),
  ADD KEY `fk_cmp_societe` (`cmp_societe_proprietaire_id`),
  ADD KEY `fk_cmp_statut` (`cmp_statut_id`);

--
-- Index pour la table `sav_competences_utilisateurs`
--
ALTER TABLE `sav_competences_utilisateurs`
  ADD PRIMARY KEY (`cut_id`),
  ADD KEY `idx_sav_cut_utilisateur_societe_statut` (`cut_utilisateur_id`,`cut_societe_id`,`cut_statut_id`),
  ADD KEY `fk_cut_competence` (`cut_competence_id`),
  ADD KEY `fk_cut_niveau` (`cut_niveau_competence_id`),
  ADD KEY `fk_cut_societe` (`cut_societe_id`),
  ADD KEY `fk_cut_statut` (`cut_statut_id`),
  ADD KEY `idx_cut_utilisateur_societe` (`cut_utilisateur_id`,`cut_societe_id`);

--
-- Index pour la table `sav_conditions_politiques_acces`
--
ALTER TABLE `sav_conditions_politiques_acces`
  ADD PRIMARY KEY (`cpa_id`),
  ADD KEY `idx_sav_cpa_politique` (`cpa_politique_acces_id`);

--
-- Index pour la table `sav_connecteurs`
--
ALTER TABLE `sav_connecteurs`
  ADD PRIMARY KEY (`con_id`),
  ADD UNIQUE KEY `uk_sav_connecteurs_uuid` (`con_uuid`),
  ADD UNIQUE KEY `uk_sav_connecteurs_societe_code` (`con_societe_id`,`con_code`),
  ADD KEY `idx_sav_con_type_statut` (`con_type`,`con_statut_id`),
  ADD KEY `fk_con_module` (`con_module_id`),
  ADD KEY `fk_con_statut` (`con_statut_id`),
  ADD KEY `fk_con_cree_par` (`con_cree_par_utilisateur_id`),
  ADD KEY `fk_con_modifie_par` (`con_modifie_par_utilisateur_id`),
  ADD KEY `fk_con_supprime_par` (`con_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_connecteurs_modules`
--
ALTER TABLE `sav_connecteurs_modules`
  ADD PRIMARY KEY (`cmo_id`),
  ADD UNIQUE KEY `uk_sav_cmo_module_code` (`cmo_module_source_id`,`cmo_code`),
  ADD KEY `fk_cmo_module_cible` (`cmo_module_cible_id`),
  ADD KEY `fk_cmo_statut` (`cmo_statut_id`);

--
-- Index pour la table `sav_consentements_rgpd`
--
ALTER TABLE `sav_consentements_rgpd`
  ADD PRIMARY KEY (`crg_id`),
  ADD KEY `idx_sav_crg_utilisateur_finalite` (`crg_utilisateur_id`,`crg_finalite`,`crg_version`),
  ADD KEY `fk_crg_societe` (`crg_societe_id`);

--
-- Index pour la table `sav_contacts_societes`
--
ALTER TABLE `sav_contacts_societes`
  ADD PRIMARY KEY (`cts_id`),
  ADD KEY `idx_sav_cts_societe_type_statut` (`cts_societe_id`,`cts_type_contact_id`,`cts_statut_id`),
  ADD KEY `fk_cts_utilisateur` (`cts_utilisateur_id`),
  ADD KEY `fk_cts_type` (`cts_type_contact_id`),
  ADD KEY `fk_cts_statut` (`cts_statut_id`),
  ADD KEY `fk_cts_etab` (`cts_etablissement_id`);

--
-- Index pour la table `sav_contenus_fichiers`
--
ALTER TABLE `sav_contenus_fichiers`
  ADD PRIMARY KEY (`cfi_id`),
  ADD UNIQUE KEY `uk_sav_contenus_fichiers_fichier` (`cfi_fichier_id`);

--
-- Index pour la table `sav_controles_qualite_base`
--
ALTER TABLE `sav_controles_qualite_base`
  ADD PRIMARY KEY (`cqb_id`),
  ADD UNIQUE KEY `uk_sav_controles_qualite_base_code` (`cqb_code`);

--
-- Index pour la table `sav_demandes_rgpd`
--
ALTER TABLE `sav_demandes_rgpd`
  ADD PRIMARY KEY (`drg_id`),
  ADD UNIQUE KEY `uk_sav_drg_uuid` (`drg_uuid`),
  ADD KEY `idx_sav_drg_utilisateur_statut` (`drg_utilisateur_id`,`drg_statut_id`),
  ADD KEY `idx_sav_drg_societe_statut` (`drg_societe_id`,`drg_statut_id`),
  ADD KEY `fk_drg_traitee_par` (`drg_traitee_par_utilisateur_id`),
  ADD KEY `idx_sav_drg_statut_date` (`drg_statut_id`,`drg_cree_le`);

--
-- Index pour la table `sav_demandes_validation`
--
ALTER TABLE `sav_demandes_validation`
  ADD PRIMARY KEY (`dva_id`),
  ADD UNIQUE KEY `uk_sav_dva_uuid` (`dva_uuid`),
  ADD KEY `idx_sav_dva_societe_statut_type` (`dva_societe_id`,`dva_statut_id`,`dva_type_demande`),
  ADD KEY `idx_sav_dva_demandeur_statut` (`dva_demandeur_utilisateur_id`,`dva_statut_id`),
  ADD KEY `fk_dva_statut` (`dva_statut_id`),
  ADD KEY `idx_sav_dva_validateur_statut` (`dva_validateur_utilisateur_id`,`dva_statut_id`,`dva_cree_le`);

--
-- Index pour la table `sav_departements`
--
ALTER TABLE `sav_departements`
  ADD PRIMARY KEY (`dep_id`),
  ADD UNIQUE KEY `uk_sav_dep_societe_code_actif` (`dep_societe_id`,`dep_code`,`dep_cle_code_active`),
  ADD KEY `idx_sav_dep_societe_statut` (`dep_societe_id`,`dep_statut_id`),
  ADD KEY `fk_dep_statut` (`dep_statut_id`);

--
-- Index pour la table `sav_departements_secteurs`
--
ALTER TABLE `sav_departements_secteurs`
  ADD PRIMARY KEY (`dse_id`),
  ADD UNIQUE KEY `uk_sav_departements_secteurs_dse_lien` (`dse_societe_id`,`dse_departement_id`,`dse_secteur_id`,`dse_debute_le`),
  ADD KEY `idx_sav_departements_secteurs_dse_statut` (`dse_societe_id`,`dse_statut_id`),
  ADD KEY `fk_dse_parent` (`dse_departement_id`),
  ADD KEY `fk_dse_child` (`dse_secteur_id`),
  ADD KEY `fk_dse_statut` (`dse_statut_id`);

--
-- Index pour la table `sav_departements_services`
--
ALTER TABLE `sav_departements_services`
  ADD PRIMARY KEY (`dsv_id`),
  ADD UNIQUE KEY `uk_sav_departements_services_dsv_lien` (`dsv_societe_id`,`dsv_departement_id`,`dsv_service_id`,`dsv_debute_le`),
  ADD KEY `idx_sav_departements_services_dsv_statut` (`dsv_societe_id`,`dsv_statut_id`),
  ADD KEY `fk_dsv_parent` (`dsv_departement_id`),
  ADD KEY `fk_dsv_child` (`dsv_service_id`),
  ADD KEY `fk_dsv_statut` (`dsv_statut_id`);

--
-- Index pour la table `sav_destinataires_notifications`
--
ALTER TABLE `sav_destinataires_notifications`
  ADD PRIMARY KEY (`dno_id`),
  ADD UNIQUE KEY `uk_sav_dno_notification_utilisateur_canal` (`dno_notification_id`,`dno_utilisateur_id`,`dno_canal_notification_id`),
  ADD KEY `idx_sav_dno_utilisateur_statut_lu` (`dno_utilisateur_id`,`dno_statut_id`,`dno_lu_le`),
  ADD KEY `fk_dno_canal` (`dno_canal_notification_id`),
  ADD KEY `fk_dno_statut` (`dno_statut_id`);

--
-- Index pour la table `sav_devises`
--
ALTER TABLE `sav_devises`
  ADD PRIMARY KEY (`dev_id`),
  ADD UNIQUE KEY `uk_sav_devises_code` (`dev_code_iso`),
  ADD KEY `idx_sav_devises_actif` (`dev_est_active`);

--
-- Index pour la table `sav_dirigeants_societes`
--
ALTER TABLE `sav_dirigeants_societes`
  ADD PRIMARY KEY (`dso_id`),
  ADD KEY `idx_dso_societe` (`dso_societe_id`);

--
-- Index pour la table `sav_documents_juridiques`
--
ALTER TABLE `sav_documents_juridiques`
  ADD PRIMARY KEY (`dju_id`),
  ADD KEY `idx_sav_dju_societe_type_version` (`dju_societe_id`,`dju_type_document`,`dju_version`),
  ADD KEY `fk_dju_marque` (`dju_marque_societe_id`),
  ADD KEY `fk_dju_statut` (`dju_statut_id`);

--
-- Index pour la table `sav_donnees_sensibles_utilisateurs`
--
ALTER TABLE `sav_donnees_sensibles_utilisateurs`
  ADD PRIMARY KEY (`dsu_id`),
  ADD UNIQUE KEY `uk_sav_dsu_utilisateur` (`dsu_utilisateur_id`),
  ADD KEY `fk_dsu_cree_par` (`dsu_cree_par_utilisateur_id`),
  ADD KEY `fk_dsu_modifie_par` (`dsu_modifie_par_utilisateur_id`),
  ADD KEY `fk_dsu_supprime_par` (`dsu_supprime_par_utilisateur_id`),
  ADD KEY `fk_dsu_derniere_consultation_par` (`dsu_derniere_consultation_par_utilisateur_id`),
  ADD KEY `fk_dsu_contrat_fichier` (`dsu_contrat_fichier_id`);

--
-- Index pour la table `sav_elements_menus`
--
ALTER TABLE `sav_elements_menus`
  ADD PRIMARY KEY (`eme_id`),
  ADD KEY `idx_sav_eme_menu_parent_position` (`eme_menu_id`,`eme_parent_id`,`eme_position`),
  ADD KEY `fk_eme_parent` (`eme_parent_id`),
  ADD KEY `fk_eme_permission` (`eme_permission_requise_id`),
  ADD KEY `fk_eme_statut` (`eme_statut_id`);

--
-- Index pour la table `sav_equipes`
--
ALTER TABLE `sav_equipes`
  ADD PRIMARY KEY (`equ_id`),
  ADD UNIQUE KEY `uk_sav_equ_societe_code_actif` (`equ_societe_id`,`equ_code`,`equ_cle_code_active`),
  ADD KEY `idx_sav_equ_societe_statut` (`equ_societe_id`,`equ_statut_id`),
  ADD KEY `fk_equ_statut` (`equ_statut_id`);

--
-- Index pour la table `sav_equipes_services`
--
ALTER TABLE `sav_equipes_services`
  ADD PRIMARY KEY (`eqs_id`),
  ADD UNIQUE KEY `uk_sav_equipes_services_eqs_lien` (`eqs_societe_id`,`eqs_equipe_id`,`eqs_service_id`,`eqs_debute_le`),
  ADD KEY `idx_sav_equipes_services_eqs_statut` (`eqs_societe_id`,`eqs_statut_id`),
  ADD KEY `fk_eqs_parent` (`eqs_equipe_id`),
  ADD KEY `fk_eqs_child` (`eqs_service_id`),
  ADD KEY `fk_eqs_statut` (`eqs_statut_id`);

--
-- Index pour la table `sav_espaces_applicatifs`
--
ALTER TABLE `sav_espaces_applicatifs`
  ADD PRIMARY KEY (`eap_id`),
  ADD UNIQUE KEY `uk_sav_espaces_applicatifs_societe` (`eap_societe_id`),
  ADD KEY `idx_sav_eap_societe_statut` (`eap_societe_id`,`eap_statut_id`),
  ADD KEY `fk_eap_abonnement` (`eap_abonnement_societe_id`),
  ADD KEY `fk_eap_statut` (`eap_statut_id`);

--
-- Index pour la table `sav_etablissements_societes`
--
ALTER TABLE `sav_etablissements_societes`
  ADD PRIMARY KEY (`ets_id`),
  ADD KEY `idx_ets_societe` (`ets_societe_id`),
  ADD KEY `idx_ets_pays` (`ets_pays_id`);

--
-- Index pour la table `sav_etiquettes`
--
ALTER TABLE `sav_etiquettes`
  ADD PRIMARY KEY (`eti_id`),
  ADD UNIQUE KEY `uk_sav_eti_societe_code_actif` (`eti_societe_id`,`eti_code`,`eti_cle_code_active`),
  ADD KEY `fk_eti_statut` (`eti_statut_id`),
  ADD KEY `fk_eti_cree_par` (`eti_cree_par_utilisateur_id`),
  ADD KEY `fk_eti_modifie_par` (`eti_modifie_par_utilisateur_id`),
  ADD KEY `fk_eti_supprime_par` (`eti_supprime_par_utilisateur_id`),
  ADD KEY `fk_eti_archive_par` (`eti_archive_par_utilisateur_id`);

--
-- Index pour la table `sav_evenements_application`
--
ALTER TABLE `sav_evenements_application`
  ADD PRIMARY KEY (`eva_id`),
  ADD UNIQUE KEY `uk_sav_eva_uuid` (`eva_uuid`),
  ADD KEY `idx_sav_eva_code_date` (`eva_code`,`eva_cree_le`),
  ADD KEY `idx_sav_eva_cible` (`eva_cible_type`,`eva_cible_id`),
  ADD KEY `idx_sav_eva_societe_date` (`eva_societe_id`,`eva_cree_le`),
  ADD KEY `fk_eva_module` (`eva_module_id`),
  ADD KEY `fk_eva_emetteur` (`eva_emetteur_utilisateur_id`),
  ADD KEY `fk_eva_statut` (`eva_statut_id`);

--
-- Index pour la table `sav_exceptions_horaires_travail`
--
ALTER TABLE `sav_exceptions_horaires_travail`
  ADD PRIMARY KEY (`eht_id`),
  ADD KEY `idx_sav_eht_societe_portee_date` (`eht_societe_id`,`eht_portee_type`,`eht_portee_id`,`eht_date`);

--
-- Index pour la table `sav_executions_maintenance`
--
ALTER TABLE `sav_executions_maintenance`
  ADD PRIMARY KEY (`exm_id`),
  ADD UNIQUE KEY `uk_sav_exm_uuid` (`exm_uuid`),
  ADD KEY `idx_sav_exm_politique_date` (`exm_politique_maintenance_id`,`exm_debut_le`),
  ADD KEY `idx_sav_exm_statut_date` (`exm_statut`,`exm_debut_le`);

--
-- Index pour la table `sav_exigences_versions_standards`
--
ALTER TABLE `sav_exigences_versions_standards`
  ADD PRIMARY KEY (`evs_id`),
  ADD KEY `idx_sav_evs_version_type` (`evs_version_standard_id`,`evs_type_exigence`),
  ADD KEY `fk_evs_competence` (`evs_competence_id`),
  ADD KEY `fk_evs_certification` (`evs_certification_id`),
  ADD KEY `fk_evs_niveau` (`evs_niveau_competence_minimum_id`);

--
-- Index pour la table `sav_fichiers`
--
ALTER TABLE `sav_fichiers`
  ADD PRIMARY KEY (`fic_id`),
  ADD UNIQUE KEY `uk_sav_fichiers_uuid` (`fic_uuid`),
  ADD KEY `idx_sav_fichiers_checksum` (`fic_checksum_sha256`),
  ADD KEY `fk_fic_statut` (`fic_statut_id`),
  ADD KEY `fk_fic_cree_par` (`fic_cree_par_utilisateur_id`);

--
-- Index pour la table `sav_file_evenements`
--
ALTER TABLE `sav_file_evenements`
  ADD PRIMARY KEY (`fev_id`),
  ADD KEY `idx_sav_fev_statut_priorite_date` (`fev_statut_id`,`fev_priorite`,`fev_traitement_apres_le`,`fev_cree_le`),
  ADD KEY `fk_fev_evenement` (`fev_evenement_id`),
  ADD KEY `fk_fev_abonnement` (`fev_abonnement_evenement_id`),
  ADD KEY `idx_sav_fev_traitement` (`fev_statut_id`,`fev_traitement_apres_le`,`fev_priorite`);

--
-- Index pour la table `sav_fonctions`
--
ALTER TABLE `sav_fonctions`
  ADD PRIMARY KEY (`fon_id`),
  ADD UNIQUE KEY `uk_sav_fonctions_code_actif` (`fon_code`,`fon_cle_code_active`),
  ADD KEY `fk_fon_societe_proprietaire` (`fon_societe_proprietaire_id`),
  ADD KEY `fk_fon_type_societe` (`fon_type_societe_id`),
  ADD KEY `fk_fon_statut` (`fon_statut_id`);

--
-- Index pour la table `sav_fonctions_utilisateurs`
--
ALTER TABLE `sav_fonctions_utilisateurs`
  ADD PRIMARY KEY (`fut_id`),
  ADD KEY `idx_sav_fut_utilisateur_societe_statut` (`fut_utilisateur_id`,`fut_societe_id`,`fut_statut_id`),
  ADD KEY `fk_fut_societe` (`fut_societe_id`),
  ADD KEY `fk_fut_fonction` (`fut_fonction_id`),
  ADD KEY `fk_fut_statut` (`fut_statut_id`),
  ADD KEY `idx_fut_utilisateur_societe` (`fut_utilisateur_id`,`fut_societe_id`);

--
-- Index pour la table `sav_formules_abonnement`
--
ALTER TABLE `sav_formules_abonnement`
  ADD PRIMARY KEY (`fab_id`),
  ADD UNIQUE KEY `uk_sav_formules_abonnement_code_actif` (`fab_code`,`fab_cle_code_active`),
  ADD KEY `fk_fab_statut` (`fab_statut_id`);

--
-- Index pour la table `sav_fuseaux_horaires`
--
ALTER TABLE `sav_fuseaux_horaires`
  ADD PRIMARY KEY (`fuh_id`),
  ADD UNIQUE KEY `uk_sav_fuseaux_horaires_nom` (`fuh_nom_iana`),
  ADD KEY `idx_sav_fuseaux_horaires_actif` (`fuh_est_actif`);

--
-- Index pour la table `sav_hierarchie_utilisateurs`
--
ALTER TABLE `sav_hierarchie_utilisateurs`
  ADD PRIMARY KEY (`hiu_id`),
  ADD KEY `idx_sav_hiu_utilisateur_societe_statut` (`hiu_utilisateur_id`,`hiu_societe_id`,`hiu_statut_id`),
  ADD KEY `fk_hiu_societe` (`hiu_societe_id`),
  ADD KEY `fk_hiu_superieur` (`hiu_superieur_utilisateur_id`),
  ADD KEY `fk_hiu_statut` (`hiu_statut_id`);

--
-- Index pour la table `sav_historique_contextes_utilisateurs`
--
ALTER TABLE `sav_historique_contextes_utilisateurs`
  ADD PRIMARY KEY (`hcu_id`),
  ADD KEY `idx_sav_hcu_utilisateur_date` (`hcu_utilisateur_id`,`hcu_cree_le`),
  ADD KEY `idx_sav_hcu_societe_date` (`hcu_societe_id`,`hcu_cree_le`),
  ADD KEY `fk_hcu_session` (`hcu_session_utilisateur_id`);

--
-- Index pour la table `sav_horaires_travail`
--
ALTER TABLE `sav_horaires_travail`
  ADD PRIMARY KEY (`htr_id`),
  ADD KEY `idx_sav_htr_societe_portee_jour` (`htr_societe_id`,`htr_portee_type`,`htr_portee_id`,`htr_jour_semaine`);

--
-- Index pour la table `sav_invitations_utilisateurs`
--
ALTER TABLE `sav_invitations_utilisateurs`
  ADD PRIMARY KEY (`inv_id`),
  ADD UNIQUE KEY `uk_sav_inv_uuid` (`inv_uuid`),
  ADD UNIQUE KEY `uk_sav_inv_jeton_hash` (`inv_jeton_hash`),
  ADD KEY `idx_sav_inv_societe_email_statut` (`inv_societe_id`,`inv_email_normalise`,`inv_statut_id`),
  ADD KEY `fk_inv_statut` (`inv_statut_id`),
  ADD KEY `fk_inv_cree_par` (`inv_cree_par_utilisateur_id`),
  ADD KEY `fk_inv_role_prevu` (`inv_role_prevu_id`),
  ADD KEY `fk_inv_fonction_prevue` (`inv_fonction_prevue_id`);

--
-- Index pour la table `sav_journaux_acces_donnees_sensibles`
--
ALTER TABLE `sav_journaux_acces_donnees_sensibles`
  ADD PRIMARY KEY (`jad_id`),
  ADD KEY `idx_sav_jad_source_date` (`jad_utilisateur_source_id`,`jad_cree_le`),
  ADD KEY `idx_sav_jad_cible_date` (`jad_utilisateur_cible_id`,`jad_cree_le`),
  ADD KEY `idx_sav_jad_societe_date` (`jad_societe_id`,`jad_cree_le`),
  ADD KEY `fk_jad_donnee` (`jad_donnee_sensible_utilisateur_id`),
  ADD KEY `idx_sav_jad_action_date` (`jad_action`,`jad_cree_le`);

--
-- Index pour la table `sav_journaux_audit`
--
ALTER TABLE `sav_journaux_audit`
  ADD PRIMARY KEY (`jau_id`),
  ADD KEY `idx_sav_jau_date` (`jau_cree_le`),
  ADD KEY `idx_sav_jau_societe_date` (`jau_societe_id`,`jau_cree_le`),
  ADD KEY `idx_sav_jau_utilisateur_date` (`jau_utilisateur_id`,`jau_cree_le`),
  ADD KEY `idx_sav_jau_cible` (`jau_table_cible`,`jau_id_cible`),
  ADD KEY `idx_sav_jau_cible_date` (`jau_table_cible`,`jau_id_cible`,`jau_cree_le`);

--
-- Index pour la table `sav_journaux_emails`
--
ALTER TABLE `sav_journaux_emails`
  ADD PRIMARY KEY (`jme_id`),
  ADD KEY `idx_sav_jme_destinataire_date` (`jme_email_destinataire`,`jme_cree_le`),
  ADD KEY `idx_sav_jme_societe_date` (`jme_societe_expediteur_id`,`jme_cree_le`),
  ADD KEY `fk_jme_modele` (`jme_modele_email_id`),
  ADD KEY `fk_jme_destinataire_societe` (`jme_societe_destinataire_id`),
  ADD KEY `fk_jme_destinataire_utilisateur` (`jme_utilisateur_destinataire_id`),
  ADD KEY `fk_jme_statut` (`jme_statut_id`),
  ADD KEY `idx_sav_jme_dest_date_statut` (`jme_email_destinataire`,`jme_cree_le`,`jme_statut_id`);

--
-- Index pour la table `sav_journaux_maintenance`
--
ALTER TABLE `sav_journaux_maintenance`
  ADD PRIMARY KEY (`jma_id`),
  ADD KEY `idx_sav_jma_execution_date` (`jma_execution_maintenance_id`,`jma_cree_le`),
  ADD KEY `idx_sav_jma_niveau_date` (`jma_niveau`,`jma_cree_le`);

--
-- Index pour la table `sav_journaux_rgpd`
--
ALTER TABLE `sav_journaux_rgpd`
  ADD PRIMARY KEY (`jrg_id`),
  ADD KEY `idx_sav_jrg_date` (`jrg_cree_le`),
  ADD KEY `idx_sav_jrg_utilisateur_date` (`jrg_utilisateur_id`,`jrg_cree_le`),
  ADD KEY `idx_sav_jrg_societe_date` (`jrg_societe_id`,`jrg_cree_le`);

--
-- Index pour la table `sav_journaux_systeme`
--
ALTER TABLE `sav_journaux_systeme`
  ADD PRIMARY KEY (`jsy_id`),
  ADD KEY `idx_sav_jsy_date` (`jsy_cree_le`),
  ADD KEY `idx_sav_jsy_niveau_date` (`jsy_niveau`,`jsy_cree_le`),
  ADD KEY `idx_sav_jsy_societe_date` (`jsy_societe_id`,`jsy_cree_le`),
  ADD KEY `idx_sav_jsy_niv_cat_date` (`jsy_niveau`,`jsy_categorie`,`jsy_cree_le`);

--
-- Index pour la table `sav_journaux_webhooks`
--
ALTER TABLE `sav_journaux_webhooks`
  ADD PRIMARY KEY (`jwh_id`),
  ADD KEY `idx_sav_jwh_webhook_date` (`jwh_webhook_id`,`jwh_cree_le`),
  ADD KEY `idx_sav_jwh_succes_date` (`jwh_succes`,`jwh_cree_le`),
  ADD KEY `fk_jwh_evenement` (`jwh_evenement_application_id`);

--
-- Index pour la table `sav_liaisons_fichiers`
--
ALTER TABLE `sav_liaisons_fichiers`
  ADD PRIMARY KEY (`lfi_id`),
  ADD UNIQUE KEY `uk_sav_lfi_fichier_cible_type` (`lfi_fichier_id`,`lfi_cible_type`,`lfi_cible_id`,`lfi_type_liaison`),
  ADD KEY `idx_sav_lfi_cible` (`lfi_cible_type`,`lfi_cible_id`),
  ADD KEY `fk_lfi_statut` (`lfi_statut_id`),
  ADD KEY `fk_lfi_cree_par` (`lfi_cree_par_utilisateur_id`),
  ADD KEY `fk_lfi_modifie_par` (`lfi_modifie_par_utilisateur_id`),
  ADD KEY `fk_lfi_supprime_par` (`lfi_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_liens_documents_juridiques_societes`
--
ALTER TABLE `sav_liens_documents_juridiques_societes`
  ADD PRIMARY KEY (`ldj_id`),
  ADD UNIQUE KEY `uk_sav_ldj_lien` (`ldj_societe_id`,`ldj_document_juridique_id`),
  ADD KEY `fk_ldj_document` (`ldj_document_juridique_id`),
  ADD KEY `fk_ldj_statut` (`ldj_statut_id`);

--
-- Index pour la table `sav_menus`
--
ALTER TABLE `sav_menus`
  ADD PRIMARY KEY (`men_id`),
  ADD UNIQUE KEY `uk_sav_menus_code` (`men_code`),
  ADD KEY `fk_men_module` (`men_module_id`),
  ADD KEY `fk_men_statut` (`men_statut_id`);

--
-- Index pour la table `sav_modeles_emails`
--
ALTER TABLE `sav_modeles_emails`
  ADD PRIMARY KEY (`mel_id`),
  ADD UNIQUE KEY `uk_sav_mel_code` (`mel_code`),
  ADD KEY `fk_mel_statut` (`mel_statut_id`);

--
-- Index pour la table `sav_modeles_notifications`
--
ALTER TABLE `sav_modeles_notifications`
  ADD PRIMARY KEY (`mno_id`),
  ADD UNIQUE KEY `uk_sav_modeles_notifications_code` (`mno_code`),
  ADD KEY `idx_sav_mno_canal_statut` (`mno_canal_notification_id`,`mno_statut_id`),
  ADD KEY `fk_mno_statut` (`mno_statut_id`),
  ADD KEY `fk_mno_cree_par` (`mno_cree_par_utilisateur_id`),
  ADD KEY `fk_mno_modifie_par` (`mno_modifie_par_utilisateur_id`),
  ADD KEY `fk_mno_supprime_par` (`mno_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_modes_reglement`
--
ALTER TABLE `sav_modes_reglement`
  ADD PRIMARY KEY (`mrg_id`),
  ADD UNIQUE KEY `uk_mrg_code` (`mrg_code`);

--
-- Index pour la table `sav_modules`
--
ALTER TABLE `sav_modules`
  ADD PRIMARY KEY (`mod_id`),
  ADD UNIQUE KEY `uk_sav_modules_code_actif` (`mod_code`,`mod_cle_code_active`),
  ADD KEY `fk_mod_statut` (`mod_statut_id`);

--
-- Index pour la table `sav_modules_societes`
--
ALTER TABLE `sav_modules_societes`
  ADD PRIMARY KEY (`mos_id`),
  ADD KEY `idx_sav_mos_societe_module_statut_date` (`mos_societe_id`,`mos_module_id`,`mos_statut_id`,`mos_debute_le`,`mos_termine_le`),
  ADD KEY `fk_mos_module` (`mos_module_id`),
  ADD KEY `fk_mos_formule` (`mos_formule_abonnement_id`),
  ADD KEY `fk_mos_active_par` (`mos_active_par_utilisateur_id`),
  ADD KEY `fk_mos_statut` (`mos_statut_id`),
  ADD KEY `idx_sav_mos_actifs` (`mos_societe_id`,`mos_module_id`,`mos_statut_id`,`mos_debute_le`,`mos_termine_le`);

--
-- Index pour la table `sav_natures_clients`
--
ALTER TABLE `sav_natures_clients`
  ADD PRIMARY KEY (`nac_id`),
  ADD UNIQUE KEY `uk_nac_code` (`nac_code`);

--
-- Index pour la table `sav_niveaux_competences`
--
ALTER TABLE `sav_niveaux_competences`
  ADD PRIMARY KEY (`nco_id`),
  ADD UNIQUE KEY `uk_sav_nco_code` (`nco_code`),
  ADD UNIQUE KEY `uk_sav_nco_rang` (`nco_rang`),
  ADD KEY `fk_nco_statut` (`nco_statut_id`);

--
-- Index pour la table `sav_notes`
--
ALTER TABLE `sav_notes`
  ADD PRIMARY KEY (`nte_id`),
  ADD UNIQUE KEY `uk_sav_notes_uuid` (`nte_uuid`),
  ADD KEY `idx_sav_nte_cible` (`nte_cible_type`,`nte_cible_id`),
  ADD KEY `idx_sav_nte_societe_date` (`nte_societe_id`,`nte_cree_le`),
  ADD KEY `fk_nte_statut` (`nte_statut_id`),
  ADD KEY `fk_nte_cree_par` (`nte_cree_par_utilisateur_id`),
  ADD KEY `fk_nte_modifie_par` (`nte_modifie_par_utilisateur_id`),
  ADD KEY `fk_nte_supprime_par` (`nte_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_notifications`
--
ALTER TABLE `sav_notifications`
  ADD PRIMARY KEY (`not_id`),
  ADD UNIQUE KEY `uk_sav_notifications_uuid` (`not_uuid`),
  ADD KEY `idx_sav_not_societe_statut_date` (`not_societe_id`,`not_statut_id`,`not_cree_le`),
  ADD KEY `idx_sav_not_cible` (`not_cible_type`,`not_cible_id`),
  ADD KEY `idx_sav_not_module` (`not_module_id`),
  ADD KEY `fk_not_modele` (`not_modele_notification_id`),
  ADD KEY `fk_not_statut` (`not_statut_id`),
  ADD KEY `fk_not_cree_par` (`not_cree_par_utilisateur_id`),
  ADD KEY `fk_not_modifie_par` (`not_modifie_par_utilisateur_id`),
  ADD KEY `fk_not_supprime_par` (`not_supprime_par_utilisateur_id`),
  ADD KEY `idx_sav_not_soc_stat_expire` (`not_societe_id`,`not_statut_id`,`not_expire_le`,`not_cree_le`);

--
-- Index pour la table `sav_parametres_application`
--
ALTER TABLE `sav_parametres_application`
  ADD PRIMARY KEY (`pap_id`),
  ADD UNIQUE KEY `uk_sav_parametres_application` (`pap_domaine`,`pap_cle`),
  ADD KEY `idx_sav_parametres_application_domaine` (`pap_domaine`),
  ADD KEY `fk_pap_statut` (`pap_statut_id`);

--
-- Index pour la table `sav_parametres_securite_utilisateurs`
--
ALTER TABLE `sav_parametres_securite_utilisateurs`
  ADD PRIMARY KEY (`psu_id`),
  ADD UNIQUE KEY `uk_sav_psu_utilisateur` (`psu_utilisateur_id`),
  ADD KEY `fk_psu_cree_par` (`psu_cree_par_utilisateur_id`),
  ADD KEY `fk_psu_modifie_par` (`psu_modifie_par_utilisateur_id`),
  ADD KEY `fk_psu_supprime_par` (`psu_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_pays`
--
ALTER TABLE `sav_pays`
  ADD PRIMARY KEY (`pay_id`),
  ADD UNIQUE KEY `uk_sav_pays_iso2` (`pay_code_iso2`),
  ADD UNIQUE KEY `uk_sav_pays_iso3` (`pay_code_iso3`),
  ADD KEY `idx_sav_pays_actif` (`pay_est_actif`);

--
-- Index pour la table `sav_permissions`
--
ALTER TABLE `sav_permissions`
  ADD PRIMARY KEY (`per_id`),
  ADD UNIQUE KEY `uk_sav_permissions_code_actif` (`per_code`,`per_cle_code_active`),
  ADD KEY `idx_sav_permissions_module_statut` (`per_module_id`,`per_statut_id`),
  ADD KEY `fk_per_statut` (`per_statut_id`);

--
-- Index pour la table `sav_politiques_acces`
--
ALTER TABLE `sav_politiques_acces`
  ADD PRIMARY KEY (`pac_id`),
  ADD UNIQUE KEY `uk_sav_pac_code` (`pac_code`),
  ADD KEY `idx_sav_pac_module_permission` (`pac_module_id`,`pac_permission_id`),
  ADD KEY `fk_pac_permission` (`pac_permission_id`),
  ADD KEY `fk_pac_statut` (`pac_statut_id`);

--
-- Index pour la table `sav_politiques_conservation_journaux`
--
ALTER TABLE `sav_politiques_conservation_journaux`
  ADD PRIMARY KEY (`pcj_id`),
  ADD UNIQUE KEY `uk_sav_pcj_type_journal` (`pcj_type_journal`),
  ADD KEY `fk_pcj_statut` (`pcj_statut_id`);

--
-- Index pour la table `sav_politiques_maintenance`
--
ALTER TABLE `sav_politiques_maintenance`
  ADD PRIMARY KEY (`pmt_id`),
  ADD UNIQUE KEY `uk_sav_pmt_code` (`pmt_code`),
  ADD KEY `idx_sav_pmt_active_frequence` (`pmt_est_active`,`pmt_frequence`,`pmt_prochaine_execution_le`),
  ADD KEY `idx_sav_pmt_table` (`pmt_table_source`,`pmt_table_archive`);

--
-- Index pour la table `sav_preferences_notifications`
--
ALTER TABLE `sav_preferences_notifications`
  ADD PRIMARY KEY (`pno_id`),
  ADD UNIQUE KEY `uk_sav_pno_utilisateur_canal_type` (`pno_utilisateur_id`,`pno_canal_notification_id`,`pno_type_notification`),
  ADD KEY `fk_pno_canal` (`pno_canal_notification_id`);

--
-- Index pour la table `sav_profils_utilisateurs`
--
ALTER TABLE `sav_profils_utilisateurs`
  ADD PRIMARY KEY (`pui_id`),
  ADD UNIQUE KEY `uk_sav_profils_utilisateurs_utilisateur` (`pui_utilisateur_id`),
  ADD KEY `fk_pui_pays` (`pui_pays_id`),
  ADD KEY `fk_pui_cree_par` (`pui_cree_par_utilisateur_id`),
  ADD KEY `fk_pui_modifie_par` (`pui_modifie_par_utilisateur_id`),
  ADD KEY `fk_pui_supprime_par` (`pui_supprime_par_utilisateur_id`),
  ADD KEY `fk_pui_photo_fichier` (`pui_photo_fichier_id`),
  ADD KEY `idx_sav_pui_nom_prenom` (`pui_nom`,`pui_prenom`);

--
-- Index pour la table `sav_regles_validation`
--
ALTER TABLE `sav_regles_validation`
  ADD PRIMARY KEY (`rva_id`),
  ADD UNIQUE KEY `uk_sav_rva_societe_code` (`rva_societe_id`,`rva_code`),
  ADD KEY `idx_sav_rva_operation_statut` (`rva_type_operation`,`rva_statut_id`),
  ADD KEY `fk_rva_module` (`rva_module_id`),
  ADD KEY `fk_rva_statut` (`rva_statut_id`),
  ADD KEY `fk_rva_cree_par` (`rva_cree_par_utilisateur_id`),
  ADD KEY `fk_rva_modifie_par` (`rva_modifie_par_utilisateur_id`),
  ADD KEY `fk_rva_supprime_par` (`rva_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_relations_societes`
--
ALTER TABLE `sav_relations_societes`
  ADD PRIMARY KEY (`rso_id`),
  ADD KEY `idx_sav_rso_source_cible_type_statut` (`rso_societe_source_id`,`rso_societe_cible_id`,`rso_type_relation_societe_id`,`rso_statut_id`),
  ADD KEY `idx_sav_rso_cible_type_statut` (`rso_societe_cible_id`,`rso_type_relation_societe_id`,`rso_statut_id`),
  ADD KEY `fk_rso_type` (`rso_type_relation_societe_id`),
  ADD KEY `fk_rso_statut` (`rso_statut_id`),
  ADD KEY `fk_rso_cree_par_societe` (`rso_cree_par_societe_id`),
  ADD KEY `idx_sav_rso_source_actif` (`rso_societe_source_id`,`rso_type_relation_societe_id`,`rso_statut_id`,`rso_termine_le`),
  ADD KEY `idx_sav_rso_cible_actif` (`rso_societe_cible_id`,`rso_type_relation_societe_id`,`rso_statut_id`,`rso_termine_le`);

--
-- Index pour la table `sav_representations_marques_societes`
--
ALTER TABLE `sav_representations_marques_societes`
  ADD PRIMARY KEY (`rma_id`),
  ADD KEY `idx_sav_rma_concession_marque_statut_date` (`rma_concession_societe_id`,`rma_marque_societe_id`,`rma_statut_id`,`rma_debute_le`,`rma_termine_le`),
  ADD KEY `idx_sav_rma_marque_importateur` (`rma_marque_societe_id`,`rma_importateur_societe_id`),
  ADD KEY `fk_rma_importateur` (`rma_importateur_societe_id`),
  ADD KEY `fk_rma_constructeur` (`rma_constructeur_societe_id`),
  ADD KEY `fk_rma_statut` (`rma_statut_id`),
  ADD KEY `idx_sav_rma_conc_marque_actif` (`rma_concession_societe_id`,`rma_marque_societe_id`,`rma_statut_id`,`rma_termine_le`);

--
-- Index pour la table `sav_resultats_controles_base`
--
ALTER TABLE `sav_resultats_controles_base`
  ADD PRIMARY KEY (`rcb_id`),
  ADD KEY `idx_sav_rcb_controle_date` (`rcb_controle_qualite_base_id`,`rcb_execute_le`);

--
-- Index pour la table `sav_roles`
--
ALTER TABLE `sav_roles`
  ADD PRIMARY KEY (`rol_id`),
  ADD UNIQUE KEY `uk_sav_roles_code_module_actif` (`rol_code`,`rol_module_id`,`rol_cle_code_active`),
  ADD KEY `fk_rol_module` (`rol_module_id`),
  ADD KEY `fk_rol_societe_proprietaire` (`rol_societe_proprietaire_id`),
  ADD KEY `fk_rol_type_societe` (`rol_type_societe_id`),
  ADD KEY `fk_rol_statut` (`rol_statut_id`);

--
-- Index pour la table `sav_roles_contextuels_utilisateurs`
--
ALTER TABLE `sav_roles_contextuels_utilisateurs`
  ADD PRIMARY KEY (`rcu_id`),
  ADD KEY `idx_sav_rcu_utilisateur_societe_module_statut` (`rcu_utilisateur_id`,`rcu_societe_id`,`rcu_module_id`,`rcu_statut_id`),
  ADD KEY `idx_sav_rcu_contexte_marque_concession` (`rcu_concession_societe_id`,`rcu_marque_societe_id`,`rcu_service_id`,`rcu_equipe_id`),
  ADD KEY `fk_rcu_role` (`rcu_role_id`),
  ADD KEY `fk_rcu_societe` (`rcu_societe_id`),
  ADD KEY `fk_rcu_marque` (`rcu_marque_societe_id`),
  ADD KEY `fk_rcu_module` (`rcu_module_id`),
  ADD KEY `fk_rcu_statut` (`rcu_statut_id`),
  ADD KEY `idx_sav_rcu_contexte_actif` (`rcu_utilisateur_id`,`rcu_societe_id`,`rcu_module_id`,`rcu_statut_id`,`rcu_debute_le`,`rcu_termine_le`),
  ADD KEY `idx_rcu_utilisateur_societe` (`rcu_utilisateur_id`,`rcu_societe_id`);

--
-- Index pour la table `sav_roles_permissions`
--
ALTER TABLE `sav_roles_permissions`
  ADD PRIMARY KEY (`rpe_id`),
  ADD UNIQUE KEY `uk_sav_roles_permissions_actif` (`rpe_role_id`,`rpe_permission_id`,`rpe_cle_active`),
  ADD KEY `idx_sav_rpe_permission_effet` (`rpe_permission_id`,`rpe_effet`),
  ADD KEY `fk_rpe_cree_utilisateur` (`rpe_cree_par_utilisateur_id`),
  ADD KEY `fk_rpe_mod_utilisateur` (`rpe_modifie_par_utilisateur_id`),
  ADD KEY `fk_rpe_sup_utilisateur` (`rpe_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_secteurs`
--
ALTER TABLE `sav_secteurs`
  ADD PRIMARY KEY (`sec_id`),
  ADD UNIQUE KEY `uk_sav_sec_societe_code_actif` (`sec_societe_id`,`sec_code`,`sec_cle_code_active`),
  ADD KEY `idx_sav_sec_societe_statut` (`sec_societe_id`,`sec_statut_id`),
  ADD KEY `fk_sec_statut` (`sec_statut_id`);

--
-- Index pour la table `sav_secteurs_services`
--
ALTER TABLE `sav_secteurs_services`
  ADD PRIMARY KEY (`ssv_id`),
  ADD UNIQUE KEY `uk_sav_secteurs_services_ssv_lien` (`ssv_societe_id`,`ssv_secteur_id`,`ssv_service_id`,`ssv_debute_le`),
  ADD KEY `idx_sav_secteurs_services_ssv_statut` (`ssv_societe_id`,`ssv_statut_id`),
  ADD KEY `fk_ssv_parent` (`ssv_secteur_id`),
  ADD KEY `fk_ssv_child` (`ssv_service_id`),
  ADD KEY `fk_ssv_statut` (`ssv_statut_id`);

--
-- Index pour la table `sav_services`
--
ALTER TABLE `sav_services`
  ADD PRIMARY KEY (`srv_id`),
  ADD UNIQUE KEY `uk_sav_srv_societe_code_actif` (`srv_societe_id`,`srv_code`,`srv_cle_code_active`),
  ADD KEY `idx_sav_srv_societe_statut` (`srv_societe_id`,`srv_statut_id`),
  ADD KEY `fk_srv_statut` (`srv_statut_id`);

--
-- Index pour la table `sav_services_equipes`
--
ALTER TABLE `sav_services_equipes`
  ADD PRIMARY KEY (`seq_id`),
  ADD UNIQUE KEY `uk_sav_services_equipes_seq_lien` (`seq_societe_id`,`seq_service_id`,`seq_equipe_id`,`seq_debute_le`),
  ADD KEY `idx_sav_services_equipes_seq_statut` (`seq_societe_id`,`seq_statut_id`),
  ADD KEY `fk_seq_parent` (`seq_service_id`),
  ADD KEY `fk_seq_child` (`seq_equipe_id`),
  ADD KEY `fk_seq_statut` (`seq_statut_id`);

--
-- Index pour la table `sav_sessions_utilisateurs`
--
ALTER TABLE `sav_sessions_utilisateurs`
  ADD PRIMARY KEY (`seu_id`),
  ADD UNIQUE KEY `uk_sav_seu_identifiant_hash` (`seu_identifiant_session_hash`),
  ADD KEY `idx_sav_seu_utilisateur_statut_activite` (`seu_utilisateur_id`,`seu_statut_id`,`seu_derniere_activite_le`),
  ADD KEY `idx_sav_seu_expiration` (`seu_expire_le`),
  ADD KEY `fk_seu_societe_active` (`seu_societe_active_id`),
  ADD KEY `fk_seu_concession_active` (`seu_concession_active_id`),
  ADD KEY `fk_seu_marque_active` (`seu_marque_active_id`),
  ADD KEY `fk_seu_service_actif` (`seu_service_actif_id`),
  ADD KEY `fk_seu_equipe_active` (`seu_equipe_active_id`),
  ADD KEY `fk_seu_statut` (`seu_statut_id`),
  ADD KEY `idx_sav_seu_hash_expire_revoq` (`seu_identifiant_session_hash`,`seu_expire_le`,`seu_revoquee_le`),
  ADD KEY `idx_seu_utilisateur_expire` (`seu_utilisateur_id`,`seu_expire_le`);

--
-- Index pour la table `sav_societes`
--
ALTER TABLE `sav_societes`
  ADD PRIMARY KEY (`soc_id`),
  ADD UNIQUE KEY `uk_sav_societes_uuid` (`soc_uuid`),
  ADD UNIQUE KEY `uk_sav_societes_code_actif` (`soc_code`,`soc_cle_code_active`),
  ADD UNIQUE KEY `uk_sav_societes_siret_actif` (`soc_siret`,`soc_cle_siret_active`),
  ADD UNIQUE KEY `uq_soc_siren` (`soc_siren`),
  ADD KEY `idx_sav_societes_nom` (`soc_nom`),
  ADD KEY `idx_sav_societes_statut` (`soc_statut_id`),
  ADD KEY `idx_sav_societes_parent` (`soc_societe_parente_id`),
  ADD KEY `idx_sav_societes_holding` (`soc_holding_id`),
  ADD KEY `fk_soc_cree_par` (`soc_cree_par_utilisateur_id`),
  ADD KEY `fk_soc_modifie_par` (`soc_modifie_par_utilisateur_id`),
  ADD KEY `fk_soc_supprime_par` (`soc_supprime_par_utilisateur_id`),
  ADD KEY `fk_soc_archive_par` (`soc_archive_par_utilisateur_id`),
  ADD KEY `fk_soc_logo_fichier` (`soc_logo_fichier_id`),
  ADD KEY `idx_sav_soc_pays_statut_nom` (`soc_pays_id`,`soc_statut_id`,`soc_nom`),
  ADD KEY `idx_sav_soc_createur_statut` (`soc_cree_par_societe_id`,`soc_statut_id`);

--
-- Index pour la table `sav_societes_comptes_bancaires`
--
ALTER TABLE `sav_societes_comptes_bancaires`
  ADD PRIMARY KEY (`scb_id`),
  ADD KEY `idx_scb_societe` (`scb_societe_id`),
  ADD KEY `fk_scb_cree_par` (`scb_cree_par_utilisateur_id`),
  ADD KEY `fk_scb_modifie_par` (`scb_modifie_par_utilisateur_id`),
  ADD KEY `fk_scb_supprime_par` (`scb_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_societes_infos_complementaires`
--
ALTER TABLE `sav_societes_infos_complementaires`
  ADD PRIMARY KEY (`sic_id`),
  ADD UNIQUE KEY `uk_sic_societe` (`sic_societe_id`),
  ADD KEY `fk_sic_nature_client` (`sic_nature_client_id`),
  ADD KEY `fk_sic_mode_reglement` (`sic_mode_reglement_id`),
  ADD KEY `fk_sic_tva_specifique` (`sic_tva_specifique_id`),
  ADD KEY `fk_sic_code_remise` (`sic_code_remise_id`),
  ADD KEY `fk_sic_cree_par` (`sic_cree_par_utilisateur_id`),
  ADD KEY `fk_sic_modifie_par` (`sic_modifie_par_utilisateur_id`),
  ADD KEY `fk_sic_supprime_par` (`sic_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_societes_mandats_prelevement`
--
ALTER TABLE `sav_societes_mandats_prelevement`
  ADD PRIMARY KEY (`smp_id`),
  ADD UNIQUE KEY `uk_smp_reference` (`smp_reference_unique`),
  ADD KEY `idx_smp_societe` (`smp_societe_id`),
  ADD KEY `fk_smp_compte_bancaire` (`smp_compte_bancaire_id`),
  ADD KEY `fk_smp_cree_par` (`smp_cree_par_utilisateur_id`),
  ADD KEY `fk_smp_modifie_par` (`smp_modifie_par_utilisateur_id`),
  ADD KEY `fk_smp_supprime_par` (`smp_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_societes_vehicules`
--
ALTER TABLE `sav_societes_vehicules`
  ADD PRIMARY KEY (`sve_id`),
  ADD KEY `idx_sve_societe` (`sve_societe_id`),
  ADD KEY `idx_sve_chassis` (`sve_chassis`),
  ADD KEY `idx_sve_immatriculation` (`sve_immatriculation`),
  ADD KEY `fk_sve_marque_societe` (`sve_marque_societe_id`),
  ADD KEY `fk_sve_cree_par` (`sve_cree_par_utilisateur_id`),
  ADD KEY `fk_sve_modifie_par` (`sve_modifie_par_utilisateur_id`),
  ADD KEY `fk_sve_supprime_par` (`sve_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_standards`
--
ALTER TABLE `sav_standards`
  ADD PRIMARY KEY (`std_id`),
  ADD UNIQUE KEY `uk_sav_standards_code_actif` (`std_code`,`std_cle_code_active`),
  ADD KEY `fk_std_societe` (`std_societe_proprietaire_id`),
  ADD KEY `fk_std_statut` (`std_statut_id`);

--
-- Index pour la table `sav_statistiques_tables`
--
ALTER TABLE `sav_statistiques_tables`
  ADD PRIMARY KEY (`stb_id`),
  ADD KEY `idx_sav_stb_table_date` (`stb_table_nom`,`stb_capture_le`),
  ADD KEY `idx_sav_stb_taille` (`stb_taille_totale_mo`,`stb_capture_le`);

--
-- Index pour la table `sav_statuts`
--
ALTER TABLE `sav_statuts`
  ADD PRIMARY KEY (`sta_id`),
  ADD UNIQUE KEY `uk_sav_statuts_domaine_code` (`sta_domaine`,`sta_code`),
  ADD KEY `idx_sav_statuts_domaine_actif` (`sta_domaine`,`sta_est_actif`);

--
-- Index pour la table `sav_taches`
--
ALTER TABLE `sav_taches`
  ADD PRIMARY KEY (`tac_id`),
  ADD UNIQUE KEY `uk_sav_taches_uuid` (`tac_uuid`),
  ADD KEY `idx_sav_tac_assignee_statut_echeance` (`tac_assignee_utilisateur_id`,`tac_statut_id`,`tac_echeance_le`),
  ADD KEY `idx_sav_tac_societe_statut` (`tac_societe_id`,`tac_statut_id`),
  ADD KEY `idx_sav_tac_cible` (`tac_cible_type`,`tac_cible_id`),
  ADD KEY `fk_tac_module` (`tac_module_id`),
  ADD KEY `fk_tac_demandeur` (`tac_demandeur_utilisateur_id`),
  ADD KEY `fk_tac_statut` (`tac_statut_id`),
  ADD KEY `fk_tac_cree_par` (`tac_cree_par_utilisateur_id`),
  ADD KEY `fk_tac_modifie_par` (`tac_modifie_par_utilisateur_id`),
  ADD KEY `fk_tac_supprime_par` (`tac_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_taux_tva`
--
ALTER TABLE `sav_taux_tva`
  ADD PRIMARY KEY (`tva_id`),
  ADD UNIQUE KEY `uk_sav_taux_tva_pays_code_debut` (`tva_pays_id`,`tva_code`,`tva_debute_le`),
  ADD KEY `idx_sav_taux_tva_pays_date` (`tva_pays_id`,`tva_debute_le`,`tva_termine_le`),
  ADD KEY `fk_tva_statut` (`tva_statut_id`);

--
-- Index pour la table `sav_tentatives_connexion`
--
ALTER TABLE `sav_tentatives_connexion`
  ADD PRIMARY KEY (`tcn_id`),
  ADD KEY `idx_sav_tcn_email_date` (`tcn_email_normalise`,`tcn_cree_le`),
  ADD KEY `idx_sav_tcn_ip_date` (`tcn_adresse_ip`,`tcn_cree_le`),
  ADD KEY `idx_sav_tcn_utilisateur_date` (`tcn_utilisateur_id`,`tcn_cree_le`),
  ADD KEY `idx_sav_tcn_email_date_succes` (`tcn_email_normalise`,`tcn_cree_le`,`tcn_succes`),
  ADD KEY `idx_sav_tcn_ip_date_succes` (`tcn_adresse_ip`,`tcn_cree_le`,`tcn_succes`);

--
-- Index pour la table `sav_traitements_evenements`
--
ALTER TABLE `sav_traitements_evenements`
  ADD PRIMARY KEY (`tev_id`),
  ADD KEY `idx_sav_tev_file_date` (`tev_file_evenement_id`,`tev_demarre_le`);

--
-- Index pour la table `sav_transitions_statuts`
--
ALTER TABLE `sav_transitions_statuts`
  ADD PRIMARY KEY (`tst_id`),
  ADD UNIQUE KEY `uk_sav_transitions_statuts` (`tst_domaine`,`tst_statut_source_id`,`tst_statut_cible_id`),
  ADD KEY `fk_tst_statut_source` (`tst_statut_source_id`),
  ADD KEY `fk_tst_statut_cible` (`tst_statut_cible_id`);

--
-- Index pour la table `sav_types_contacts`
--
ALTER TABLE `sav_types_contacts`
  ADD PRIMARY KEY (`tco_id`),
  ADD UNIQUE KEY `uk_sav_tco_code` (`tco_code`),
  ADD KEY `fk_tco_statut` (`tco_statut_id`);

--
-- Index pour la table `sav_types_relations_societes`
--
ALTER TABLE `sav_types_relations_societes`
  ADD PRIMARY KEY (`tre_id`),
  ADD UNIQUE KEY `uk_sav_tre_code_actif` (`tre_code`,`tre_cle_code_active`),
  ADD KEY `fk_tre_statut` (`tre_statut_id`);

--
-- Index pour la table `sav_types_societes`
--
ALTER TABLE `sav_types_societes`
  ADD PRIMARY KEY (`tso_id`),
  ADD UNIQUE KEY `uk_sav_types_societes_code_actif` (`tso_code`,`tso_cle_code_active`),
  ADD KEY `fk_tso_statut` (`tso_statut_id`),
  ADD KEY `fk_tso_logo_fichier` (`tso_logo_fichier_id`);

--
-- Index pour la table `sav_utilisateurs`
--
ALTER TABLE `sav_utilisateurs`
  ADD PRIMARY KEY (`uti_id`),
  ADD UNIQUE KEY `uk_sav_utilisateurs_uuid` (`uti_uuid`),
  ADD UNIQUE KEY `uk_sav_utilisateurs_email_actif` (`uti_email_normalise`,`uti_cle_email_active`),
  ADD KEY `idx_sav_utilisateurs_statut` (`uti_statut_id`),
  ADD KEY `idx_sav_utilisateurs_societe_active` (`uti_societe_active_id`),
  ADD KEY `fk_uti_marque_active` (`uti_marque_active_id`),
  ADD KEY `fk_uti_fuseau` (`uti_fuseau_horaire_id`),
  ADD KEY `fk_uti_anonymise_par` (`uti_anonymise_par_utilisateur_id`),
  ADD KEY `fk_uti_cree_par` (`uti_cree_par_utilisateur_id`),
  ADD KEY `fk_uti_modifie_par` (`uti_modifie_par_utilisateur_id`),
  ADD KEY `fk_uti_supprime_par` (`uti_supprime_par_utilisateur_id`),
  ADD KEY `idx_sav_uti_email_statut_suppr` (`uti_email_normalise`,`uti_statut_id`,`uti_supprime_le`),
  ADD KEY `idx_sav_uti_societe_statut` (`uti_societe_active_id`,`uti_statut_id`),
  ADD KEY `idx_uti_id_acl_version` (`uti_id`,`uti_acl_version`);

--
-- Index pour la table `sav_utilisateurs_comptes_bancaires`
--
ALTER TABLE `sav_utilisateurs_comptes_bancaires`
  ADD PRIMARY KEY (`ucb_id`),
  ADD KEY `idx_ucb_utilisateur` (`ucb_utilisateur_id`),
  ADD KEY `fk_ucb_cree_par` (`ucb_cree_par_utilisateur_id`),
  ADD KEY `fk_ucb_modifie_par` (`ucb_modifie_par_utilisateur_id`),
  ADD KEY `fk_ucb_supprime_par` (`ucb_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_utilisateurs_departements`
--
ALTER TABLE `sav_utilisateurs_departements`
  ADD PRIMARY KEY (`udp_id`),
  ADD KEY `idx_sav_utilisateurs_departements_udp_utilisateur_societe_statut` (`udp_utilisateur_id`,`udp_societe_id`,`udp_statut_id`),
  ADD KEY `fk_udp_societe` (`udp_societe_id`),
  ADD KEY `fk_udp_cible` (`udp_departement_id`),
  ADD KEY `fk_udp_statut` (`udp_statut_id`);

--
-- Index pour la table `sav_utilisateurs_equipes`
--
ALTER TABLE `sav_utilisateurs_equipes`
  ADD PRIMARY KEY (`ueq_id`),
  ADD KEY `idx_sav_utilisateurs_equipes_ueq_utilisateur_societe_statut` (`ueq_utilisateur_id`,`ueq_societe_id`,`ueq_statut_id`),
  ADD KEY `fk_ueq_societe` (`ueq_societe_id`),
  ADD KEY `fk_ueq_cible` (`ueq_equipe_id`),
  ADD KEY `fk_ueq_statut` (`ueq_statut_id`);

--
-- Index pour la table `sav_utilisateurs_infos_complementaires`
--
ALTER TABLE `sav_utilisateurs_infos_complementaires`
  ADD PRIMARY KEY (`uic_id`),
  ADD UNIQUE KEY `uk_uic_utilisateur` (`uic_utilisateur_id`),
  ADD KEY `fk_uic_nature_client` (`uic_nature_client_id`),
  ADD KEY `fk_uic_mode_reglement` (`uic_mode_reglement_id`),
  ADD KEY `fk_uic_code_remise` (`uic_code_remise_id`),
  ADD KEY `fk_uic_cree_par` (`uic_cree_par_utilisateur_id`),
  ADD KEY `fk_uic_modifie_par` (`uic_modifie_par_utilisateur_id`),
  ADD KEY `fk_uic_supprime_par` (`uic_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_utilisateurs_mandats_prelevement`
--
ALTER TABLE `sav_utilisateurs_mandats_prelevement`
  ADD PRIMARY KEY (`ump_id`),
  ADD UNIQUE KEY `uk_ump_reference` (`ump_reference_unique`),
  ADD KEY `idx_ump_utilisateur` (`ump_utilisateur_id`),
  ADD KEY `fk_ump_compte_bancaire` (`ump_compte_bancaire_id`),
  ADD KEY `fk_ump_cree_par` (`ump_cree_par_utilisateur_id`),
  ADD KEY `fk_ump_modifie_par` (`ump_modifie_par_utilisateur_id`),
  ADD KEY `fk_ump_supprime_par` (`ump_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_utilisateurs_services`
--
ALTER TABLE `sav_utilisateurs_services`
  ADD PRIMARY KEY (`usv_id`),
  ADD KEY `idx_sav_utilisateurs_services_usv_utilisateur_societe_statut` (`usv_utilisateur_id`,`usv_societe_id`,`usv_statut_id`),
  ADD KEY `fk_usv_societe` (`usv_societe_id`),
  ADD KEY `fk_usv_cible` (`usv_service_id`),
  ADD KEY `fk_usv_statut` (`usv_statut_id`);

--
-- Index pour la table `sav_utilisateurs_vehicules`
--
ALTER TABLE `sav_utilisateurs_vehicules`
  ADD PRIMARY KEY (`uve_id`),
  ADD KEY `idx_uve_utilisateur` (`uve_utilisateur_id`),
  ADD KEY `idx_uve_chassis` (`uve_chassis`),
  ADD KEY `idx_uve_immatriculation` (`uve_immatriculation`),
  ADD KEY `fk_uve_marque_societe` (`uve_marque_societe_id`),
  ADD KEY `fk_uve_cree_par` (`uve_cree_par_utilisateur_id`),
  ADD KEY `fk_uve_modifie_par` (`uve_modifie_par_utilisateur_id`),
  ADD KEY `fk_uve_supprime_par` (`uve_supprime_par_utilisateur_id`);

--
-- Index pour la table `sav_verrous_entites`
--
ALTER TABLE `sav_verrous_entites`
  ADD PRIMARY KEY (`ven_id`),
  ADD KEY `idx_sav_ven_cible_expiration` (`ven_cible_type`,`ven_cible_id`,`ven_expire_le`,`ven_libere_le`),
  ADD KEY `idx_sav_ven_utilisateur` (`ven_utilisateur_id`),
  ADD KEY `fk_ven_session` (`ven_session_utilisateur_id`),
  ADD KEY `idx_sav_ven_statut_expire` (`ven_statut_id`,`ven_expire_le`,`ven_libere_le`);

--
-- Index pour la table `sav_verrous_maintenance`
--
ALTER TABLE `sav_verrous_maintenance`
  ADD PRIMARY KEY (`vma_id`),
  ADD UNIQUE KEY `uk_sav_vma_code_actif` (`vma_code`,`vma_libere_le`),
  ADD KEY `idx_sav_vma_expire` (`vma_expire_le`,`vma_libere_le`),
  ADD KEY `fk_vma_execution` (`vma_execution_maintenance_id`);

--
-- Index pour la table `sav_versions_schema`
--
ALTER TABLE `sav_versions_schema`
  ADD PRIMARY KEY (`vsc_id`),
  ADD UNIQUE KEY `uk_sav_versions_schema_code` (`vsc_code`);

--
-- Index pour la table `sav_versions_standards`
--
ALTER TABLE `sav_versions_standards`
  ADD PRIMARY KEY (`vst_id`),
  ADD UNIQUE KEY `uk_sav_versions_standards` (`vst_standard_id`,`vst_version`),
  ADD KEY `idx_sav_vst_standard_dates` (`vst_standard_id`,`vst_valide_du`,`vst_valide_au`),
  ADD KEY `fk_vst_statut` (`vst_statut_id`);

--
-- Index pour la table `sav_webhooks`
--
ALTER TABLE `sav_webhooks`
  ADD PRIMARY KEY (`whk_id`),
  ADD UNIQUE KEY `uk_sav_webhooks_connecteur_code` (`whk_connecteur_id`,`whk_code`),
  ADD KEY `idx_sav_whk_actif` (`whk_est_actif`),
  ADD KEY `fk_whk_statut` (`whk_statut_id`),
  ADD KEY `fk_whk_cree_par` (`whk_cree_par_utilisateur_id`),
  ADD KEY `fk_whk_modifie_par` (`whk_modifie_par_utilisateur_id`),
  ADD KEY `fk_whk_supprime_par` (`whk_supprime_par_utilisateur_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `sav_abonnements_evenements`
--
ALTER TABLE `sav_abonnements_evenements`
  MODIFY `abe_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_abonnements_societes`
--
ALTER TABLE `sav_abonnements_societes`
  MODIFY `abo_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `sav_acceptations_documents_juridiques_utilisateurs`
--
ALTER TABLE `sav_acceptations_documents_juridiques_utilisateurs`
  MODIFY `adj_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_adhesions_utilisateurs_societes`
--
ALTER TABLE `sav_adhesions_utilisateurs_societes`
  MODIFY `aus_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT pour la table `sav_affectations_etiquettes`
--
ALTER TABLE `sav_affectations_etiquettes`
  MODIFY `afe_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_affectations_types_societes`
--
ALTER TABLE `sav_affectations_types_societes`
  MODIFY `ats_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT pour la table `sav_archives_evenements_application`
--
ALTER TABLE `sav_archives_evenements_application`
  MODIFY `eva_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_archives_historique_contextes_utilisateurs`
--
ALTER TABLE `sav_archives_historique_contextes_utilisateurs`
  MODIFY `hcu_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_archives_journaux_acces_donnees_sensibles`
--
ALTER TABLE `sav_archives_journaux_acces_donnees_sensibles`
  MODIFY `jad_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_archives_journaux_audit`
--
ALTER TABLE `sav_archives_journaux_audit`
  MODIFY `jau_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_archives_journaux_emails`
--
ALTER TABLE `sav_archives_journaux_emails`
  MODIFY `jme_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_archives_journaux_rgpd`
--
ALTER TABLE `sav_archives_journaux_rgpd`
  MODIFY `jrg_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_archives_journaux_systeme`
--
ALTER TABLE `sav_archives_journaux_systeme`
  MODIFY `jsy_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_archives_journaux_webhooks`
--
ALTER TABLE `sav_archives_journaux_webhooks`
  MODIFY `jwh_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_archives_sessions_utilisateurs`
--
ALTER TABLE `sav_archives_sessions_utilisateurs`
  MODIFY `seu_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_archives_tentatives_connexion`
--
ALTER TABLE `sav_archives_tentatives_connexion`
  MODIFY `tcn_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_archives_traitements_evenements`
--
ALTER TABLE `sav_archives_traitements_evenements`
  MODIFY `tev_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_blocages_securite`
--
ALTER TABLE `sav_blocages_securite`
  MODIFY `bse_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_canaux_notifications`
--
ALTER TABLE `sav_canaux_notifications`
  MODIFY `cno_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `sav_certifications`
--
ALTER TABLE `sav_certifications`
  MODIFY `cer_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `sav_certifications_utilisateurs`
--
ALTER TABLE `sav_certifications_utilisateurs`
  MODIFY `ceu_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_cles_api`
--
ALTER TABLE `sav_cles_api`
  MODIFY `cap_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_codes_remise`
--
ALTER TABLE `sav_codes_remise`
  MODIFY `cre_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `sav_competences`
--
ALTER TABLE `sav_competences`
  MODIFY `cmp_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `sav_competences_utilisateurs`
--
ALTER TABLE `sav_competences_utilisateurs`
  MODIFY `cut_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `sav_conditions_politiques_acces`
--
ALTER TABLE `sav_conditions_politiques_acces`
  MODIFY `cpa_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_connecteurs`
--
ALTER TABLE `sav_connecteurs`
  MODIFY `con_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_connecteurs_modules`
--
ALTER TABLE `sav_connecteurs_modules`
  MODIFY `cmo_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_consentements_rgpd`
--
ALTER TABLE `sav_consentements_rgpd`
  MODIFY `crg_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_contacts_societes`
--
ALTER TABLE `sav_contacts_societes`
  MODIFY `cts_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_contenus_fichiers`
--
ALTER TABLE `sav_contenus_fichiers`
  MODIFY `cfi_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_controles_qualite_base`
--
ALTER TABLE `sav_controles_qualite_base`
  MODIFY `cqb_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `sav_demandes_rgpd`
--
ALTER TABLE `sav_demandes_rgpd`
  MODIFY `drg_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `sav_demandes_validation`
--
ALTER TABLE `sav_demandes_validation`
  MODIFY `dva_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_departements`
--
ALTER TABLE `sav_departements`
  MODIFY `dep_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_departements_secteurs`
--
ALTER TABLE `sav_departements_secteurs`
  MODIFY `dse_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_departements_services`
--
ALTER TABLE `sav_departements_services`
  MODIFY `dsv_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_destinataires_notifications`
--
ALTER TABLE `sav_destinataires_notifications`
  MODIFY `dno_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_devises`
--
ALTER TABLE `sav_devises`
  MODIFY `dev_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `sav_dirigeants_societes`
--
ALTER TABLE `sav_dirigeants_societes`
  MODIFY `dso_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_documents_juridiques`
--
ALTER TABLE `sav_documents_juridiques`
  MODIFY `dju_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_donnees_sensibles_utilisateurs`
--
ALTER TABLE `sav_donnees_sensibles_utilisateurs`
  MODIFY `dsu_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_elements_menus`
--
ALTER TABLE `sav_elements_menus`
  MODIFY `eme_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_equipes`
--
ALTER TABLE `sav_equipes`
  MODIFY `equ_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_equipes_services`
--
ALTER TABLE `sav_equipes_services`
  MODIFY `eqs_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_espaces_applicatifs`
--
ALTER TABLE `sav_espaces_applicatifs`
  MODIFY `eap_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `sav_etablissements_societes`
--
ALTER TABLE `sav_etablissements_societes`
  MODIFY `ets_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_etiquettes`
--
ALTER TABLE `sav_etiquettes`
  MODIFY `eti_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_evenements_application`
--
ALTER TABLE `sav_evenements_application`
  MODIFY `eva_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_exceptions_horaires_travail`
--
ALTER TABLE `sav_exceptions_horaires_travail`
  MODIFY `eht_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_executions_maintenance`
--
ALTER TABLE `sav_executions_maintenance`
  MODIFY `exm_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_exigences_versions_standards`
--
ALTER TABLE `sav_exigences_versions_standards`
  MODIFY `evs_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_fichiers`
--
ALTER TABLE `sav_fichiers`
  MODIFY `fic_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_file_evenements`
--
ALTER TABLE `sav_file_evenements`
  MODIFY `fev_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_fonctions`
--
ALTER TABLE `sav_fonctions`
  MODIFY `fon_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT pour la table `sav_fonctions_utilisateurs`
--
ALTER TABLE `sav_fonctions_utilisateurs`
  MODIFY `fut_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT pour la table `sav_formules_abonnement`
--
ALTER TABLE `sav_formules_abonnement`
  MODIFY `fab_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `sav_fuseaux_horaires`
--
ALTER TABLE `sav_fuseaux_horaires`
  MODIFY `fuh_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `sav_hierarchie_utilisateurs`
--
ALTER TABLE `sav_hierarchie_utilisateurs`
  MODIFY `hiu_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT pour la table `sav_historique_contextes_utilisateurs`
--
ALTER TABLE `sav_historique_contextes_utilisateurs`
  MODIFY `hcu_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT pour la table `sav_horaires_travail`
--
ALTER TABLE `sav_horaires_travail`
  MODIFY `htr_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_invitations_utilisateurs`
--
ALTER TABLE `sav_invitations_utilisateurs`
  MODIFY `inv_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_journaux_acces_donnees_sensibles`
--
ALTER TABLE `sav_journaux_acces_donnees_sensibles`
  MODIFY `jad_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_journaux_audit`
--
ALTER TABLE `sav_journaux_audit`
  MODIFY `jau_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_journaux_emails`
--
ALTER TABLE `sav_journaux_emails`
  MODIFY `jme_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_journaux_maintenance`
--
ALTER TABLE `sav_journaux_maintenance`
  MODIFY `jma_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_journaux_rgpd`
--
ALTER TABLE `sav_journaux_rgpd`
  MODIFY `jrg_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_journaux_systeme`
--
ALTER TABLE `sav_journaux_systeme`
  MODIFY `jsy_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `sav_journaux_webhooks`
--
ALTER TABLE `sav_journaux_webhooks`
  MODIFY `jwh_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_liaisons_fichiers`
--
ALTER TABLE `sav_liaisons_fichiers`
  MODIFY `lfi_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_liens_documents_juridiques_societes`
--
ALTER TABLE `sav_liens_documents_juridiques_societes`
  MODIFY `ldj_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_menus`
--
ALTER TABLE `sav_menus`
  MODIFY `men_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_modeles_emails`
--
ALTER TABLE `sav_modeles_emails`
  MODIFY `mel_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_modeles_notifications`
--
ALTER TABLE `sav_modeles_notifications`
  MODIFY `mno_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_modes_reglement`
--
ALTER TABLE `sav_modes_reglement`
  MODIFY `mrg_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `sav_modules`
--
ALTER TABLE `sav_modules`
  MODIFY `mod_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `sav_modules_societes`
--
ALTER TABLE `sav_modules_societes`
  MODIFY `mos_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `sav_natures_clients`
--
ALTER TABLE `sav_natures_clients`
  MODIFY `nac_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `sav_niveaux_competences`
--
ALTER TABLE `sav_niveaux_competences`
  MODIFY `nco_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `sav_notes`
--
ALTER TABLE `sav_notes`
  MODIFY `nte_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_notifications`
--
ALTER TABLE `sav_notifications`
  MODIFY `not_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_parametres_application`
--
ALTER TABLE `sav_parametres_application`
  MODIFY `pap_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `sav_parametres_securite_utilisateurs`
--
ALTER TABLE `sav_parametres_securite_utilisateurs`
  MODIFY `psu_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `sav_pays`
--
ALTER TABLE `sav_pays`
  MODIFY `pay_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `sav_permissions`
--
ALTER TABLE `sav_permissions`
  MODIFY `per_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=265;

--
-- AUTO_INCREMENT pour la table `sav_politiques_acces`
--
ALTER TABLE `sav_politiques_acces`
  MODIFY `pac_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_politiques_conservation_journaux`
--
ALTER TABLE `sav_politiques_conservation_journaux`
  MODIFY `pcj_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `sav_politiques_maintenance`
--
ALTER TABLE `sav_politiques_maintenance`
  MODIFY `pmt_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `sav_preferences_notifications`
--
ALTER TABLE `sav_preferences_notifications`
  MODIFY `pno_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_profils_utilisateurs`
--
ALTER TABLE `sav_profils_utilisateurs`
  MODIFY `pui_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT pour la table `sav_regles_validation`
--
ALTER TABLE `sav_regles_validation`
  MODIFY `rva_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_relations_societes`
--
ALTER TABLE `sav_relations_societes`
  MODIFY `rso_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `sav_representations_marques_societes`
--
ALTER TABLE `sav_representations_marques_societes`
  MODIFY `rma_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT pour la table `sav_resultats_controles_base`
--
ALTER TABLE `sav_resultats_controles_base`
  MODIFY `rcb_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_roles`
--
ALTER TABLE `sav_roles`
  MODIFY `rol_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT pour la table `sav_roles_contextuels_utilisateurs`
--
ALTER TABLE `sav_roles_contextuels_utilisateurs`
  MODIFY `rcu_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT pour la table `sav_roles_permissions`
--
ALTER TABLE `sav_roles_permissions`
  MODIFY `rpe_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1781;

--
-- AUTO_INCREMENT pour la table `sav_secteurs`
--
ALTER TABLE `sav_secteurs`
  MODIFY `sec_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_secteurs_services`
--
ALTER TABLE `sav_secteurs_services`
  MODIFY `ssv_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_services`
--
ALTER TABLE `sav_services`
  MODIFY `srv_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_services_equipes`
--
ALTER TABLE `sav_services_equipes`
  MODIFY `seq_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_sessions_utilisateurs`
--
ALTER TABLE `sav_sessions_utilisateurs`
  MODIFY `seu_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT pour la table `sav_societes`
--
ALTER TABLE `sav_societes`
  MODIFY `soc_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT pour la table `sav_societes_comptes_bancaires`
--
ALTER TABLE `sav_societes_comptes_bancaires`
  MODIFY `scb_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_societes_infos_complementaires`
--
ALTER TABLE `sav_societes_infos_complementaires`
  MODIFY `sic_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_societes_mandats_prelevement`
--
ALTER TABLE `sav_societes_mandats_prelevement`
  MODIFY `smp_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_societes_vehicules`
--
ALTER TABLE `sav_societes_vehicules`
  MODIFY `sve_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_standards`
--
ALTER TABLE `sav_standards`
  MODIFY `std_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_statistiques_tables`
--
ALTER TABLE `sav_statistiques_tables`
  MODIFY `stb_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT pour la table `sav_statuts`
--
ALTER TABLE `sav_statuts`
  MODIFY `sta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT pour la table `sav_taches`
--
ALTER TABLE `sav_taches`
  MODIFY `tac_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_taux_tva`
--
ALTER TABLE `sav_taux_tva`
  MODIFY `tva_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_tentatives_connexion`
--
ALTER TABLE `sav_tentatives_connexion`
  MODIFY `tcn_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT pour la table `sav_traitements_evenements`
--
ALTER TABLE `sav_traitements_evenements`
  MODIFY `tev_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_transitions_statuts`
--
ALTER TABLE `sav_transitions_statuts`
  MODIFY `tst_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_types_contacts`
--
ALTER TABLE `sav_types_contacts`
  MODIFY `tco_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_types_relations_societes`
--
ALTER TABLE `sav_types_relations_societes`
  MODIFY `tre_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `sav_types_societes`
--
ALTER TABLE `sav_types_societes`
  MODIFY `tso_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT pour la table `sav_utilisateurs`
--
ALTER TABLE `sav_utilisateurs`
  MODIFY `uti_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT pour la table `sav_utilisateurs_comptes_bancaires`
--
ALTER TABLE `sav_utilisateurs_comptes_bancaires`
  MODIFY `ucb_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_utilisateurs_departements`
--
ALTER TABLE `sav_utilisateurs_departements`
  MODIFY `udp_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_utilisateurs_equipes`
--
ALTER TABLE `sav_utilisateurs_equipes`
  MODIFY `ueq_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_utilisateurs_infos_complementaires`
--
ALTER TABLE `sav_utilisateurs_infos_complementaires`
  MODIFY `uic_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_utilisateurs_mandats_prelevement`
--
ALTER TABLE `sav_utilisateurs_mandats_prelevement`
  MODIFY `ump_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_utilisateurs_services`
--
ALTER TABLE `sav_utilisateurs_services`
  MODIFY `usv_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_utilisateurs_vehicules`
--
ALTER TABLE `sav_utilisateurs_vehicules`
  MODIFY `uve_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_verrous_entites`
--
ALTER TABLE `sav_verrous_entites`
  MODIFY `ven_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_verrous_maintenance`
--
ALTER TABLE `sav_verrous_maintenance`
  MODIFY `vma_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_versions_schema`
--
ALTER TABLE `sav_versions_schema`
  MODIFY `vsc_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `sav_versions_standards`
--
ALTER TABLE `sav_versions_standards`
  MODIFY `vst_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sav_webhooks`
--
ALTER TABLE `sav_webhooks`
  MODIFY `whk_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure de la vue `sav_vue_espaces_applicatifs_actifs`
--
DROP TABLE IF EXISTS `sav_vue_espaces_applicatifs_actifs`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u166513890_base`@`127.0.0.1` SQL SECURITY INVOKER VIEW `sav_vue_espaces_applicatifs_actifs`  AS SELECT `e`.`eap_id` AS `eap_id`, `s`.`soc_id` AS `soc_id`, `s`.`soc_code` AS `soc_code`, `s`.`soc_nom` AS `soc_nom`, `a`.`abo_id` AS `abo_id`, `fa`.`fab_code` AS `formule_code`, `fa`.`fab_nom` AS `formule_nom`, `st_e`.`sta_code` AS `statut_espace_code`, `st_a`.`sta_code` AS `statut_abonnement_code`, `e`.`eap_bloque_le` AS `eap_bloque_le`, `e`.`eap_motif_blocage` AS `eap_motif_blocage` FROM (((((`sav_espaces_applicatifs` `e` join `sav_societes` `s` on(`s`.`soc_id` = `e`.`eap_societe_id`)) left join `sav_abonnements_societes` `a` on(`a`.`abo_id` = `e`.`eap_abonnement_societe_id`)) left join `sav_formules_abonnement` `fa` on(`fa`.`fab_id` = `a`.`abo_formule_abonnement_id`)) left join `sav_statuts` `st_e` on(`st_e`.`sta_id` = `e`.`eap_statut_id`)) left join `sav_statuts` `st_a` on(`st_a`.`sta_id` = `a`.`abo_statut_abonnement_id`)) WHERE `e`.`eap_supprime_le` is null AND `s`.`soc_supprime_le` is null AND `s`.`soc_archive_le` is null ;

-- --------------------------------------------------------

--
-- Structure de la vue `sav_vue_etat_maintenance`
--
DROP TABLE IF EXISTS `sav_vue_etat_maintenance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u166513890_base`@`127.0.0.1` SQL SECURITY INVOKER VIEW `sav_vue_etat_maintenance`  AS SELECT `p`.`pmt_code` AS `pmt_code`, `p`.`pmt_nom` AS `pmt_nom`, `p`.`pmt_table_source` AS `pmt_table_source`, `p`.`pmt_frequence` AS `pmt_frequence`, `p`.`pmt_derniere_execution_le` AS `pmt_derniere_execution_le`, `p`.`pmt_prochaine_execution_le` AS `pmt_prochaine_execution_le`, `e`.`exm_statut` AS `dernier_statut`, `e`.`exm_lignes_archivees` AS `exm_lignes_archivees`, `e`.`exm_lignes_purgees` AS `exm_lignes_purgees`, `e`.`exm_debut_le` AS `derniere_execution_debut`, `e`.`exm_fin_le` AS `derniere_execution_fin`, `e`.`exm_message` AS `exm_message` FROM (`sav_politiques_maintenance` `p` left join `sav_executions_maintenance` `e` on(`e`.`exm_id` = (select `e2`.`exm_id` from `sav_executions_maintenance` `e2` where `e2`.`exm_politique_maintenance_id` = `p`.`pmt_id` order by `e2`.`exm_debut_le` desc,`e2`.`exm_id` desc limit 1))) WHERE `p`.`pmt_supprime_le` is null ;

-- --------------------------------------------------------

--
-- Structure de la vue `sav_vue_permissions_roles`
--
DROP TABLE IF EXISTS `sav_vue_permissions_roles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u166513890_base`@`127.0.0.1` SQL SECURITY INVOKER VIEW `sav_vue_permissions_roles`  AS SELECT `r`.`rol_id` AS `rol_id`, `r`.`rol_code` AS `rol_code`, `r`.`rol_nom` AS `rol_nom`, `p`.`per_id` AS `per_id`, `p`.`per_code` AS `per_code`, `rp`.`rpe_effet` AS `rpe_effet`, `m`.`mod_code` AS `mod_code`, `m`.`mod_nom` AS `mod_nom` FROM (((`sav_roles_permissions` `rp` join `sav_roles` `r` on(`r`.`rol_id` = `rp`.`rpe_role_id`)) join `sav_permissions` `p` on(`p`.`per_id` = `rp`.`rpe_permission_id`)) left join `sav_modules` `m` on(`m`.`mod_id` = `p`.`per_module_id`)) WHERE `rp`.`rpe_supprime_le` is null AND `r`.`rol_supprime_le` is null AND `r`.`rol_archive_le` is null AND `p`.`per_supprime_le` is null AND `p`.`per_archive_le` is null ;

-- --------------------------------------------------------

--
-- Structure de la vue `sav_vue_politiques_maintenance_actives`
--
DROP TABLE IF EXISTS `sav_vue_politiques_maintenance_actives`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u166513890_base`@`127.0.0.1` SQL SECURITY INVOKER VIEW `sav_vue_politiques_maintenance_actives`  AS SELECT `sav_politiques_maintenance`.`pmt_id` AS `pmt_id`, `sav_politiques_maintenance`.`pmt_code` AS `pmt_code`, `sav_politiques_maintenance`.`pmt_nom` AS `pmt_nom`, `sav_politiques_maintenance`.`pmt_type_action` AS `pmt_type_action`, `sav_politiques_maintenance`.`pmt_table_source` AS `pmt_table_source`, `sav_politiques_maintenance`.`pmt_table_archive` AS `pmt_table_archive`, `sav_politiques_maintenance`.`pmt_colonne_date` AS `pmt_colonne_date`, `sav_politiques_maintenance`.`pmt_duree_conservation_jours` AS `pmt_duree_conservation_jours`, `sav_politiques_maintenance`.`pmt_archivage_active` AS `pmt_archivage_active`, `sav_politiques_maintenance`.`pmt_purge_active` AS `pmt_purge_active`, `sav_politiques_maintenance`.`pmt_frequence` AS `pmt_frequence`, `sav_politiques_maintenance`.`pmt_derniere_execution_le` AS `pmt_derniere_execution_le`, `sav_politiques_maintenance`.`pmt_prochaine_execution_le` AS `pmt_prochaine_execution_le`, `sav_politiques_maintenance`.`pmt_mode_simulation` AS `pmt_mode_simulation` FROM `sav_politiques_maintenance` WHERE `sav_politiques_maintenance`.`pmt_est_active` = 1 AND `sav_politiques_maintenance`.`pmt_supprime_le` is null ;

-- --------------------------------------------------------

--
-- Structure de la vue `sav_vue_roles_utilisateurs_actifs`
--
DROP TABLE IF EXISTS `sav_vue_roles_utilisateurs_actifs`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u166513890_base`@`127.0.0.1` SQL SECURITY INVOKER VIEW `sav_vue_roles_utilisateurs_actifs`  AS SELECT `rcu`.`rcu_id` AS `rcu_id`, `rcu`.`rcu_utilisateur_id` AS `rcu_utilisateur_id`, `u`.`uti_email_normalise` AS `uti_email_normalise`, `rcu`.`rcu_societe_id` AS `rcu_societe_id`, `s`.`soc_code` AS `societe_code`, `r`.`rol_code` AS `rol_code`, `r`.`rol_nom` AS `rol_nom`, `m`.`mod_code` AS `mod_code`, `rcu`.`rcu_debute_le` AS `rcu_debute_le`, `rcu`.`rcu_termine_le` AS `rcu_termine_le`, `st`.`sta_code` AS `statut_code` FROM (((((`sav_roles_contextuels_utilisateurs` `rcu` join `sav_utilisateurs` `u` on(`u`.`uti_id` = `rcu`.`rcu_utilisateur_id`)) join `sav_roles` `r` on(`r`.`rol_id` = `rcu`.`rcu_role_id`)) left join `sav_societes` `s` on(`s`.`soc_id` = `rcu`.`rcu_societe_id`)) left join `sav_modules` `m` on(`m`.`mod_id` = `rcu`.`rcu_module_id`)) left join `sav_statuts` `st` on(`st`.`sta_id` = `rcu`.`rcu_statut_id`)) WHERE `rcu`.`rcu_supprime_le` is null AND `rcu`.`rcu_archive_le` is null AND (`rcu`.`rcu_termine_le` is null OR `rcu`.`rcu_termine_le` >= curdate()) ;

-- --------------------------------------------------------

--
-- Structure de la vue `sav_vue_sessions_actives`
--
DROP TABLE IF EXISTS `sav_vue_sessions_actives`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u166513890_base`@`127.0.0.1` SQL SECURITY INVOKER VIEW `sav_vue_sessions_actives`  AS SELECT `se`.`seu_id` AS `seu_id`, `se`.`seu_utilisateur_id` AS `seu_utilisateur_id`, `u`.`uti_email_normalise` AS `uti_email_normalise`, `se`.`seu_societe_active_id` AS `seu_societe_active_id`, `s`.`soc_code` AS `societe_active_code`, `se`.`seu_derniere_activite_le` AS `seu_derniere_activite_le`, `se`.`seu_expire_le` AS `seu_expire_le`, `se`.`seu_revoquee_le` AS `seu_revoquee_le`, `st`.`sta_code` AS `statut_code` FROM (((`sav_sessions_utilisateurs` `se` join `sav_utilisateurs` `u` on(`u`.`uti_id` = `se`.`seu_utilisateur_id`)) left join `sav_societes` `s` on(`s`.`soc_id` = `se`.`seu_societe_active_id`)) left join `sav_statuts` `st` on(`st`.`sta_id` = `se`.`seu_statut_id`)) WHERE `se`.`seu_supprime_le` is null AND `se`.`seu_revoquee_le` is null AND `se`.`seu_expire_le` > current_timestamp() ;

-- --------------------------------------------------------

--
-- Structure de la vue `sav_vue_societes_actives`
--
DROP TABLE IF EXISTS `sav_vue_societes_actives`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u166513890_base`@`127.0.0.1` SQL SECURITY INVOKER VIEW `sav_vue_societes_actives`  AS SELECT `s`.`soc_id` AS `soc_id`, `s`.`soc_uuid` AS `soc_uuid`, `s`.`soc_code` AS `soc_code`, `s`.`soc_nom` AS `soc_nom`, `s`.`soc_nom_legal` AS `soc_nom_legal`, `s`.`soc_email` AS `soc_email`, `s`.`soc_ville` AS `soc_ville`, `p`.`pay_code_iso2` AS `pay_code_iso2`, `p`.`pay_nom` AS `pays_nom`, `st`.`sta_code` AS `statut_code`, `st`.`sta_libelle` AS `statut_libelle`, `s`.`soc_est_holding` AS `soc_est_holding`, `s`.`soc_cree_le` AS `soc_cree_le`, `s`.`soc_modifie_le` AS `soc_modifie_le` FROM ((`sav_societes` `s` left join `sav_pays` `p` on(`p`.`pay_id` = `s`.`soc_pays_id`)) left join `sav_statuts` `st` on(`st`.`sta_id` = `s`.`soc_statut_id`)) WHERE `s`.`soc_supprime_le` is null AND `s`.`soc_archive_le` is null ;

-- --------------------------------------------------------

--
-- Structure de la vue `sav_vue_utilisateurs_actifs`
--
DROP TABLE IF EXISTS `sav_vue_utilisateurs_actifs`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u166513890_base`@`127.0.0.1` SQL SECURITY INVOKER VIEW `sav_vue_utilisateurs_actifs`  AS SELECT `u`.`uti_id` AS `uti_id`, `u`.`uti_uuid` AS `uti_uuid`, `u`.`uti_email_normalise` AS `uti_email_normalise`, `u`.`uti_identifiant` AS `uti_identifiant`, `pui`.`pui_nom` AS `pui_nom`, `pui`.`pui_prenom` AS `pui_prenom`, `st`.`sta_code` AS `statut_code`, `st`.`sta_libelle` AS `statut_libelle`, `u`.`uti_societe_active_id` AS `uti_societe_active_id`, `s`.`soc_code` AS `societe_active_code`, `s`.`soc_nom` AS `societe_active_nom`, `u`.`uti_email_verifie_le` AS `uti_email_verifie_le`, `u`.`uti_est_verrouille` AS `uti_est_verrouille`, `u`.`uti_derniere_connexion_le` AS `uti_derniere_connexion_le`, `u`.`uti_cree_le` AS `uti_cree_le` FROM (((`sav_utilisateurs` `u` left join `sav_profils_utilisateurs` `pui` on(`pui`.`pui_utilisateur_id` = `u`.`uti_id` and `pui`.`pui_supprime_le` is null)) left join `sav_statuts` `st` on(`st`.`sta_id` = `u`.`uti_statut_id`)) left join `sav_societes` `s` on(`s`.`soc_id` = `u`.`uti_societe_active_id`)) WHERE `u`.`uti_supprime_le` is null AND `u`.`uti_anonymise_le` is null ;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `sav_abonnements_evenements`
--
ALTER TABLE `sav_abonnements_evenements`
  ADD CONSTRAINT `fk_abe_cree_par` FOREIGN KEY (`abe_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_abe_modifie_par` FOREIGN KEY (`abe_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_abe_module` FOREIGN KEY (`abe_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_abe_statut` FOREIGN KEY (`abe_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_abe_supprime_par` FOREIGN KEY (`abe_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_abonnements_societes`
--
ALTER TABLE `sav_abonnements_societes`
  ADD CONSTRAINT `fk_abo_formule` FOREIGN KEY (`abo_formule_abonnement_id`) REFERENCES `sav_formules_abonnement` (`fab_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_abo_societe` FOREIGN KEY (`abo_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_abo_statut_abonnement` FOREIGN KEY (`abo_statut_abonnement_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_abo_statut_paiement` FOREIGN KEY (`abo_statut_paiement_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_acceptations_documents_juridiques_utilisateurs`
--
ALTER TABLE `sav_acceptations_documents_juridiques_utilisateurs`
  ADD CONSTRAINT `fk_adj_document` FOREIGN KEY (`adj_document_juridique_id`) REFERENCES `sav_documents_juridiques` (`dju_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adj_utilisateur` FOREIGN KEY (`adj_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_adhesions_utilisateurs_societes`
--
ALTER TABLE `sav_adhesions_utilisateurs_societes`
  ADD CONSTRAINT `fk_aus_cree_par` FOREIGN KEY (`aus_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aus_societe` FOREIGN KEY (`aus_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aus_statut` FOREIGN KEY (`aus_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aus_utilisateur` FOREIGN KEY (`aus_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_affectations_etiquettes`
--
ALTER TABLE `sav_affectations_etiquettes`
  ADD CONSTRAINT `fk_afe_cree_par` FOREIGN KEY (`afe_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_afe_etiquette` FOREIGN KEY (`afe_etiquette_id`) REFERENCES `sav_etiquettes` (`eti_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_afe_supprime_par` FOREIGN KEY (`afe_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_affectations_types_societes`
--
ALTER TABLE `sav_affectations_types_societes`
  ADD CONSTRAINT `fk_ats_societe` FOREIGN KEY (`ats_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ats_statut` FOREIGN KEY (`ats_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ats_type` FOREIGN KEY (`ats_type_societe_id`) REFERENCES `sav_types_societes` (`tso_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_blocages_securite`
--
ALTER TABLE `sav_blocages_securite`
  ADD CONSTRAINT `fk_bse_statut` FOREIGN KEY (`bse_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bse_utilisateur` FOREIGN KEY (`bse_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_canaux_notifications`
--
ALTER TABLE `sav_canaux_notifications`
  ADD CONSTRAINT `fk_cno_cree_par` FOREIGN KEY (`cno_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cno_modifie_par` FOREIGN KEY (`cno_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cno_statut` FOREIGN KEY (`cno_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cno_supprime_par` FOREIGN KEY (`cno_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_certifications`
--
ALTER TABLE `sav_certifications`
  ADD CONSTRAINT `fk_cer_societe` FOREIGN KEY (`cer_societe_proprietaire_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cer_statut` FOREIGN KEY (`cer_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_certifications_utilisateurs`
--
ALTER TABLE `sav_certifications_utilisateurs`
  ADD CONSTRAINT `fk_ceu_certification` FOREIGN KEY (`ceu_certification_id`) REFERENCES `sav_certifications` (`cer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ceu_preuve_fichier` FOREIGN KEY (`ceu_preuve_fichier_id`) REFERENCES `sav_fichiers` (`fic_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ceu_societe` FOREIGN KEY (`ceu_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ceu_statut` FOREIGN KEY (`ceu_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ceu_utilisateur` FOREIGN KEY (`ceu_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_cles_api`
--
ALTER TABLE `sav_cles_api`
  ADD CONSTRAINT `fk_cap_connecteur` FOREIGN KEY (`cap_connecteur_id`) REFERENCES `sav_connecteurs` (`con_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cap_cree_par` FOREIGN KEY (`cap_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cap_modifie_par` FOREIGN KEY (`cap_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cap_revoque_par` FOREIGN KEY (`cap_revoque_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cap_societe` FOREIGN KEY (`cap_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cap_statut` FOREIGN KEY (`cap_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cap_utilisateur` FOREIGN KEY (`cap_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_competences`
--
ALTER TABLE `sav_competences`
  ADD CONSTRAINT `fk_cmp_societe` FOREIGN KEY (`cmp_societe_proprietaire_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cmp_statut` FOREIGN KEY (`cmp_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_competences_utilisateurs`
--
ALTER TABLE `sav_competences_utilisateurs`
  ADD CONSTRAINT `fk_cut_competence` FOREIGN KEY (`cut_competence_id`) REFERENCES `sav_competences` (`cmp_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cut_niveau` FOREIGN KEY (`cut_niveau_competence_id`) REFERENCES `sav_niveaux_competences` (`nco_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cut_societe` FOREIGN KEY (`cut_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cut_statut` FOREIGN KEY (`cut_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cut_utilisateur` FOREIGN KEY (`cut_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_conditions_politiques_acces`
--
ALTER TABLE `sav_conditions_politiques_acces`
  ADD CONSTRAINT `fk_cpa_politique` FOREIGN KEY (`cpa_politique_acces_id`) REFERENCES `sav_politiques_acces` (`pac_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_connecteurs`
--
ALTER TABLE `sav_connecteurs`
  ADD CONSTRAINT `fk_con_cree_par` FOREIGN KEY (`con_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_con_modifie_par` FOREIGN KEY (`con_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_con_module` FOREIGN KEY (`con_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_con_societe` FOREIGN KEY (`con_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_con_statut` FOREIGN KEY (`con_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_con_supprime_par` FOREIGN KEY (`con_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_connecteurs_modules`
--
ALTER TABLE `sav_connecteurs_modules`
  ADD CONSTRAINT `fk_cmo_module_cible` FOREIGN KEY (`cmo_module_cible_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cmo_module_source` FOREIGN KEY (`cmo_module_source_id`) REFERENCES `sav_modules` (`mod_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cmo_statut` FOREIGN KEY (`cmo_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_consentements_rgpd`
--
ALTER TABLE `sav_consentements_rgpd`
  ADD CONSTRAINT `fk_crg_societe` FOREIGN KEY (`crg_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_crg_utilisateur` FOREIGN KEY (`crg_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_contacts_societes`
--
ALTER TABLE `sav_contacts_societes`
  ADD CONSTRAINT `fk_cts_etab` FOREIGN KEY (`cts_etablissement_id`) REFERENCES `sav_etablissements_societes` (`ets_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cts_societe` FOREIGN KEY (`cts_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cts_statut` FOREIGN KEY (`cts_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cts_type` FOREIGN KEY (`cts_type_contact_id`) REFERENCES `sav_types_contacts` (`tco_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cts_utilisateur` FOREIGN KEY (`cts_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_contenus_fichiers`
--
ALTER TABLE `sav_contenus_fichiers`
  ADD CONSTRAINT `fk_cfi_fichier` FOREIGN KEY (`cfi_fichier_id`) REFERENCES `sav_fichiers` (`fic_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_demandes_rgpd`
--
ALTER TABLE `sav_demandes_rgpd`
  ADD CONSTRAINT `fk_drg_societe` FOREIGN KEY (`drg_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_drg_statut` FOREIGN KEY (`drg_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_drg_traitee_par` FOREIGN KEY (`drg_traitee_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_drg_utilisateur` FOREIGN KEY (`drg_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_demandes_validation`
--
ALTER TABLE `sav_demandes_validation`
  ADD CONSTRAINT `fk_dva_demandeur` FOREIGN KEY (`dva_demandeur_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dva_societe` FOREIGN KEY (`dva_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dva_statut` FOREIGN KEY (`dva_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dva_validateur` FOREIGN KEY (`dva_validateur_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_departements`
--
ALTER TABLE `sav_departements`
  ADD CONSTRAINT `fk_dep_societe` FOREIGN KEY (`dep_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dep_statut` FOREIGN KEY (`dep_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_departements_secteurs`
--
ALTER TABLE `sav_departements_secteurs`
  ADD CONSTRAINT `fk_dse_child` FOREIGN KEY (`dse_secteur_id`) REFERENCES `sav_secteurs` (`sec_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dse_parent` FOREIGN KEY (`dse_departement_id`) REFERENCES `sav_departements` (`dep_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dse_societe` FOREIGN KEY (`dse_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dse_statut` FOREIGN KEY (`dse_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_departements_services`
--
ALTER TABLE `sav_departements_services`
  ADD CONSTRAINT `fk_dsv_child` FOREIGN KEY (`dsv_service_id`) REFERENCES `sav_services` (`srv_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dsv_parent` FOREIGN KEY (`dsv_departement_id`) REFERENCES `sav_departements` (`dep_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dsv_societe` FOREIGN KEY (`dsv_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dsv_statut` FOREIGN KEY (`dsv_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_destinataires_notifications`
--
ALTER TABLE `sav_destinataires_notifications`
  ADD CONSTRAINT `fk_dno_canal` FOREIGN KEY (`dno_canal_notification_id`) REFERENCES `sav_canaux_notifications` (`cno_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dno_notification` FOREIGN KEY (`dno_notification_id`) REFERENCES `sav_notifications` (`not_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dno_statut` FOREIGN KEY (`dno_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dno_utilisateur` FOREIGN KEY (`dno_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_dirigeants_societes`
--
ALTER TABLE `sav_dirigeants_societes`
  ADD CONSTRAINT `fk_dso_societe` FOREIGN KEY (`dso_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_documents_juridiques`
--
ALTER TABLE `sav_documents_juridiques`
  ADD CONSTRAINT `fk_dju_marque` FOREIGN KEY (`dju_marque_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dju_societe` FOREIGN KEY (`dju_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dju_statut` FOREIGN KEY (`dju_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_donnees_sensibles_utilisateurs`
--
ALTER TABLE `sav_donnees_sensibles_utilisateurs`
  ADD CONSTRAINT `fk_dsu_contrat_fichier` FOREIGN KEY (`dsu_contrat_fichier_id`) REFERENCES `sav_fichiers` (`fic_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dsu_cree_par` FOREIGN KEY (`dsu_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dsu_derniere_consultation_par` FOREIGN KEY (`dsu_derniere_consultation_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dsu_modifie_par` FOREIGN KEY (`dsu_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dsu_supprime_par` FOREIGN KEY (`dsu_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dsu_utilisateur` FOREIGN KEY (`dsu_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_elements_menus`
--
ALTER TABLE `sav_elements_menus`
  ADD CONSTRAINT `fk_eme_menu` FOREIGN KEY (`eme_menu_id`) REFERENCES `sav_menus` (`men_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eme_parent` FOREIGN KEY (`eme_parent_id`) REFERENCES `sav_elements_menus` (`eme_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eme_permission` FOREIGN KEY (`eme_permission_requise_id`) REFERENCES `sav_permissions` (`per_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eme_statut` FOREIGN KEY (`eme_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_equipes`
--
ALTER TABLE `sav_equipes`
  ADD CONSTRAINT `fk_equ_societe` FOREIGN KEY (`equ_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_equ_statut` FOREIGN KEY (`equ_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_equipes_services`
--
ALTER TABLE `sav_equipes_services`
  ADD CONSTRAINT `fk_eqs_child` FOREIGN KEY (`eqs_service_id`) REFERENCES `sav_services` (`srv_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eqs_parent` FOREIGN KEY (`eqs_equipe_id`) REFERENCES `sav_equipes` (`equ_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eqs_societe` FOREIGN KEY (`eqs_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eqs_statut` FOREIGN KEY (`eqs_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_espaces_applicatifs`
--
ALTER TABLE `sav_espaces_applicatifs`
  ADD CONSTRAINT `fk_eap_abonnement` FOREIGN KEY (`eap_abonnement_societe_id`) REFERENCES `sav_abonnements_societes` (`abo_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eap_societe` FOREIGN KEY (`eap_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eap_statut` FOREIGN KEY (`eap_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_etablissements_societes`
--
ALTER TABLE `sav_etablissements_societes`
  ADD CONSTRAINT `fk_ets_pays` FOREIGN KEY (`ets_pays_id`) REFERENCES `sav_pays` (`pay_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ets_societe` FOREIGN KEY (`ets_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_etiquettes`
--
ALTER TABLE `sav_etiquettes`
  ADD CONSTRAINT `fk_eti_archive_par` FOREIGN KEY (`eti_archive_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eti_cree_par` FOREIGN KEY (`eti_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eti_modifie_par` FOREIGN KEY (`eti_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eti_societe` FOREIGN KEY (`eti_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eti_statut` FOREIGN KEY (`eti_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eti_supprime_par` FOREIGN KEY (`eti_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_evenements_application`
--
ALTER TABLE `sav_evenements_application`
  ADD CONSTRAINT `fk_eva_emetteur` FOREIGN KEY (`eva_emetteur_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eva_module` FOREIGN KEY (`eva_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eva_societe` FOREIGN KEY (`eva_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eva_statut` FOREIGN KEY (`eva_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_exceptions_horaires_travail`
--
ALTER TABLE `sav_exceptions_horaires_travail`
  ADD CONSTRAINT `fk_eht_societe` FOREIGN KEY (`eht_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_executions_maintenance`
--
ALTER TABLE `sav_executions_maintenance`
  ADD CONSTRAINT `fk_exm_politique` FOREIGN KEY (`exm_politique_maintenance_id`) REFERENCES `sav_politiques_maintenance` (`pmt_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_exigences_versions_standards`
--
ALTER TABLE `sav_exigences_versions_standards`
  ADD CONSTRAINT `fk_evs_certification` FOREIGN KEY (`evs_certification_id`) REFERENCES `sav_certifications` (`cer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_evs_competence` FOREIGN KEY (`evs_competence_id`) REFERENCES `sav_competences` (`cmp_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_evs_niveau` FOREIGN KEY (`evs_niveau_competence_minimum_id`) REFERENCES `sav_niveaux_competences` (`nco_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_evs_version` FOREIGN KEY (`evs_version_standard_id`) REFERENCES `sav_versions_standards` (`vst_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_fichiers`
--
ALTER TABLE `sav_fichiers`
  ADD CONSTRAINT `fk_fic_cree_par` FOREIGN KEY (`fic_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fic_statut` FOREIGN KEY (`fic_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_file_evenements`
--
ALTER TABLE `sav_file_evenements`
  ADD CONSTRAINT `fk_fev_abonnement` FOREIGN KEY (`fev_abonnement_evenement_id`) REFERENCES `sav_abonnements_evenements` (`abe_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fev_evenement` FOREIGN KEY (`fev_evenement_id`) REFERENCES `sav_evenements_application` (`eva_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fev_statut` FOREIGN KEY (`fev_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_fonctions`
--
ALTER TABLE `sav_fonctions`
  ADD CONSTRAINT `fk_fon_societe_proprietaire` FOREIGN KEY (`fon_societe_proprietaire_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fon_statut` FOREIGN KEY (`fon_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fon_type_societe` FOREIGN KEY (`fon_type_societe_id`) REFERENCES `sav_types_societes` (`tso_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_fonctions_utilisateurs`
--
ALTER TABLE `sav_fonctions_utilisateurs`
  ADD CONSTRAINT `fk_fut_fonction` FOREIGN KEY (`fut_fonction_id`) REFERENCES `sav_fonctions` (`fon_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fut_societe` FOREIGN KEY (`fut_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fut_statut` FOREIGN KEY (`fut_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fut_utilisateur` FOREIGN KEY (`fut_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_formules_abonnement`
--
ALTER TABLE `sav_formules_abonnement`
  ADD CONSTRAINT `fk_fab_statut` FOREIGN KEY (`fab_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_hierarchie_utilisateurs`
--
ALTER TABLE `sav_hierarchie_utilisateurs`
  ADD CONSTRAINT `fk_hiu_societe` FOREIGN KEY (`hiu_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hiu_statut` FOREIGN KEY (`hiu_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hiu_superieur` FOREIGN KEY (`hiu_superieur_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hiu_utilisateur` FOREIGN KEY (`hiu_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_historique_contextes_utilisateurs`
--
ALTER TABLE `sav_historique_contextes_utilisateurs`
  ADD CONSTRAINT `fk_hcu_session` FOREIGN KEY (`hcu_session_utilisateur_id`) REFERENCES `sav_sessions_utilisateurs` (`seu_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hcu_utilisateur` FOREIGN KEY (`hcu_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_horaires_travail`
--
ALTER TABLE `sav_horaires_travail`
  ADD CONSTRAINT `fk_htr_societe` FOREIGN KEY (`htr_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_invitations_utilisateurs`
--
ALTER TABLE `sav_invitations_utilisateurs`
  ADD CONSTRAINT `fk_inv_cree_par` FOREIGN KEY (`inv_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_fonction_prevue` FOREIGN KEY (`inv_fonction_prevue_id`) REFERENCES `sav_fonctions` (`fon_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_role_prevu` FOREIGN KEY (`inv_role_prevu_id`) REFERENCES `sav_roles` (`rol_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_societe` FOREIGN KEY (`inv_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_statut` FOREIGN KEY (`inv_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_journaux_acces_donnees_sensibles`
--
ALTER TABLE `sav_journaux_acces_donnees_sensibles`
  ADD CONSTRAINT `fk_jad_cible` FOREIGN KEY (`jad_utilisateur_cible_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jad_donnee` FOREIGN KEY (`jad_donnee_sensible_utilisateur_id`) REFERENCES `sav_donnees_sensibles_utilisateurs` (`dsu_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jad_societe` FOREIGN KEY (`jad_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jad_source` FOREIGN KEY (`jad_utilisateur_source_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_journaux_emails`
--
ALTER TABLE `sav_journaux_emails`
  ADD CONSTRAINT `fk_jme_destinataire_societe` FOREIGN KEY (`jme_societe_destinataire_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jme_destinataire_utilisateur` FOREIGN KEY (`jme_utilisateur_destinataire_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jme_expediteur` FOREIGN KEY (`jme_societe_expediteur_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jme_modele` FOREIGN KEY (`jme_modele_email_id`) REFERENCES `sav_modeles_emails` (`mel_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jme_statut` FOREIGN KEY (`jme_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_journaux_maintenance`
--
ALTER TABLE `sav_journaux_maintenance`
  ADD CONSTRAINT `fk_jma_execution` FOREIGN KEY (`jma_execution_maintenance_id`) REFERENCES `sav_executions_maintenance` (`exm_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_journaux_webhooks`
--
ALTER TABLE `sav_journaux_webhooks`
  ADD CONSTRAINT `fk_jwh_evenement` FOREIGN KEY (`jwh_evenement_application_id`) REFERENCES `sav_evenements_application` (`eva_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jwh_webhook` FOREIGN KEY (`jwh_webhook_id`) REFERENCES `sav_webhooks` (`whk_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_liaisons_fichiers`
--
ALTER TABLE `sav_liaisons_fichiers`
  ADD CONSTRAINT `fk_lfi_cree_par` FOREIGN KEY (`lfi_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lfi_fichier` FOREIGN KEY (`lfi_fichier_id`) REFERENCES `sav_fichiers` (`fic_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lfi_modifie_par` FOREIGN KEY (`lfi_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lfi_statut` FOREIGN KEY (`lfi_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lfi_supprime_par` FOREIGN KEY (`lfi_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_liens_documents_juridiques_societes`
--
ALTER TABLE `sav_liens_documents_juridiques_societes`
  ADD CONSTRAINT `fk_ldj_document` FOREIGN KEY (`ldj_document_juridique_id`) REFERENCES `sav_documents_juridiques` (`dju_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ldj_societe` FOREIGN KEY (`ldj_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ldj_statut` FOREIGN KEY (`ldj_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_menus`
--
ALTER TABLE `sav_menus`
  ADD CONSTRAINT `fk_men_module` FOREIGN KEY (`men_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_men_statut` FOREIGN KEY (`men_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_modeles_emails`
--
ALTER TABLE `sav_modeles_emails`
  ADD CONSTRAINT `fk_mel_statut` FOREIGN KEY (`mel_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_modeles_notifications`
--
ALTER TABLE `sav_modeles_notifications`
  ADD CONSTRAINT `fk_mno_canal` FOREIGN KEY (`mno_canal_notification_id`) REFERENCES `sav_canaux_notifications` (`cno_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mno_cree_par` FOREIGN KEY (`mno_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mno_modifie_par` FOREIGN KEY (`mno_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mno_statut` FOREIGN KEY (`mno_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mno_supprime_par` FOREIGN KEY (`mno_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_modules`
--
ALTER TABLE `sav_modules`
  ADD CONSTRAINT `fk_mod_statut` FOREIGN KEY (`mod_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_modules_societes`
--
ALTER TABLE `sav_modules_societes`
  ADD CONSTRAINT `fk_mos_active_par` FOREIGN KEY (`mos_active_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mos_formule` FOREIGN KEY (`mos_formule_abonnement_id`) REFERENCES `sav_formules_abonnement` (`fab_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mos_module` FOREIGN KEY (`mos_module_id`) REFERENCES `sav_modules` (`mod_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mos_societe` FOREIGN KEY (`mos_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mos_statut` FOREIGN KEY (`mos_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_niveaux_competences`
--
ALTER TABLE `sav_niveaux_competences`
  ADD CONSTRAINT `fk_nco_statut` FOREIGN KEY (`nco_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_notes`
--
ALTER TABLE `sav_notes`
  ADD CONSTRAINT `fk_nte_cree_par` FOREIGN KEY (`nte_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nte_modifie_par` FOREIGN KEY (`nte_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nte_societe` FOREIGN KEY (`nte_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nte_statut` FOREIGN KEY (`nte_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nte_supprime_par` FOREIGN KEY (`nte_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_notifications`
--
ALTER TABLE `sav_notifications`
  ADD CONSTRAINT `fk_not_cree_par` FOREIGN KEY (`not_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_not_modele` FOREIGN KEY (`not_modele_notification_id`) REFERENCES `sav_modeles_notifications` (`mno_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_not_modifie_par` FOREIGN KEY (`not_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_not_module` FOREIGN KEY (`not_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_not_societe` FOREIGN KEY (`not_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_not_statut` FOREIGN KEY (`not_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_not_supprime_par` FOREIGN KEY (`not_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_parametres_application`
--
ALTER TABLE `sav_parametres_application`
  ADD CONSTRAINT `fk_pap_statut` FOREIGN KEY (`pap_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_parametres_securite_utilisateurs`
--
ALTER TABLE `sav_parametres_securite_utilisateurs`
  ADD CONSTRAINT `fk_psu_cree_par` FOREIGN KEY (`psu_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_psu_modifie_par` FOREIGN KEY (`psu_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_psu_supprime_par` FOREIGN KEY (`psu_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_psu_utilisateur` FOREIGN KEY (`psu_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_permissions`
--
ALTER TABLE `sav_permissions`
  ADD CONSTRAINT `fk_per_module` FOREIGN KEY (`per_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_per_statut` FOREIGN KEY (`per_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_politiques_acces`
--
ALTER TABLE `sav_politiques_acces`
  ADD CONSTRAINT `fk_pac_module` FOREIGN KEY (`pac_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pac_permission` FOREIGN KEY (`pac_permission_id`) REFERENCES `sav_permissions` (`per_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pac_statut` FOREIGN KEY (`pac_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_politiques_conservation_journaux`
--
ALTER TABLE `sav_politiques_conservation_journaux`
  ADD CONSTRAINT `fk_pcj_statut` FOREIGN KEY (`pcj_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_preferences_notifications`
--
ALTER TABLE `sav_preferences_notifications`
  ADD CONSTRAINT `fk_pno_canal` FOREIGN KEY (`pno_canal_notification_id`) REFERENCES `sav_canaux_notifications` (`cno_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pno_utilisateur` FOREIGN KEY (`pno_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_profils_utilisateurs`
--
ALTER TABLE `sav_profils_utilisateurs`
  ADD CONSTRAINT `fk_pui_cree_par` FOREIGN KEY (`pui_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pui_modifie_par` FOREIGN KEY (`pui_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pui_pays` FOREIGN KEY (`pui_pays_id`) REFERENCES `sav_pays` (`pay_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pui_photo_fichier` FOREIGN KEY (`pui_photo_fichier_id`) REFERENCES `sav_fichiers` (`fic_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pui_supprime_par` FOREIGN KEY (`pui_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pui_utilisateur` FOREIGN KEY (`pui_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_regles_validation`
--
ALTER TABLE `sav_regles_validation`
  ADD CONSTRAINT `fk_rva_cree_par` FOREIGN KEY (`rva_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rva_modifie_par` FOREIGN KEY (`rva_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rva_module` FOREIGN KEY (`rva_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rva_societe` FOREIGN KEY (`rva_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rva_statut` FOREIGN KEY (`rva_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rva_supprime_par` FOREIGN KEY (`rva_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_relations_societes`
--
ALTER TABLE `sav_relations_societes`
  ADD CONSTRAINT `fk_rso_cible` FOREIGN KEY (`rso_societe_cible_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rso_cree_par_societe` FOREIGN KEY (`rso_cree_par_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rso_source` FOREIGN KEY (`rso_societe_source_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rso_statut` FOREIGN KEY (`rso_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rso_type` FOREIGN KEY (`rso_type_relation_societe_id`) REFERENCES `sav_types_relations_societes` (`tre_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_representations_marques_societes`
--
ALTER TABLE `sav_representations_marques_societes`
  ADD CONSTRAINT `fk_rma_concession` FOREIGN KEY (`rma_concession_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rma_constructeur` FOREIGN KEY (`rma_constructeur_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rma_importateur` FOREIGN KEY (`rma_importateur_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rma_marque` FOREIGN KEY (`rma_marque_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rma_statut` FOREIGN KEY (`rma_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_resultats_controles_base`
--
ALTER TABLE `sav_resultats_controles_base`
  ADD CONSTRAINT `fk_rcb_controle` FOREIGN KEY (`rcb_controle_qualite_base_id`) REFERENCES `sav_controles_qualite_base` (`cqb_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_roles`
--
ALTER TABLE `sav_roles`
  ADD CONSTRAINT `fk_rol_module` FOREIGN KEY (`rol_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rol_societe_proprietaire` FOREIGN KEY (`rol_societe_proprietaire_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rol_statut` FOREIGN KEY (`rol_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rol_type_societe` FOREIGN KEY (`rol_type_societe_id`) REFERENCES `sav_types_societes` (`tso_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_roles_contextuels_utilisateurs`
--
ALTER TABLE `sav_roles_contextuels_utilisateurs`
  ADD CONSTRAINT `fk_rcu_concession` FOREIGN KEY (`rcu_concession_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rcu_marque` FOREIGN KEY (`rcu_marque_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rcu_module` FOREIGN KEY (`rcu_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rcu_role` FOREIGN KEY (`rcu_role_id`) REFERENCES `sav_roles` (`rol_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rcu_societe` FOREIGN KEY (`rcu_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rcu_statut` FOREIGN KEY (`rcu_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rcu_utilisateur` FOREIGN KEY (`rcu_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_roles_permissions`
--
ALTER TABLE `sav_roles_permissions`
  ADD CONSTRAINT `fk_rpe_cree_utilisateur` FOREIGN KEY (`rpe_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpe_mod_utilisateur` FOREIGN KEY (`rpe_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpe_permission` FOREIGN KEY (`rpe_permission_id`) REFERENCES `sav_permissions` (`per_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpe_role` FOREIGN KEY (`rpe_role_id`) REFERENCES `sav_roles` (`rol_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpe_sup_utilisateur` FOREIGN KEY (`rpe_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_secteurs`
--
ALTER TABLE `sav_secteurs`
  ADD CONSTRAINT `fk_sec_societe` FOREIGN KEY (`sec_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sec_statut` FOREIGN KEY (`sec_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_secteurs_services`
--
ALTER TABLE `sav_secteurs_services`
  ADD CONSTRAINT `fk_ssv_child` FOREIGN KEY (`ssv_service_id`) REFERENCES `sav_services` (`srv_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ssv_parent` FOREIGN KEY (`ssv_secteur_id`) REFERENCES `sav_secteurs` (`sec_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ssv_societe` FOREIGN KEY (`ssv_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ssv_statut` FOREIGN KEY (`ssv_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_services`
--
ALTER TABLE `sav_services`
  ADD CONSTRAINT `fk_srv_societe` FOREIGN KEY (`srv_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_statut` FOREIGN KEY (`srv_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_services_equipes`
--
ALTER TABLE `sav_services_equipes`
  ADD CONSTRAINT `fk_seq_child` FOREIGN KEY (`seq_equipe_id`) REFERENCES `sav_equipes` (`equ_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seq_parent` FOREIGN KEY (`seq_service_id`) REFERENCES `sav_services` (`srv_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seq_societe` FOREIGN KEY (`seq_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seq_statut` FOREIGN KEY (`seq_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_sessions_utilisateurs`
--
ALTER TABLE `sav_sessions_utilisateurs`
  ADD CONSTRAINT `fk_seu_concession_active` FOREIGN KEY (`seu_concession_active_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seu_equipe_active` FOREIGN KEY (`seu_equipe_active_id`) REFERENCES `sav_equipes` (`equ_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seu_marque_active` FOREIGN KEY (`seu_marque_active_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seu_service_actif` FOREIGN KEY (`seu_service_actif_id`) REFERENCES `sav_services` (`srv_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seu_societe_active` FOREIGN KEY (`seu_societe_active_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seu_statut` FOREIGN KEY (`seu_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seu_utilisateur` FOREIGN KEY (`seu_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_societes`
--
ALTER TABLE `sav_societes`
  ADD CONSTRAINT `fk_soc_archive_par` FOREIGN KEY (`soc_archive_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_soc_cree_par` FOREIGN KEY (`soc_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_soc_cree_par_societe` FOREIGN KEY (`soc_cree_par_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_soc_holding` FOREIGN KEY (`soc_holding_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_soc_logo_fichier` FOREIGN KEY (`soc_logo_fichier_id`) REFERENCES `sav_fichiers` (`fic_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_soc_modifie_par` FOREIGN KEY (`soc_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_soc_parent` FOREIGN KEY (`soc_societe_parente_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_soc_pays` FOREIGN KEY (`soc_pays_id`) REFERENCES `sav_pays` (`pay_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_soc_statut` FOREIGN KEY (`soc_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_soc_supprime_par` FOREIGN KEY (`soc_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_societes_comptes_bancaires`
--
ALTER TABLE `sav_societes_comptes_bancaires`
  ADD CONSTRAINT `fk_scb_cree_par` FOREIGN KEY (`scb_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_scb_modifie_par` FOREIGN KEY (`scb_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_scb_societe` FOREIGN KEY (`scb_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_scb_supprime_par` FOREIGN KEY (`scb_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_societes_infos_complementaires`
--
ALTER TABLE `sav_societes_infos_complementaires`
  ADD CONSTRAINT `fk_sic_code_remise` FOREIGN KEY (`sic_code_remise_id`) REFERENCES `sav_codes_remise` (`cre_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sic_cree_par` FOREIGN KEY (`sic_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sic_mode_reglement` FOREIGN KEY (`sic_mode_reglement_id`) REFERENCES `sav_modes_reglement` (`mrg_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sic_modifie_par` FOREIGN KEY (`sic_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sic_nature_client` FOREIGN KEY (`sic_nature_client_id`) REFERENCES `sav_natures_clients` (`nac_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sic_societe` FOREIGN KEY (`sic_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sic_supprime_par` FOREIGN KEY (`sic_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sic_tva_specifique` FOREIGN KEY (`sic_tva_specifique_id`) REFERENCES `sav_taux_tva` (`tva_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_societes_mandats_prelevement`
--
ALTER TABLE `sav_societes_mandats_prelevement`
  ADD CONSTRAINT `fk_smp_compte_bancaire` FOREIGN KEY (`smp_compte_bancaire_id`) REFERENCES `sav_societes_comptes_bancaires` (`scb_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_smp_cree_par` FOREIGN KEY (`smp_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_smp_modifie_par` FOREIGN KEY (`smp_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_smp_societe` FOREIGN KEY (`smp_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_smp_supprime_par` FOREIGN KEY (`smp_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_societes_vehicules`
--
ALTER TABLE `sav_societes_vehicules`
  ADD CONSTRAINT `fk_sve_cree_par` FOREIGN KEY (`sve_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sve_marque_societe` FOREIGN KEY (`sve_marque_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sve_modifie_par` FOREIGN KEY (`sve_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sve_societe` FOREIGN KEY (`sve_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sve_supprime_par` FOREIGN KEY (`sve_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_standards`
--
ALTER TABLE `sav_standards`
  ADD CONSTRAINT `fk_std_societe` FOREIGN KEY (`std_societe_proprietaire_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_std_statut` FOREIGN KEY (`std_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_taches`
--
ALTER TABLE `sav_taches`
  ADD CONSTRAINT `fk_tac_assignee` FOREIGN KEY (`tac_assignee_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tac_cree_par` FOREIGN KEY (`tac_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tac_demandeur` FOREIGN KEY (`tac_demandeur_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tac_modifie_par` FOREIGN KEY (`tac_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tac_module` FOREIGN KEY (`tac_module_id`) REFERENCES `sav_modules` (`mod_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tac_societe` FOREIGN KEY (`tac_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tac_statut` FOREIGN KEY (`tac_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tac_supprime_par` FOREIGN KEY (`tac_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_taux_tva`
--
ALTER TABLE `sav_taux_tva`
  ADD CONSTRAINT `fk_tva_pays` FOREIGN KEY (`tva_pays_id`) REFERENCES `sav_pays` (`pay_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tva_statut` FOREIGN KEY (`tva_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_tentatives_connexion`
--
ALTER TABLE `sav_tentatives_connexion`
  ADD CONSTRAINT `fk_tcn_utilisateur` FOREIGN KEY (`tcn_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_traitements_evenements`
--
ALTER TABLE `sav_traitements_evenements`
  ADD CONSTRAINT `fk_tev_file_evenement` FOREIGN KEY (`tev_file_evenement_id`) REFERENCES `sav_file_evenements` (`fev_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_transitions_statuts`
--
ALTER TABLE `sav_transitions_statuts`
  ADD CONSTRAINT `fk_tst_statut_cible` FOREIGN KEY (`tst_statut_cible_id`) REFERENCES `sav_statuts` (`sta_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tst_statut_source` FOREIGN KEY (`tst_statut_source_id`) REFERENCES `sav_statuts` (`sta_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_types_contacts`
--
ALTER TABLE `sav_types_contacts`
  ADD CONSTRAINT `fk_tco_statut` FOREIGN KEY (`tco_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_types_relations_societes`
--
ALTER TABLE `sav_types_relations_societes`
  ADD CONSTRAINT `fk_tre_statut` FOREIGN KEY (`tre_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_types_societes`
--
ALTER TABLE `sav_types_societes`
  ADD CONSTRAINT `fk_tso_logo_fichier` FOREIGN KEY (`tso_logo_fichier_id`) REFERENCES `sav_fichiers` (`fic_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tso_statut` FOREIGN KEY (`tso_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_utilisateurs`
--
ALTER TABLE `sav_utilisateurs`
  ADD CONSTRAINT `fk_uti_anonymise_par` FOREIGN KEY (`uti_anonymise_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uti_cree_par` FOREIGN KEY (`uti_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uti_fuseau` FOREIGN KEY (`uti_fuseau_horaire_id`) REFERENCES `sav_fuseaux_horaires` (`fuh_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uti_marque_active` FOREIGN KEY (`uti_marque_active_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uti_modifie_par` FOREIGN KEY (`uti_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uti_societe_active` FOREIGN KEY (`uti_societe_active_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uti_statut` FOREIGN KEY (`uti_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uti_supprime_par` FOREIGN KEY (`uti_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_utilisateurs_comptes_bancaires`
--
ALTER TABLE `sav_utilisateurs_comptes_bancaires`
  ADD CONSTRAINT `fk_ucb_cree_par` FOREIGN KEY (`ucb_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ucb_modifie_par` FOREIGN KEY (`ucb_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ucb_supprime_par` FOREIGN KEY (`ucb_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ucb_utilisateur` FOREIGN KEY (`ucb_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_utilisateurs_departements`
--
ALTER TABLE `sav_utilisateurs_departements`
  ADD CONSTRAINT `fk_udp_cible` FOREIGN KEY (`udp_departement_id`) REFERENCES `sav_departements` (`dep_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_udp_societe` FOREIGN KEY (`udp_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_udp_statut` FOREIGN KEY (`udp_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_udp_utilisateur` FOREIGN KEY (`udp_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_utilisateurs_equipes`
--
ALTER TABLE `sav_utilisateurs_equipes`
  ADD CONSTRAINT `fk_ueq_cible` FOREIGN KEY (`ueq_equipe_id`) REFERENCES `sav_equipes` (`equ_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ueq_societe` FOREIGN KEY (`ueq_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ueq_statut` FOREIGN KEY (`ueq_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ueq_utilisateur` FOREIGN KEY (`ueq_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_utilisateurs_infos_complementaires`
--
ALTER TABLE `sav_utilisateurs_infos_complementaires`
  ADD CONSTRAINT `fk_uic_code_remise` FOREIGN KEY (`uic_code_remise_id`) REFERENCES `sav_codes_remise` (`cre_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uic_cree_par` FOREIGN KEY (`uic_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uic_mode_reglement` FOREIGN KEY (`uic_mode_reglement_id`) REFERENCES `sav_modes_reglement` (`mrg_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uic_modifie_par` FOREIGN KEY (`uic_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uic_nature_client` FOREIGN KEY (`uic_nature_client_id`) REFERENCES `sav_natures_clients` (`nac_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uic_supprime_par` FOREIGN KEY (`uic_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uic_utilisateur` FOREIGN KEY (`uic_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_utilisateurs_mandats_prelevement`
--
ALTER TABLE `sav_utilisateurs_mandats_prelevement`
  ADD CONSTRAINT `fk_ump_compte_bancaire` FOREIGN KEY (`ump_compte_bancaire_id`) REFERENCES `sav_utilisateurs_comptes_bancaires` (`ucb_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ump_cree_par` FOREIGN KEY (`ump_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ump_modifie_par` FOREIGN KEY (`ump_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ump_supprime_par` FOREIGN KEY (`ump_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ump_utilisateur` FOREIGN KEY (`ump_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_utilisateurs_services`
--
ALTER TABLE `sav_utilisateurs_services`
  ADD CONSTRAINT `fk_usv_cible` FOREIGN KEY (`usv_service_id`) REFERENCES `sav_services` (`srv_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usv_societe` FOREIGN KEY (`usv_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usv_statut` FOREIGN KEY (`usv_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usv_utilisateur` FOREIGN KEY (`usv_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_utilisateurs_vehicules`
--
ALTER TABLE `sav_utilisateurs_vehicules`
  ADD CONSTRAINT `fk_uve_cree_par` FOREIGN KEY (`uve_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uve_marque_societe` FOREIGN KEY (`uve_marque_societe_id`) REFERENCES `sav_societes` (`soc_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uve_modifie_par` FOREIGN KEY (`uve_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uve_supprime_par` FOREIGN KEY (`uve_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uve_utilisateur` FOREIGN KEY (`uve_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_verrous_entites`
--
ALTER TABLE `sav_verrous_entites`
  ADD CONSTRAINT `fk_ven_session` FOREIGN KEY (`ven_session_utilisateur_id`) REFERENCES `sav_sessions_utilisateurs` (`seu_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ven_statut` FOREIGN KEY (`ven_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ven_utilisateur` FOREIGN KEY (`ven_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_verrous_maintenance`
--
ALTER TABLE `sav_verrous_maintenance`
  ADD CONSTRAINT `fk_vma_execution` FOREIGN KEY (`vma_execution_maintenance_id`) REFERENCES `sav_executions_maintenance` (`exm_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_versions_standards`
--
ALTER TABLE `sav_versions_standards`
  ADD CONSTRAINT `fk_vst_standard` FOREIGN KEY (`vst_standard_id`) REFERENCES `sav_standards` (`std_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vst_statut` FOREIGN KEY (`vst_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `sav_webhooks`
--
ALTER TABLE `sav_webhooks`
  ADD CONSTRAINT `fk_whk_connecteur` FOREIGN KEY (`whk_connecteur_id`) REFERENCES `sav_connecteurs` (`con_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_whk_cree_par` FOREIGN KEY (`whk_cree_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_whk_modifie_par` FOREIGN KEY (`whk_modifie_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_whk_statut` FOREIGN KEY (`whk_statut_id`) REFERENCES `sav_statuts` (`sta_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_whk_supprime_par` FOREIGN KEY (`whk_supprime_par_utilisateur_id`) REFERENCES `sav_utilisateurs` (`uti_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
