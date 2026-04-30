<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Accès direct interdit.');

// ============================================================
// AUTOSAV — Configuration base de données
// ============================================================

$_dbEnv = APP_ENV;

$_dbConfigs = [
    'development' => [
        'driver'   => 'mysql',
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int)(getenv('DB_PORT') ?: 3306),
        'dbname'   => getenv('DB_NAME') ?: 'autosav_dev',
        'user'     => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: '',
        'charset'  => 'utf8mb4',
    ],
    'staging' => [
        'driver'   => 'mysql',
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int)(getenv('DB_PORT') ?: 3306),
        'dbname'   => getenv('DB_NAME') ?: 'autosav_staging',
        'user'     => getenv('DB_USER') ?: 'autosav_stg',
        'password' => getenv('DB_PASS') ?: '',
        'charset'  => 'utf8mb4',
    ],
    'production' => [
        'driver'   => 'mysql',
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int)(getenv('DB_PORT') ?: 3306),
        'dbname'   => getenv('DB_NAME') ?: 'autosav_prod',
        'user'     => getenv('DB_USER') ?: 'autosav_user',
        'password' => getenv('DB_PASS') ?: '',
        'charset'  => 'utf8mb4',
    ],
];

$_dbSelected = $_dbConfigs[$_dbEnv] ?? $_dbConfigs['production'];

define('DB_DRIVER',   $_dbSelected['driver']);
define('DB_HOST',     $_dbSelected['host']);
define('DB_PORT',     $_dbSelected['port']);
define('DB_NAME',     $_dbSelected['dbname']);
define('DB_USER',     $_dbSelected['user']);
define('DB_PASS',     $_dbSelected['password']);
define('DB_CHARSET',  $_dbSelected['charset']);
define('DB_PREFIX',   'sav_');

// Options PDO globales
define('DB_PDO_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
]);

unset($_dbEnv, $_dbConfigs, $_dbSelected);
