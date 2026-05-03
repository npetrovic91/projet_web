<?php
declare(strict_types=1);

/**
 * AUTOSAV — Point d'entrée racine
 * Fichier : public_html/index.php
 *
 * Délègue à public/index.php via require (pas de redirect HTTP).
 * Ainsi REDIRECT_URL reste l'URL originale du navigateur (/login, /).
 * Apache ne fait pas de double requête, le routing fonctionne.
 */

define('AUTOSAV_ROOT', __DIR__);
define('CONFIG_PATH',  AUTOSAV_ROOT . '/config');
define('SRC_PATH',     AUTOSAV_ROOT . '/src');

require_once __DIR__ . '/public/index.php';
