<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$migration = file_get_contents($root . '/database/migrations/2026_06_03_lot35_demo_reseau_automobile.sql');
$migrationLot37 = file_get_contents($root . '/database/migrations/2026_06_03_lot37_admin_societe_permissions_referentiels.sql');
$authService = file_get_contents($root . '/Modules/Auth/Services/AuthService.php');
$roleResolver = file_get_contents($root . '/Core/Security/Class/RoleResolver.php');
$rules = file_get_contents($root . '/docs/REGLES_ROLES_PROJET.md');

assert($migration !== false, 'Migration lot35 introuvable.');
assert($migrationLot37 !== false, 'Migration lot37 introuvable.');
assert($authService !== false, 'AuthService introuvable.');
assert($roleResolver !== false, 'RoleResolver introuvable.');
assert($rules !== false, 'Synthese roles projet introuvable.');

$adminGeneralPermissions = [
    'utilisateur.creer',
    'utilisateur.modifier',
    'utilisateur.bloquer',
    'societe.creer',
    'societe.modifier',
    'role.gerer',
    'fonction.gerer',
    'competence.gerer',
    'certification.gerer',
    'module.acceder',
    'notification.consulter',
    'validation.gerer',
];

foreach ($adminGeneralPermissions as $permission) {
    assert(str_contains($migration, "UNION ALL SELECT 'administrateur_general_societe', '{$permission}'")
        || str_contains($migration, "SELECT 'administrateur_general_societe' AS role_code, '{$permission}' AS permission_code")
        || str_contains($migrationLot37, "UNION ALL SELECT 'administrateur_general_societe', '{$permission}'")
        || str_contains($migrationLot37, "SELECT 'administrateur_general_societe' AS role_code, '{$permission}' AS permission_code"),
        "Permission manquante pour administrateur_general_societe : {$permission}");
}

assert(str_contains($migration, 'INSERT INTO sav_roles_permissions'));
assert(str_contains($migration, "rp.rpe_role_id = r.rol_id"));
assert(str_contains($migration, "rp.rpe_permission_id = p.per_id"));
assert(str_contains($authService, 'RoleResolver::buildSessionBlock'));
assert(str_contains($roleResolver, "'administrateur_general_societe', 'administrateur_general', 'admin_general'"));
assert(str_contains($roleResolver, "'responsable_groupe_concessions', 'administrateur_departement', 'admin_departement'"));
assert(str_contains($roleResolver, "'directeur_concession', 'responsable_apres_vente', 'responsable_garantie', 'administrateur_service', 'admin_service'"));
assert(str_contains($rules, 'Administre uniquement la societe'));
assert(str_contains($rules, 'Cree, modifie et bloque les utilisateurs'));
assert(str_contains($rules, 'Chaque utilisateur, sauf l\'administrateur general, doit avoir un superieur hierarchique.'));

echo "ProjectRoleAccessTest SUCCESS\n";
