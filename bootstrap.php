<?php
declare(strict_types=1);

/**
 * AUTOSAV — Bootstrap frontal.
 * Charge la configuration, l'autoload, les helpers et les en-têtes de sécurité.
 */

define('AUTOSAV_ROOT', __DIR__);

// Le projet actuel place Core/ et Modules/ à la racine.
defined('SRC_PATH') || define('SRC_PATH', AUTOSAV_ROOT);

$autoload = AUTOSAV_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'Nenad\\Autosav\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $relative = str_replace('\\', '/', $relative);
        $path = AUTOSAV_ROOT . '/' . $relative . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    });
}

// Filet de sécurité global (CORRECTIF 2.3) : enregistré le plus tôt
// possible pour capter aussi les erreurs survenant pendant le chargement
// de la configuration elle-même.
\Nenad\Autosav\Core\Error\GlobalErrorHandler::register();

require_once AUTOSAV_ROOT . '/config/environment.php';
require_once AUTOSAV_ROOT . '/config/app.php';
require_once AUTOSAV_ROOT . '/config/constants.php';
require_once AUTOSAV_ROOT . '/config/security.php';
require_once AUTOSAV_ROOT . '/config/sessions.php';
require_once AUTOSAV_ROOT . '/config/ajax.php';
require_once AUTOSAV_ROOT . '/config/pagination.php';
require_once AUTOSAV_ROOT . '/config/dashboard.php';
require_once AUTOSAV_ROOT . '/config/maintenance.php';
require_once AUTOSAV_ROOT . '/config/modules.php';
require_once AUTOSAV_ROOT . '/config/gdpr.php';
require_once AUTOSAV_ROOT . '/config/terms.php';
require_once AUTOSAV_ROOT . '/config/database.php';
require_once AUTOSAV_ROOT . '/config/mail.php';
require_once AUTOSAV_ROOT . '/config/production.php';
require_once AUTOSAV_ROOT . '/Core/Helpers/functions.php';

if (function_exists('send_security_headers')) {
    send_security_headers();
}

\Nenad\Autosav\Core\Security\Class\SessionHandler::init();
