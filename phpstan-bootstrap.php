<?php
declare(strict_types=1);

/**
 * AUTOSAV — Bootstrap PHPStan
 *
 * Charge les constantes définies dynamiquement par les fichiers de
 * config/ (APP_DEBUG, DB_PDO_OPTIONS, PRODUCTION_BACKUP_DIR...) pour que
 * PHPStan les reconnaisse, sans dépendre d'une vraie connexion DB ni
 * d'un .env présent (CI n'en a pas).
 */

define('AUTOSAV_ROOT', __DIR__);
defined('SRC_PATH') || define('SRC_PATH', AUTOSAV_ROOT);

require_once AUTOSAV_ROOT . '/config/environment.php';
require_once AUTOSAV_ROOT . '/config/app.php';
require_once AUTOSAV_ROOT . '/config/constants.php';
require_once AUTOSAV_ROOT . '/config/security.php';
require_once AUTOSAV_ROOT . '/config/database.php';
require_once AUTOSAV_ROOT . '/config/production.php';
