<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Acces direct interdit.');

/**
 * AUTOSAV — Configuration email.
 *
 * Règle production : aucune valeur sensible ne doit être codée en dur.
 * Toutes les valeurs SMTP proviennent du fichier .env ou de l'environnement serveur.
 */

$env = static function (string $key, mixed $default = ''): mixed {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
};

$envBool = static function (string $key, bool $default = false) use ($env): bool {
    $value = strtolower((string) $env($key, $default ? 'true' : 'false'));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
};

defined('MAIL_HOST')       || define('MAIL_HOST',       (string) $env('MAIL_HOST', ''));
defined('MAIL_PORT')       || define('MAIL_PORT',       (int) $env('MAIL_PORT', 587));
defined('MAIL_ENCRYPTION') || define('MAIL_ENCRYPTION', (string) $env('MAIL_ENCRYPTION', 'tls'));
defined('MAIL_USERNAME')   || define('MAIL_USERNAME',   (string) $env('MAIL_USERNAME', ''));
defined('MAIL_PASSWORD')   || define('MAIL_PASSWORD',   (string) $env('MAIL_PASSWORD', ''));
defined('MAIL_FROM_EMAIL') || define('MAIL_FROM_EMAIL', (string) $env('MAIL_FROM_ADDRESS', $env('MAIL_FROM_EMAIL', 'noreply@autosav.local')));
defined('MAIL_FROM')       || define('MAIL_FROM',       (string) $env('MAIL_FROM', MAIL_FROM_EMAIL));
defined('MAIL_FROM_NAME')  || define('MAIL_FROM_NAME',  (string) $env('MAIL_FROM_NAME', 'AUTOSAV'));
defined('MAIL_REPLY_TO')   || define('MAIL_REPLY_TO',   (string) $env('MAIL_REPLY_TO', MAIL_FROM_EMAIL));
defined('MAIL_CHARSET')    || define('MAIL_CHARSET',    (string) $env('MAIL_CHARSET', 'UTF-8'));
defined('MAIL_DEBUG')      || define('MAIL_DEBUG',      (int) $env('MAIL_DEBUG', 0));
defined('MAIL_SANDBOX')    || define('MAIL_SANDBOX',    $envBool('MAIL_SANDBOX', APP_ENV !== 'production'));
defined('MAIL_SANDBOX_TO') || define('MAIL_SANDBOX_TO', (string) $env('MAIL_SANDBOX_TO', ''));
defined('MAIL_FAIL_IF_UNCONFIGURED') || define('MAIL_FAIL_IF_UNCONFIGURED', $envBool('MAIL_FAIL_IF_UNCONFIGURED', APP_ENV === 'production'));

unset($env, $envBool);
