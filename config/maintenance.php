<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Accès direct interdit.');

// ============================================================
// AUTOSAV — Configuration du mode maintenance
// ============================================================

// Rôles toujours autorisés même en maintenance
define('MAINTENANCE_ALLOWED_ROLES', [ROLE_SUPERADMIN]);

// IPs toujours autorisées (ex: IP du dev/admin)
define('MAINTENANCE_ALLOWED_IPS', [
    '127.0.0.1',
    '::1',
]);

// Route de la page maintenance
define('MAINTENANCE_ROUTE', '/maintenance');

// Chemin direct vers la vue (sans router, pour éviter les boucles)
define('MAINTENANCE_VIEW', SRC_PATH . '/Modules/Maintenance/Views/maintenance.php');
