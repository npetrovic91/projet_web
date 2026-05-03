<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Accès direct interdit.');

// ============================================================
// AUTOSAV — Configuration des sessions
// ============================================================

defined('SESSION_NAME')            || define('SESSION_NAME', 'AUTOSAV_SESSION');
defined('SESSION_HANDLER')         || define('SESSION_HANDLER', 'files');
defined('SESSION_SAVE_PATH')       || define('SESSION_SAVE_PATH', SESSIONS_PATH);
defined('SESSION_COOKIE_SECURE')   || define('SESSION_COOKIE_SECURE', APP_FORCE_HTTPS);
defined('SESSION_COOKIE_HTTPONLY') || define('SESSION_COOKIE_HTTPONLY', true);
defined('SESSION_COOKIE_SAMESITE') || define('SESSION_COOKIE_SAMESITE', 'Strict');
defined('SESSION_USE_STRICT_MODE') || define('SESSION_USE_STRICT_MODE', true);
defined('SESSION_GC_MAXLIFETIME')  || define('SESSION_GC_MAXLIFETIME', SESSION_LIFETIME_MINUTES * 60);
defined('SESSION_GC_PROBABILITY')  || define('SESSION_GC_PROBABILITY', 1);
defined('SESSION_GC_DIVISOR')      || define('SESSION_GC_DIVISOR', 100);
