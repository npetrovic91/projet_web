<?php
declare(strict_types=1);

/**
 * AUTOSAV — Environnement & chemins
 * Fichier : config/environment.php
 *
 * ORDRE IMPÉRATIF :
 *   1. Lecture .env  → putenv() / $_ENV
 *   2. Constantes    → define() utilise getenv() pour lire le .env
 *
 *   Si l'ordre est inversé, APP_DEBUG est défini à false (APP_ENV=production)
 *   avant que le .env ne soit lu, et le define() immuable ignore la valeur .env.
 */

defined('AUTOSAV_ROOT') or die('Bootstrap appelé sans AUTOSAV_ROOT défini.');

// ============================================================
// CHEMINS SYSTÈME — définis en premier (pas de dépendance .env)
// ============================================================
defined('ROOT_PATH')    || define('ROOT_PATH',    AUTOSAV_ROOT);
defined('CONFIG_PATH')  || define('CONFIG_PATH',  ROOT_PATH . '/config');
defined('SRC_PATH')     || define('SRC_PATH',     ROOT_PATH . '/src');

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
define('VENDOR_PATH',   ROOT_PATH . '/vendor');

// ============================================================
// 1. LECTURE DU FICHIER .env  ← EN PREMIER (avant tout define)
// ============================================================
$_envFile = ROOT_PATH . '/.env';
if (is_file($_envFile) && is_readable($_envFile)) {
    $lines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $_line) {
            $_line = trim($_line);
            if ($_line === '' || $_line[0] === '#') {
                continue;
            }
            if (str_contains($_line, '=')) {
                [$_key, $_val] = explode('=', $_line, 2);
                $_key = trim($_key);
                $_val = trim($_val, " \t\n\r\0\x0B\"'");
                if ($_key !== '' && preg_match('/^[A-Z][A-Z0-9_]*$/', $_key)) {
                    if (getenv($_key) === false) {
                        putenv("{$_key}={$_val}");
                        $_ENV[$_key]    = $_val;
                        $_SERVER[$_key] = $_val;
                    }
                }
            }
        }
    }
}
unset($_envFile, $lines, $_line, $_key, $_val);

// ============================================================
// 2. CONSTANTES APPLICATIVES ← APRÈS lecture .env
//    getenv() lit les valeurs mises par putenv() ci-dessus.
// ============================================================
defined('APP_ENV')   || define('APP_ENV',   getenv('APP_ENV') ?: 'production');

// APP_DEBUG : lire depuis .env en priorité, fallback sur APP_ENV
$_debugEnv = getenv('APP_DEBUG');
$_debugVal = match(true) {
    $_debugEnv === 'true'  => true,
    $_debugEnv === 'false' => false,
    $_debugEnv === '1'     => true,
    $_debugEnv === '0'     => false,
    default                => (APP_ENV === 'development'),
};
defined('APP_DEBUG') || define('APP_DEBUG', $_debugVal);
unset($_debugEnv, $_debugVal);

defined('APP_FORCE_HTTPS') || define('APP_FORCE_HTTPS', APP_ENV === 'production');

// ============================================================
// 3. HTTPS FORCÉ (production uniquement)
// ============================================================
if (APP_FORCE_HTTPS) {
    $_https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || ($_SERVER['SERVER_PORT'] ?? 80) == 443
    );
    if (!$_https) {
        $h = filter_var($_SERVER['HTTP_HOST']   ?? 'localhost', FILTER_SANITIZE_URL);
        $u = filter_var($_SERVER['REQUEST_URI'] ?? '/',         FILTER_SANITIZE_URL);
        header('Location: https://' . $h . $u, true, 301);
        exit;
    }
    unset($_https, $h, $u);
}

// ============================================================
// 4. GESTION DES ERREURS (utilise APP_DEBUG maintenant correct)
// ============================================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors',         '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors',         '0');
    ini_set('display_startup_errors', '0');
}
ini_set('log_errors', '1');
ini_set('error_log',  LOGS_PATH . '/error.log');

// ============================================================
// 5. TIMEZONE
// ============================================================
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/Paris');
