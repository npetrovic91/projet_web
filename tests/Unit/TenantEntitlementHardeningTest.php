<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

$middleware = file_get_contents($root . '/Core/Middleware/TenantEntitlementMiddleware.php') ?: '';
$router = file_get_contents($root . '/Core/Router/Router.php') ?: '';
$authModel = file_get_contents($root . '/Modules/Auth/Models/AuthModel.php') ?: '';
$ajaxContextModel = file_get_contents($root . '/Modules/Ajax/Models/ContextModel.php') ?: '';
$portailModel = file_get_contents($root . '/Modules/Portail/Models/ContexteActifModel.php') ?: '';
$usersModel = file_get_contents($root . '/Modules/Users/Models/UserModel.php') ?: '';

assert(str_contains($router, 'TenantEntitlementMiddleware::checkControllerAccess($route->controller)'));
assert(str_contains($middleware, 'public static function companyIsAccessible'));
assert(str_contains($middleware, 'sav_modules_societes mos'));
assert(str_contains($middleware, "st_pay.sta_code = 'a_jour'"));
assert(str_contains($middleware, "st_abo.sta_code IN ('actif', 'essai')"));
assert(str_contains($authModel, 'INNER JOIN sav_espaces_applicatifs eap'));
assert(str_contains($authModel, 'INNER JOIN sav_abonnements_societes abo'));
assert(str_contains($ajaxContextModel, 'INNER JOIN sav_espaces_applicatifs eap'));
assert(str_contains($portailModel, 'TenantEntitlementMiddleware::companyIsAccessible'));
assert(str_contains($usersModel, 'INNER JOIN sav_abonnements_societes abo'));

echo "TenantEntitlementHardeningTest SUCCESS\n";
