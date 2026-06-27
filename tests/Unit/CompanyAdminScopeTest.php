<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

$sidebar = file_get_contents($root . '/Core/Theme/Components/Sidebar.php') ?: '';
$themeConfig = file_get_contents($root . '/Core/Theme/config.json') ?: '';
$authHelper = file_get_contents($root . '/Core/Helpers/AuthHelper.php') ?: '';
$baseController = file_get_contents($root . '/Core/Controller/BaseController.php') ?: '';
$roleController = file_get_contents($root . '/Modules/Roles/Controllers/RoleController.php') ?: '';
$roleModel = file_get_contents($root . '/Modules/Roles/Models/RolePermissionModel.php') ?: '';
$userService = file_get_contents($root . '/Modules/Users/Services/UserService.php') ?: '';
$userModel = file_get_contents($root . '/Modules/Users/Models/UserModel.php') ?: '';
$functionService = file_get_contents($root . '/Modules/Functions/Services/FunctionService.php') ?: '';
$skillService = file_get_contents($root . '/Modules/Skills/Services/SkillService.php') ?: '';
$qualificationService = file_get_contents($root . '/Modules/Qualifications/Services/QualificationService.php') ?: '';
$functionController = file_get_contents($root . '/Modules/Functions/Controllers/FunctionController.php') ?: '';
$skillController = file_get_contents($root . '/Modules/Skills/Controllers/SkillController.php') ?: '';
$qualificationController = file_get_contents($root . '/Modules/Qualifications/Controllers/QualificationController.php') ?: '';
$jobController = file_get_contents($root . '/Modules/Jobs/Controllers/JobController.php') ?: '';
$skillModel = file_get_contents($root . '/Modules/Skills/Models/SkillModel.php') ?: '';
$qualificationModel = file_get_contents($root . '/Modules/Qualifications/Models/QualificationModel.php') ?: '';
$functionModel = file_get_contents($root . '/Modules/Functions/Models/FunctionModel.php') ?: '';
$contextController = file_get_contents($root . '/Modules/Ajax/Controllers/ContextController.php') ?: '';
$csrfMiddleware = file_get_contents($root . '/Core/Middleware/CsrfMiddleware.php') ?: '';
$migrationLot37 = file_get_contents($root . '/database/migrations/2026_06_03_lot37_admin_societe_permissions_referentiels.sql') ?: '';
$migrationLot38 = file_get_contents($root . '/database/seeds/2026_06_03_lot38_contextes_roles_admin_reseau.sql') ?: '';

assert(json_decode($themeConfig, true) !== null, 'Theme config JSON invalide.');

foreach (['/users', '/admin/roles', '/functions', '/admin/skills', '/admin/qualifications'] as $route) {
    assert(str_contains($themeConfig, $route), "Route menu manquante : {$route}");
}

foreach (['functions.read', 'skills.read', 'qualifications.read'] as $alias) {
    assert(str_contains($authHelper, "'{$alias}'"), "Alias permission manquant : {$alias}");
}

assert(str_contains($sidebar, 'has_permission($permission)'), 'Le sidebar doit utiliser les alias has_permission.');
assert(str_contains($baseController, "\$_SESSION['active_concession_id']"), 'BaseController doit lire la concession active.');
assert(str_contains($contextController, 'refreshAuthorizationContext'), 'Le changement de societe doit recharger roles et permissions.');
assert(str_contains($contextController, 'listerRoles($userId, $companyId, null, $brandId)'), 'ContextController doit recalculer les roles par societe et marque active.');
assert(str_contains($contextController, 'listerPermissions($userId, $companyId, null, $brandId)'), 'ContextController doit recalculer les permissions par societe et marque active.');

assert(str_contains($roleController, 'forcerSocieteRole'), 'RoleController doit forcer la societe proprietaire.');
assert(str_contains($roleController, 'peutAdministrerRole'), 'RoleController doit verifier le perimetre role.');
assert(str_contains($roleModel, 'roleDansSocietesAutorisees'), 'RolePermissionModel doit verifier le role dans les societes autorisees.');
assert(str_contains($roleModel, 'societe_ids_autorisees'), 'RolePermissionModel doit filtrer les roles par societe.');

assert(str_contains($userModel, 'rolesActifs(?array $societesAutorisees = null)'), 'Roles utilisateurs filtrables par societe.');
assert(str_contains($userModel, 'fonctionsActives(?array $societesAutorisees = null)'), 'Fonctions utilisateurs filtrables par societe.');
assert(str_contains($userService, 'filtrerAssignmentsParCatalogue'), 'Assignments competences/certifications doivent etre filtres.');

foreach ([
    'FunctionService' => $functionService,
    'SkillService' => $skillService,
    'QualificationService' => $qualificationService,
] as $name => $source) {
    assert(str_contains($source, 'Selectionnez une societe active'), "{$name} doit refuser une creation hors societe active.");
    assert(str_contains($source, 'catch (\\Throwable $e)'), "{$name} doit convertir les exceptions SQL en erreur metier.");
    assert(str_contains($source, 'safeLog'), "{$name} doit proteger les logs contre les erreurs de fichiers.");
    assert(str_contains($source, '_validate_failed'), "{$name} doit convertir les erreurs de validation DB en erreur metier.");
}

assert(str_contains($functionService, '{2,80}'), 'FunctionService doit respecter fon_code varchar(80).');
assert(str_contains($csrfMiddleware, 'catch (\\Throwable)'), 'CsrfMiddleware ne doit pas produire de 500 si le logger est indisponible.');

foreach ([
    $functionController => ['functions.read', 'functions.manage', 'canManageFunction'],
    $skillController => ['skills.read', 'skills.manage', 'canManageSkill'],
    $qualificationController => ['qualifications.read', 'qualifications.manage', 'canManageQualification'],
] as $source => $needles) {
    foreach ($needles as $needle) {
        assert(str_contains($source, $needle), "Controle manquant : {$needle}");
    }
}

foreach ([
    'FunctionController' => $functionController,
    'SkillController' => $skillController,
    'QualificationController' => $qualificationController,
    'JobController' => $jobController,
] as $name => $source) {
    assert(!str_contains($source, 'private function operatorId'), "{$name} ne doit pas redescendre operatorId() en private.");
    assert(!str_contains($source, '$this->validateCsrf();'), "{$name} doit laisser la validation CSRF au middleware route.");
}

foreach ([
    'SkillModel' => $skillModel,
    'QualificationModel' => $qualificationModel,
    'FunctionModel' => $functionModel,
] as $name => $source) {
    assert(!str_contains($source, "'\\n WHERE"), "{$name} ne doit pas injecter de retour ligne SQL litteral.");
    assert(!str_contains($source, "'\\n ORDER"), "{$name} ne doit pas injecter de retour ligne SQL litteral.");
    assert(str_contains($source, 'insertDynamic'), "{$name} doit utiliser une insertion dynamique.");
    assert(str_contains($source, 'filterColumns'), "{$name} doit filtrer les colonnes absentes.");
    assert(!str_contains($source, 'private function insertDynamic'), "{$name} ne doit plus dupliquer insertDynamic.");
    assert(!str_contains($source, 'private function tableColumns'), "{$name} ne doit plus dupliquer tableColumns.");
}

$baseModel = file_get_contents($root . '/Core/Model/BaseModel.php') ?: '';
$roleResolver = file_get_contents($root . '/Core/Security/Class/RoleResolver.php') ?: '';
$authModel = file_get_contents($root . '/Modules/Auth/Models/AuthModel.php') ?: '';
$authMiddleware = file_get_contents($root . '/Core/Middleware/AuthMiddleware.php') ?: '';
$tenantMiddleware = file_get_contents($root . '/Core/Middleware/TenantEntitlementMiddleware.php') ?: '';

assert(str_contains($baseModel, 'protected function insertDynamic'), 'BaseModel doit centraliser insertDynamic.');
assert(str_contains($baseModel, 'protected function filterColumns'), 'BaseModel doit centraliser filterColumns.');
assert(str_contains($baseModel, 'SHOW COLUMNS'), 'BaseModel doit adapter les insertions au schema SQL installe.');
assert(str_contains($roleResolver, 'final class RoleResolver'), 'RoleResolver centralise les roles.');
assert(str_contains($authModel, 'rcu.rcu_marque_societe_id = :marque_id'), 'AuthModel doit filtrer les roles par marque active.');
assert(str_contains($authMiddleware, 'uti_acl_version'), 'AuthMiddleware doit verifier la version ACL.');
assert(str_contains($tenantMiddleware, 'tenant_entitlement_cache'), 'TenantEntitlementMiddleware doit cacher les droits tenant.');

foreach (['fonction.gerer', 'competence.gerer', 'certification.gerer'] as $permission) {
    assert(str_contains($migrationLot37, $permission), "Permission lot37 manquante : {$permission}");
}

foreach (['admin.general', 'responsable.groupe', 'administrateur_general_societe', 'responsable_groupe_concessions', 'DIRECTION_GENERALE'] as $needle) {
    assert(str_contains($migrationLot38, $needle), "Lot38 incomplet : {$needle}");
}

echo "CompanyAdminScopeTest SUCCESS\n";
