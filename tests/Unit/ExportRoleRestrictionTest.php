<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : exports interdits aux roles operationnels
 *
 * Cahier des charges : "Les exports sont interdits aux chef_equipe,
 * technicien et visiteur" — meme si un bouton d'export est affiche par
 * erreur cote UI, le backend doit refuser. Un audit du 2026-06-25 avait
 * trouve que les 7 endpoints exportJson() ne s'appuyaient QUE sur une
 * permission generique (standard.consulter, horaire.consulter, etc.),
 * sans aucun controle de role explicite. Corrige via
 * BaseController::requireExportAllowed().
 */

$root = dirname(__DIR__, 2);

$baseController = file_get_contents($root . '/Core/Controller/BaseController.php') ?: '';
assert(
    str_contains($baseController, 'function requireExportAllowed'),
    'BaseController doit definir requireExportAllowed().'
);
foreach (['chef_equipe', 'technicien', 'visiteur'] as $role) {
    assert(
        str_contains($baseController, "'{$role}'"),
        "requireExportAllowed() doit refuser explicitement le role {$role}."
    );
}

$exporters = [
    'Modules/Standards/Controllers/StandardController.php',
    'Modules/Horaires/Controllers/HorairesController.php',
    'Modules/Validation/Controllers/ValidationController.php',
    'Modules/Verrous/Controllers/VerrousController.php',
    'Modules/Organisation/Controllers/OrganisationController.php',
    'Modules/Referentiels/Controllers/ReferentielController.php',
    'Modules/Relations/Controllers/RelationSocieteController.php',
];

foreach ($exporters as $path) {
    $source = file_get_contents($root . '/' . $path) ?: '';
    assert($source !== '', "{$path} introuvable.");
    assert(
        (bool) preg_match('/function exportJson\([^)]*\)[^{]*\{[^}]*requireExportAllowed\(\)/s', $source),
        "{$path} : exportJson() doit appeler requireExportAllowed()."
    );
}

echo "ExportRoleRestrictionTest SUCCESS\n";
