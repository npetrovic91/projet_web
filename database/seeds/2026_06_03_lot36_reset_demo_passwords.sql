-- ============================================================
-- AUTOSAV — Migration 2026_06_03_lot36_reset_demo_passwords
-- ------------------------------------------------------------
-- RECONSTITUTION : fichier référencé par
-- tests/Unit/DemoPasswordResetHotfixTest.php mais absent de
-- l'archive public_html (6).zip (cf. ROADMAP_PRODUCTION.md §1.1).
--
-- Correctif appliqué après lot35 : sur certains imports (notamment
-- Hostinger, cf. README_IMPORT_LOT36_RESET_DEMO_PASSWORDS_HOSTINGER.txt),
-- les comptes démo du réseau Auto Avenue remontaient
-- « Identifiants invalides » malgré un hash correct — généralement
-- dû à un compte resté verrouillé (uti_est_verrouille) ou à un
-- compteur d'échecs de connexion non réinitialisé après import.
-- Ce correctif force le mot de passe démo, déverrouille les
-- comptes et réinitialise les compteurs d'échec.
--
-- Mot de passe démo commun : DemoAutosav!2026 (cf. docs/COMPTES_DEMO_LOT35.md)
-- ============================================================

UPDATE sav_utilisateurs
SET
    uti_mot_de_passe_hash = '$argon2id$v=19$m=65536,t=4,p=1$eG91d1ZxUGZ1LjV1MDFPVw$NfTUFJpcpH6gZegE8mJZ0BNRgK2O+KZUXTuJ/pI1784',
    uti_doit_changer_mot_de_passe = 1,
    uti_est_verrouille = 0,
    uti_verrouille_jusqua = NULL,
    uti_motif_verrouillage = NULL,
    uti_echecs_connexion = 0,
    uti_modifie_le = NOW()
WHERE uti_identifiant IN (
    'admin.general',
    'responsable.groupe',
    'directeur.paris',
    'responsable.sav',
    'conseiller.service',
    'technicien.diagnostic',
    'gestionnaire.pieces',
    'responsable.garantie',
    'magasinier.lille',
    'conseiller.toulouse'
)
OR uti_email_normalise IN (
    'admin.general@autosav.demo',
    'responsable.groupe@autosav.demo',
    'directeur.paris@autosav.demo',
    'responsable.sav@autosav.demo',
    'conseiller.service@autosav.demo',
    'technicien.diagnostic@autosav.demo',
    'gestionnaire.pieces@autosav.demo',
    'responsable.garantie@autosav.demo',
    'magasinier.lille@autosav.demo',
    'conseiller.toulouse@autosav.demo'
);

-- Référence croisée pour le suivi des migrations appliquées :
-- 2026_06_03_lot36_reset_demo_passwords
