<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

$usersController = file_get_contents($root . '/Modules/Users/Controllers/UserController.php') ?: '';
$usersService = file_get_contents($root . '/Modules/Users/Services/UserService.php') ?: '';
$rolesAjaxController = file_get_contents($root . '/Modules/Ajax/Controllers/RolesAjaxController.php') ?: '';
$superAdminController = file_get_contents($root . '/Modules/SuperAdmin/Controllers/SuperAdminController.php') ?: '';
$autoTestController = file_get_contents($root . '/Modules/AutoTests/Controllers/AutoTestController.php') ?: '';

assert(str_contains($usersController, "requirePermission('utilisateur.creer')"));
assert(str_contains($usersController, "requirePermission('utilisateur.modifier')"));
assert(str_contains($usersController, "requirePermission('utilisateur.bloquer')"));
assert(str_contains($usersService, "'super_administrateur', 'super_admin', 'superadmin'"));
assert(str_contains($usersService, '$rolesRefuses = array_diff'));
assert(str_contains($rolesAjaxController, "LOWER(r.rol_code) NOT IN"));
// 2026-06-25 : ajout de justificationForm()/justificationSubmit() (ACC-007,
// acces justifie aux donnees metier), toutes deux protegees par requireRole()
// comme les actions existantes : 3 + 2 = 5.
assert(substr_count($superAdminController, 'requireRole(') === 5);
assert(substr_count($autoTestController, 'requireRole(') === 4);

echo "AuthorizationHardeningTest SUCCESS\n";
