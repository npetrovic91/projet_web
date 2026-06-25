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
// APP_URL
// Priorité : .env APP_URL → auto-détection (host seul)
// ============================================================
if (!defined('APP_URL')) {
    $envUrl = getenv('APP_URL');
    if ($envUrl !== false && $envUrl !== '') {
        define('APP_URL', rtrim($envUrl, '/'));
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        define('APP_URL', $scheme . '://' . $host);
    }
}

// ============================================================
// ASSETS — Les assets sont dans public_html/assets/ (URL: /assets/)
// Structure Hostinger :
//   public_html/assets/vendor/adminlte/  ← URL: /assets/vendor/adminlte
// ============================================================
define('ASSETS_URL', APP_URL . '/assets');
define('VENDOR_URL', ASSETS_URL . '/vendor');

// ============================================================
// DIVERS
// ============================================================
define('APP_ITEMS_PER_PAGE',    25);
define('APP_MAX_UPLOAD_SIZE',   5 * 1024 * 1024);
define('APP_ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('APP_ALLOWED_IMAGE_EXTS',  ['jpg', 'jpeg', 'png', 'webp', 'gif']);

define('DATE_FORMAT',          'd/m/Y');
define('DATETIME_FORMAT',      'd/m/Y H:i');
define('DATETIME_LONG_FORMAT', 'd/m/Y H:i:s');