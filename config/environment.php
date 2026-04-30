<?php
declare(strict_types=1);

/**
 * AUTOSAV — Environnement & chemins
 * Fichier : config/environment.php
 *
 * IMPORTANT : Ce fichier est requis par bootstrap.php APRÈS que
 * index.php ait déjà défini AUTOSAV_ROOT, CONFIG_PATH et SRC_PATH.
 * Toutes les constantes potentiellement dupliquées sont protégées
 * par des gardes defined() pour éviter les E_WARNING/E_ERROR PHP 8.
 */

defined('AUTOSAV_ROOT') or die('Accès direct interdit.');

// ============================================================
// ENVIRONNEMENT
// ============================================================
defined('APP_ENV')         || define('APP_ENV',         getenv('APP_ENV') ?: 'production');
defined('APP_DEBUG')       || define('APP_DEBUG',       APP_ENV === 'development');
defined('APP_FORCE_HTTPS') || define('APP_FORCE_HTTPS', APP_ENV === 'production');

// ============================================================
// CHEMINS SYSTÈME (POSIX / FHS)
// ROOT_PATH = répertoire racine du projet (≡ AUTOSAV_ROOT)
// Sur Hostinger : /home/u166513890/domains/devnenad.fr/public_html
// ============================================================
defined('ROOT_PATH')    || define('ROOT_PATH',    AUTOSAV_ROOT);

// CONFIG_PATH et SRC_PATH peuvent déjà être définis par index.php
// avant que bootstrap.php ne charge ce fichier — on protège.
defined('CONFIG_PATH')  || define('CONFIG_PATH',  ROOT_PATH . '/config');
defined('SRC_PATH')     || define('SRC_PATH',     ROOT_PATH . '/src');

// Ces constantes ne sont JAMAIS définies avant, pas de risque.
define('MODULES_PATH',  SRC_PATH  . '/Modules');
define('PUBLIC_PATH',   ROOT_PATH . '/public');
define('STORAGE_PATH',  ROOT_PATH . '/storage');
define('LOGS_PATH',     STORAGE_PATH . '/logs');
define('SESSIONS_PATH', STORAGE_PATH . '/sessions');
define('CACHE_PATH',    STORAGE_PATH . '/cache');
define('UPLOADS_PATH',  STORAGE_PATH . '/uploads');
define('EXPORTS_PATH',  STORAGE_PATH . '/exports');
define('DATABASE_PATH', ROOT_PATH . '/database');
define('TESTS_PATH',    ROOT_PATH . '/tests');

// CORRIGÉ : VENDOR_PATH pointe vers ROOT_PATH/vendor (Composer)
// et non vers SRC_PATH/Vendor (chemin inexistant / incorrect).
define('VENDOR_PATH',   ROOT_PATH . '/vendor');

// ============================================================
// CHARGEMENT DU FICHIER .env
// ============================================================
$_envFile = ROOT_PATH . '/.env';
if (is_file($_envFile) && is_readable($_envFile)) {
    $lines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            // Ignorer les lignes vides et les commentaires
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                // Supprimer guillemets simples/doubles autour de la valeur
                $val = trim($val, " \t\n\r\0\x0B\"'");
                if ($key !== '' && preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                    // N'écraser que si pas encore défini (évite injection via $_ENV)
                    if (getenv($key) === false) {
                        putenv("{$key}={$val}");
                        $_ENV[$key]    = $val;
                        $_SERVER[$key] = $val;
                    }
                }
            }
        }
    }
}
unset($_envFile, $lines, $line, $key, $val);

// ============================================================
// GESTION DES ERREURS
// ============================================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors',         '1');
    ini_set('display_startup_errors', '1');
} else {
    // Production : masquer les erreurs à l'utilisateur,
    // mais continuer à les logger (obligation RGPD / traçabilité).
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors',         '0');
    ini_set('display_startup_errors', '0');
}
ini_set('log_errors',  '1');
ini_set('error_log',   LOGS_PATH . '/error.log');

// ============================================================
// TIMEZONE
// ============================================================
date_default_timezone_set('Europe/Paris');
