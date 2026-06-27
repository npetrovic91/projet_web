-- ============================================================
-- AUTOSAV — Unification du mot de passe de TOUS les comptes démo
-- ------------------------------------------------------------
-- Constat (export u166513890_base (5).sql, 2026-06-27) : 67 comptes
-- utilisateurs au total, dont 64 comptes démo (@autosav.demo) répartis
-- sur deux mots de passe différents selon le lot d'origine :
--   - 54 comptes (MotorGroup/ImportAuto/NeoVolt + Groupe/Concession)
--     -> Demo2026!
--   - 10 comptes (réseau automobile, lot35/36)
--     -> DemoAutosav!2026
-- ANOMALIE : pdg.neovolt@autosav.demo avait un troisième hash, distinct
-- des 10 autres comptes NeoVolt (probablement un compte créé avant la
-- standardisation des mots de passe démo, jamais couvert par
-- 2026_06_03_lot36_reset_demo_passwords.sql qui ne ciblait que les
-- comptes du réseau automobile).
--
-- Ce script unifie TOUS les comptes démo (@autosav.demo, quel que soit
-- le lot) sur un seul mot de passe commun : Demo2026!
-- Les comptes réels (@autosav.local : nenad.petrovic, super.admin,
-- admin, contact) ne sont PAS concernés par ce filtre — leur mot de
-- passe n'est jamais touché par ce script.
--
-- À exécuter manuellement (phpMyAdmin ou client SQL) : ce fichier vit
-- dans database/seeds/, jamais appliqué automatiquement par
-- bin/migrate.php (qui ne scanne que database/migrations/), pour éviter
-- tout risque qu'un déploiement réel écrase des mots de passe réels.
-- ============================================================

UPDATE sav_utilisateurs
SET
    uti_mot_de_passe_hash = '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c',
    uti_doit_changer_mot_de_passe = 1,
    uti_est_verrouille = 0,
    uti_verrouille_jusqua = NULL,
    uti_motif_verrouillage = NULL,
    uti_echecs_connexion = 0,
    uti_modifie_le = NOW()
WHERE uti_email_normalise LIKE '%@autosav.demo';
