<?php
declare(strict_types=1);

/**
 * AUTOSAV — Point d'entrée racine
 * Fichier : public_html/index.php
 *
 * Délègue à public/index.php via require (pas de redirect HTTP).
 * Ainsi REDIRECT_URL reste l'URL originale du navigateur (/login, /).
 * Apache ne fait pas de double requête, le routing fonctionne.
 *
 * NOTE : SRC_PATH et CONFIG_PATH sont intentionnellement NON définis ici.
 * bootstrap.php (chargé par public/index.php) les définit correctement
 * via 'defined() || define()'. Les définir ici avec '/src' (dossier absent)
 * bloquerait la résolution des vues par BaseView::resolveViewPath().
 */

define('AUTOSAV_ROOT', __DIR__);

require_once __DIR__ . '/public/index.php';