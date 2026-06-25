<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

$htaccess = file_get_contents($root . '/.htaccess') ?: '';
$authHelper = file_get_contents($root . '/Core/Helpers/AuthHelper.php') ?: '';
$migration = file_get_contents($root . '/database/migrations/2026_06_05_v431_permissions_modules_sensibles.sql') ?: '';
$authService = file_get_contents($root . '/Modules/Auth/Services/AuthService.php') ?: '';
$userService = file_get_contents($root . '/Modules/Users/Services/UserService.php') ?: '';
$dashboardService = file_get_contents($root . '/Modules/Dashboard/Services/DashboardService.php') ?: '';

assert(!is_file($root . '/diagnostic_login_demo.php'), 'diagnostic_login_demo.php ne doit pas etre livre.');
assert(str_contains($htaccess, 'diagnostic_login_demo\.php'), 'Le diagnostic doit etre bloque explicitement par .htaccess.');

foreach ([
    'abonnements.read',
    'verrous.read',
    'validation.read',
    'standards.read',
    'horaires.read',
    'jobs.read',
] as $alias) {
    assert(str_contains($authHelper, "'{$alias}'"), "Alias permission manquant : {$alias}");
}

foreach ([
    'abonnement.gerer',
    'verrou.gerer',
    'validation.gerer',
    'standard.gerer',
    'horaire.gerer',
    'uti_acl_version',
] as $needle) {
    assert(str_contains($migration, $needle), "Migration v4.3.1 incomplete : {$needle}");
}

foreach ([
    'Modules/Standards/Controllers/StandardController.php' => ['standard.consulter', 'standard.gerer', 'updateVersion'],
    'Modules/Validation/Controllers/ValidationController.php' => ['validation.consulter', 'validation.gerer', '$filters'],
    'Modules/Horaires/Controllers/HorairesController.php' => ['horaire.consulter', 'horaire.gerer'],
    'Modules/Jobs/Controllers/JobController.php' => ['fonction.gerer'],
    'Modules/Verrous/Controllers/VerrousController.php' => ['verrou.consulter', 'verrou.gerer'],
] as $path => $needles) {
    $source = file_get_contents($root . '/' . $path) ?: '';
    assert(!str_contains($source, '$this->requireAuth();'), "{$path} ne doit plus reposer sur requireAuth seul.");
    foreach ($needles as $needle) {
        assert(str_contains($source, $needle), "{$path} ne contient pas {$needle}");
    }
}

assert(!str_contains($authService, 'private function codesRoles'), 'AuthService ne doit plus dupliquer RoleResolver.');
assert(!str_contains($authService, 'verrouillÃ'), 'AuthService ne doit plus contenir de mojibake visible.');
assert(str_contains($userService, 'RoleResolver::isSuperAdmin'), 'UserService doit deleguer a RoleResolver.');
assert(str_contains($dashboardService, 'RoleResolver::isSuperAdmin'), 'DashboardService doit deleguer a RoleResolver.');

echo "V431SecurityHardeningTest SUCCESS\n";
