<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : chef_departement limité à son propre département
 *
 * ACC-004 du cahier des charges : "Un chef_departement ne peut créer qu'un
 * utilisateur de rôle inférieur dans son propre département." Un audit du
 * 2026-06-25 avait trouvé que la hiérarchie de rôle était bien vérifiée
 * (UserService::rolesAttribuables), mais que departement_ids n'était filtré
 * que par société gérée, jamais par département propre à l'acteur — un
 * chef_departement pouvait donc affecter un utilisateur à N'IMPORTE QUEL
 * département de sa société, pas seulement le sien. Corrigé via
 * UserService::departementsGerables().
 */

$root = dirname(__DIR__, 2);
$userService = file_get_contents($root . '/Modules/Users/Services/UserService.php') ?: '';

assert($userService !== '', 'UserService.php introuvable.');
assert(
    str_contains($userService, 'function departementsGerables'),
    'UserService doit definir departementsGerables().'
);
assert(
    str_contains($userService, "in_array('chef_de_departement', \$roleCodes, true)"),
    'departementsGerables() doit detecter specifiquement le role chef_de_departement.'
);
assert(
    str_contains($userService, 'departementsUtilisateur($utilisateurId)'),
    'departementsGerables() doit lire les departements reels de l\'acteur via UserModel::departementsUtilisateur().'
);

// La restriction doit etre appliquee dans enregistrer(), pas seulement definie.
assert(
    (bool) preg_match(
        '/\$departementsGerables\s*=\s*\$this->departementsGerables\(\$utilisateurAction\);.*?array_intersect\(\$relations\[.departement_ids.\],\s*\$departementsGerables\)/s',
        $userService
    ),
    'enregistrer() doit intersecter departement_ids avec departementsGerables() quand l\'acteur est chef_de_departement.'
);

echo "ChefDepartementScopeTest SUCCESS\n";
