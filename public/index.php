<?php
declare(strict_types=1);

/**
 * AUTOSAV — Front Controller
 * Fichier : public_html/public/index.php
 *
 * RÈGLE PHP : Les déclarations "use" doivent précéder TOUT code
 * exécutable (y compris if, define, require). Sinon : Parse Error 500.
 */

// ============================================================
// DÉCLARATIONS "use" — EN TÊTE ABSOLUE (avant tout code)
// ============================================================
use Nenad\Autosav\Core\Router\Router;

// ============================================================
// CONSTANTES RACINE
// Protégées par defined() : ce fichier peut être appelé par
// root/index.php (qui les définit déjà) ou directement par Apache.
// ============================================================
if (!defined('AUTOSAV_ROOT')) {
    define('AUTOSAV_ROOT', dirname(__DIR__));
    define('CONFIG_PATH',  AUTOSAV_ROOT . '/config');
    define('SRC_PATH',     AUTOSAV_ROOT . '/src');
}

require_once SRC_PATH . '/bootstrap.php';

// ============================================================
// ROUTEUR — DISPATCH
// ============================================================
$router = new Router();
$routes = require CONFIG_PATH . '/urls.php';

foreach ($routes as $definition => $handler) {
    [$method, $path] = explode(' ', $definition, 2);
    $router->add($method, $path, $handler[0], $handler[1], $handler[2] ?? []);
}

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    _resolve_request_path()
);

// ============================================================
// RÉSOLUTION DU CHEMIN DE LA REQUÊTE
//
// Scénarios sur Hostinger :
//   A) root/index.php → require public/index.php :
//      SCRIPT_NAME = /index.php, REQUEST_URI = /login
//      → retourne /login ✓
//
//   B) Apache rewrite → public/index.php :
//      SCRIPT_NAME = /public/index.php
//      REDIRECT_URL = /login (URL originale avant rewrite)
//      → retourne /login ✓
//
//   C) Accès direct /public/login via browser :
//      SCRIPT_NAME = /public/index.php, REQUEST_URI = /public/login
//      → strip /public → /login ✓
// ============================================================
function _resolve_request_path(): string
{
    $scriptBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');

    // Source 1 : REDIRECT_URL — URL avant rewrite Apache (la plus fiable)
    if (!empty($_SERVER['REDIRECT_URL'])) {
        $path = $_SERVER['REDIRECT_URL'];
        if ($scriptBase !== '' && $scriptBase !== '/'
            && str_starts_with($path, $scriptBase . '/')) {
            $path = substr($path, strlen($scriptBase)) ?: '/';
        } elseif ($path === $scriptBase) {
            $path = '/';
        }
        return '/' . ltrim($path, '/');
    }

    // Source 2 : REQUEST_URI sans query string
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if ($scriptBase !== '' && $scriptBase !== '/'
        && str_starts_with($path, $scriptBase . '/')) {
        $path = substr($path, strlen($scriptBase)) ?: '/';
    } elseif ($path === $scriptBase) {
        $path = '/';
    }

    return '/' . ltrim($path, '/');
}
