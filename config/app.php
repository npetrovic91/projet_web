<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Acces direct interdit.');

// ============================================================
// AUTOSAV — Configuration generale de l'application
// ============================================================

define('APP_NAME',        'Autosav');
define('APP_VERSION',     '1.0.0');
define('APP_DESCRIPTION', 'Gestion reseau professionnel automobile multi-niveaux');
define('APP_LOCALE',      'fr');
define('APP_CHARSET',     'UTF-8');

// ============================================================
// APP_URL — URL de base de l'application
//
// PROBLEME SUR HOSTINGER (shared hosting) :
//   SCRIPT_NAME = /public/index.php
//   dirname(SCRIPT_NAME) = /public
//   → APP_URL auto-detecte = https://devnenad.fr/public  ← FAUX
//   → Tous les liens generent /public/xxx
//   → Le router recoit /public/login → aucune route → 404
//
// SOLUTION : Definir APP_URL dans .env
//   APP_URL=https://devnenad.fr   (jamais de / final)
//
// Priorite : .env APP_URL → auto-detection securisee (host seul)
// ============================================================
if (!defined('APP_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https'
        : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // 1. Lire APP_URL depuis .env (charge par environment.php)
    $envUrl = getenv('APP_URL');
    if ($envUrl !== false && $envUrl !== '') {
        define('APP_URL', rtrim($envUrl, '/'));

    } else {
        // 2. Auto-detection : on retire /public du chemin si present
        //    (artefact du double rewrite public_html/.htaccess → public/.htaccess)
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
        // Si scriptDir = /public (shared hosting), la base reelle est /
        if (preg_match('#^(/[^/]+)?/public$#i', $scriptDir)) {
            $scriptDir = preg_replace('#/public$#i', '', $scriptDir);
        }
        define('APP_URL', $scheme . '://' . $host . $scriptDir);
    }
}

// URLs des assets (servis depuis public/assets/)
define('ASSETS_URL', APP_URL . '/assets');
define('VENDOR_URL', ASSETS_URL . '/vendor');

// Pagination par defaut
define('APP_ITEMS_PER_PAGE', 25);

// Taille max upload (en octets — 5 Mo)
define('APP_MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

// Extensions autorisees pour les photos de profil
define('APP_ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('APP_ALLOWED_IMAGE_EXTS',  ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// Formats de dates
define('DATE_FORMAT',          'd/m/Y');
define('DATETIME_FORMAT',      'd/m/Y H:i');
define('DATETIME_LONG_FORMAT', 'd/m/Y H:i:s');