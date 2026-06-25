<?php
declare(strict_types=1);

use Nenad\Autosav\Core\Theme\Components\Sidebar;
use Nenad\Autosav\Core\Theme\Support\Config;

require_once dirname(__DIR__) . '/bootstrap.php';

$root = dirname(__DIR__, 2);
Config::load($root . '/Core/Theme/config.json');

$_SESSION = [];
$_SESSION['csrf_token'] = 'sidebar-context-token';
$_SESSION['csrf_token_expiry'] = time() + 3600;
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Super Admin',
    'role_codes' => ['super_administrateur'],
    'permissions' => ['*'],
    'actual_society_id' => 10,
    'actual_concession_id' => 42,
    'actual_brand' => 84,
];
$_SESSION['user_roles'] = ['super_administrateur'];
$_SESSION['user_permissions'] = ['*'];
$_SESSION['active_company_id'] = 10;
$_SESSION['active_concession_id'] = 42;
$_SESSION['active_brand_id'] = 84;
$_SESSION['concession'] = ['id' => 42, 'soc_id' => 42, 'soc_nom' => 'Concession session'];
$_SESSION['marque'] = ['id' => 84, 'soc_id' => 84, 'soc_nom' => 'Marque session'];
$_SESSION['available_companies'] = [
    ['soc_id' => 10, 'soc_nom' => 'Societe principale'],
    ['soc_id' => 42, 'soc_nom' => 'Concession selectionnee'],
    ['soc_id' => 77, 'soc_nom' => 'Autre concession'],
];
$_SESSION['available_brands'] = [
    ['soc_id' => 84, 'soc_nom' => 'Marque selectionnee'],
    ['soc_id' => 91, 'soc_nom' => 'Autre marque'],
];

$sidebar = (new Sidebar())->render();

assert(str_contains($sidebar, 'name="active_concession_id"'));
assert(str_contains($sidebar, 'name="active_brand_id"'));
assert(str_contains($sidebar, 'data-session-key="active_concession_id"'));
assert(str_contains($sidebar, 'data-session-key="active_brand_id"'));
assert(str_contains($sidebar, 'data-selected-value="42"'));
assert(str_contains($sidebar, 'data-selected-value="84"'));
assert(str_contains($sidebar, '<option value="42" selected>Concession selectionnee</option>'));
assert(str_contains($sidebar, '<option value="84" selected>Marque selectionnee</option>'));
assert(str_contains($sidebar, "'X-CSRF-Token': csrfToken"));
assert(str_contains($sidebar, 'values["_csrf_token"] = csrfToken'));

$_SESSION['active_concession_id'] = 77;
$_SESSION['active_brand_id'] = 91;
$sidebar = (new Sidebar())->render();

assert(str_contains($sidebar, 'data-selected-value="77"'));
assert(str_contains($sidebar, 'data-selected-value="91"'));
assert(str_contains($sidebar, '<option value="77" selected>Autre concession</option>'));
assert(str_contains($sidebar, '<option value="91" selected>Autre marque</option>'));

$controller = file_get_contents($root . '/Modules/Ajax/Controllers/ContextController.php') ?: '';
$model = file_get_contents($root . '/Modules/Ajax/Models/ContextModel.php') ?: '';
$themeAutotest = file_get_contents($root . '/Core/Theme/autotest.php') ?: '';

assert(str_contains($controller, "\$_SESSION['active_concession_id'] = \$companyId"));
assert(str_contains($controller, "\$_SESSION['user']['active_brand_id'] = \$brandId"));
assert(str_contains($model, 'seu_concession_active_id = :concession_id'));
assert(str_contains($themeAutotest, 'autotest-sidebar-context'));
assert(str_contains($themeAutotest, 'active_concession_id'));
assert(str_contains($themeAutotest, 'active_brand_id'));

echo "SidebarContextPersistenceTest SUCCESS\n";
