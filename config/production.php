<?php
declare(strict_types=1);

/**
 * AUTOSAV — Configuration production.
 * Toutes les valeurs sensibles doivent venir du fichier .env ou de l'environnement serveur.
 */

defined('AUTOSAV_ROOT') or die('Accès direct interdit.');

if (!function_exists('env_string')) {
    function env_string(string $key, string $default = ''): string
    {
        $value = getenv($key);
        return $value === false ? $default : (string) $value;
    }
}

if (!function_exists('env_int')) {
    function env_int(string $key, int $default): int
    {
        $value = getenv($key);
        return $value === false || $value === '' ? $default : (int) $value;
    }
}

if (!function_exists('env_bool')) {
    function env_bool(string $key, bool $default = false): bool
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}

defined('PRODUCTION_BACKUP_DIR') || define('PRODUCTION_BACKUP_DIR', env_string('BACKUP_DIR', STORAGE_PATH . '/backups'));
defined('PRODUCTION_BACKUP_RETENTION_DAYS') || define('PRODUCTION_BACKUP_RETENTION_DAYS', env_int('BACKUP_RETENTION_DAYS', 30));
defined('PRODUCTION_LOG_RETENTION_DAYS') || define('PRODUCTION_LOG_RETENTION_DAYS', env_int('LOG_RETENTION_DAYS', 90));
defined('PRODUCTION_HEALTH_TOKEN') || define('PRODUCTION_HEALTH_TOKEN', env_string('HEALTHCHECK_TOKEN', ''));

// AUDIT 2026-06-21 — correctif point 4.3 :
// Un jeton laissé à sa valeur d'exemple est public (présent dans .env.example,
// la documentation de déploiement et le rapport d'audit) : il ne doit JAMAIS
// être traité comme un secret valide, même si quelqu'un le soumet volontairement.
// CORRECTIF HIGH-3 (audit DevOps 2026-06-26) : .env.production.template
// utilise désormais "REMPLACER_TOKEN_HEALTHCHECK_64_CHARS" comme texte de
// placeholder — ajouté à la liste reconnue pour qu'un oubli de remplacement
// reste bloqué comme avant (sinon ce nouveau texte, public dans le dépôt,
// passerait inaperçu du filtre). Un vrai token (bin2hex(random_bytes(32)))
// fait toujours 64 caractères hexadécimaux : une valeur plus courte ou
// contenant un caractère non hexadécimal est aussi traitée comme suspecte.
defined('PRODUCTION_HEALTH_TOKEN_IS_PLACEHOLDER') || define(
    'PRODUCTION_HEALTH_TOKEN_IS_PLACEHOLDER',
    PRODUCTION_HEALTH_TOKEN === ''
    || preg_match('/^(changer_ce_token|change_me|changeme|change-me|todo|exemple|example|placeholder|remplacer)/i', PRODUCTION_HEALTH_TOKEN) === 1
    || !preg_match('/^[0-9a-f]{64}$/i', PRODUCTION_HEALTH_TOKEN)
);

defined('PRODUCTION_HEALTH_PUBLIC') || define('PRODUCTION_HEALTH_PUBLIC', env_bool('HEALTHCHECK_PUBLIC', false));
defined('PRODUCTION_BACKUP_COMPRESS') || define('PRODUCTION_BACKUP_COMPRESS', env_bool('BACKUP_COMPRESS', true));
defined('PRODUCTION_DB_DUMP_BINARY') || define('PRODUCTION_DB_DUMP_BINARY', env_string('MYSQLDUMP_BINARY', 'mysqldump'));
defined('PRODUCTION_PHP_BINARY') || define('PRODUCTION_PHP_BINARY', env_string('PHP_BINARY_PATH', PHP_BINARY));