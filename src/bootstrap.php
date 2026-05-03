<?php
declare(strict_types=1);
use Nenad\Autosav\Core\Security\Class\SessionHandler as AppSession;
use Nenad\Autosav\Core\Security\Class\CsrfProtection;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Core\Database\Database;
/**
 * AUTOSAV — Bootstrap applicatif
 * Fichier : src/bootstrap.php
 *
 * CORRECTION CRITIQUE :
 *   Les instructions "use" DOIVENT être en tête de fichier PHP,
 *   avant tout code exécutable. Les placer après foreach/header()
 *   provoque une Parse Error → HTTP 500.
 *   Toutes les déclarations "use" sont maintenant regroupées ici.
 */

// ============================================================
// DECLARATIONS "use" — OBLIGATOIREMENT EN TÊTE DE FICHIER
// ============================================================


// ============================================================
// 0. GARDE
// ============================================================
defined('AUTOSAV_ROOT') or die('Bootstrap appelé sans AUTOSAV_ROOT défini.');

// ============================================================
// 1. HTTPS forcé — AVANT tout header et session_start()
//    Détection proxy Hostinger via HTTP_X_FORWARDED_PROTO.
// ============================================================
$_bEnv = getenv('APP_ENV') ?: 'production';
if ($_bEnv === 'production') {
    $_https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || ($_SERVER['SERVER_PORT'] ?? 80) == 443
    );
    if (!$_https) {
        $h = filter_var($_SERVER['HTTP_HOST']   ?? 'localhost', FILTER_SANITIZE_URL);
        $u = filter_var($_SERVER['REQUEST_URI'] ?? '/',        FILTER_SANITIZE_URL);
        header('Location: https://' . $h . $u, true, 301);
        exit;
    }
    unset($_https, $h, $u);
}
unset($_bEnv);

// ============================================================
// 2. CONFIGURATION (ordre impératif — dépendances en cascade)
// ============================================================
require_once CONFIG_PATH . '/environment.php'; // ROOT_PATH, chemins, .env
require_once CONFIG_PATH . '/app.php';         // APP_NAME, APP_URL, APP_FORCE_HTTPS
require_once CONFIG_PATH . '/database.php';    // DB_HOST, DB_NAME, DB_PDO_OPTIONS
require_once CONFIG_PATH . '/security.php';    // SECURITY_HEADERS, CSP_POLICY, CSRF, HASH_ALGO
require_once CONFIG_PATH . '/sessions.php';    // SESSION_NAME, SESSION_SAVE_PATH
require_once CONFIG_PATH . '/constants.php';
require_once CONFIG_PATH . '/mail.php';
require_once CONFIG_PATH . '/terms.php';
require_once CONFIG_PATH . '/maintenance.php';
require_once CONFIG_PATH . '/gdpr.php';
require_once CONFIG_PATH . '/ajax.php';
require_once CONFIG_PATH . '/dashboard.php';
require_once CONFIG_PATH . '/pagination.php';
require_once CONFIG_PATH . '/modules.php';

// ============================================================
// 3. CRÉATION AUTOMATIQUE DES DOSSIERS storage/
//    Sur Hostinger, ces dossiers ne sont pas versionnés.
//    Créés automatiquement au premier démarrage.
//    SESSIONS_PATH : 0700 (données sensibles, lecture propriétaire seul)
//    Autres        : 0750 (lecture groupe pour logs, cache, uploads)
// ============================================================
foreach ([LOGS_PATH, SESSIONS_PATH, CACHE_PATH, UPLOADS_PATH, EXPORTS_PATH] as $_dir) {
    if (!is_dir($_dir)) {
        $mode = ($_dir === SESSIONS_PATH) ? 0700 : 0750;
        @mkdir($_dir, $mode, true);
    }
}
unset($_dir, $mode);

// ============================================================
// 4. AUTOLOADER PSR-4 MANUEL
//    Doit être chargé AVANT tout appel à une classe du projet
//    (AppSession, CsrfProtection, LogManager ci-dessous).
// ============================================================
require_once SRC_PATH . '/autoload.php';

// ============================================================
// 5. EN-TÊTES HTTP DE SÉCURITÉ
//    Envoyés AVANT session_start() pour cohérence.
//    SECURITY_HEADERS et CSP_POLICY définis dans security.php.
// ============================================================
foreach (SECURITY_HEADERS as $header => $value) {
    if ($value === '') {
        header_remove($header);
    } else {
        header("{$header}: {$value}");
    }
}
header('Content-Security-Policy: ' . CSP_POLICY);
header_remove('X-Powered-By');

// ============================================================
// 6. SESSION — Initialisation sécurisée
//    AppSession::init() appelle session_start() avec les
//    paramètres de config/sessions.php (SameSite, HttpOnly,
//    save_path, gc_maxlifetime...).
// ============================================================
AppSession::init();

// ============================================================
// 7. CSRF — Génération / vérification du token
//    Doit être APRÈS session_start() (token stocké en session).
// ============================================================
CsrfProtection::ensureToken();

// ============================================================
// 8. LOGGER — Singleton (écriture fichiers, pas de DB)
// ============================================================
LogManager::getInstance();

// ============================================================
// 9. HELPERS GLOBAUX
//    Fonctions disponibles dans toute l'application.
//    Protégées par function_exists() pour éviter redéclaration
//    si helpers.php est aussi chargé.
// ============================================================

if (!function_exists('db')) {
    function db(): Database {
        return Database::getInstance();
    }
}

if (!function_exists('database')) {
    function database(): Database {
        return Database::getInstance();
    }
}

if (!function_exists('logger')) {
    function logger(string $channel = 'application'): \Nenad\Autosav\Core\Logger\Class\Logger {
        return LogManager::getInstance()->channel($channel);
    }
}

if (!function_exists('client_ip')) {
    /**
     * Retourne l'IP réelle du client.
     * Gère le proxy Hostinger (HTTP_X_FORWARDED_FOR).
     * Valide l'IP pour éviter les injections d'en-têtes.
     */
    function client_ip(): string {
        foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = trim(explode(',', $_SERVER[$k])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('session')) {
    function session(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('is_authenticated')) {
    function is_authenticated(): bool {
        return !empty($_SESSION['authenticated']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('has_role')) {
    function has_role(string $role): bool {
        return in_array($role, (array)($_SESSION['user_roles'] ?? []), true);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $code = 302): never {
        $location = str_starts_with($path, 'http') ? $path : url($path);
        header('Location: ' . $location, true, $code);
        exit;
    }
}

if (!function_exists('generate_uuid')) {
    function generate_uuid(): string {
        $d = random_bytes(16);
        $d[6] = chr(ord($d[6]) & 0x0f | 0x40);
        $d[8] = chr(ord($d[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}

if (!function_exists('e')) {
    function e(mixed $v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(CsrfProtection::getToken()) . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return CsrfProtection::getToken();
    }
}