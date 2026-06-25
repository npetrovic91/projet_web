<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : justification obligatoire de l'accès super_admin
 * aux données métier d'une société cliente (ACC-007)
 *
 * Cahier des charges : "Le super_admin ne doit accéder aux données métier
 * qu'en mode support/audit/sécurité/incident/maintenance avec
 * justification, et tout accès doit enregistrer utilisateur, justification,
 * date/heure, organisation, périmètre, données, durée et actions." Un
 * audit du 2026-06-25 avait trouvé que Modules/SuperAdmin ne faisait que
 * du reporting, sans aucun flux de justification ni journal dédié.
 */

$root = dirname(__DIR__, 2);

$guard = file_get_contents($root . '/Core/Security/Class/SuperAdminAccessGuard.php') ?: '';
$baseController = file_get_contents($root . '/Core/Controller/BaseController.php') ?: '';
$societeController = file_get_contents($root . '/Modules/Society/Controllers/SocieteController.php') ?: '';
$superAdminController = file_get_contents($root . '/Modules/SuperAdmin/Controllers/SuperAdminController.php') ?: '';
$migration = file_get_contents($root . '/database/migrations/0003_acces_donnees_superadmin.sql') ?: '';
$authService = file_get_contents($root . '/Modules/Auth/Services/AuthService.php') ?: '';
$urls = file_get_contents($root . '/config/urls.php') ?: '';

assert($guard !== '', 'SuperAdminAccessGuard.php introuvable.');

// Les 5 motifs du cahier des charges doivent être présents tels quels.
foreach (['support', 'audit', 'securite', 'incident', 'maintenance'] as $motif) {
    assert(str_contains($guard, "'{$motif}'"), "Motif manquant dans SuperAdminAccessGuard::MOTIFS : {$motif}");
}

// Le journal doit porter tous les champs exigés par le cahier des charges.
foreach ([
    'asd_utilisateur_id', 'asd_societe_id', 'asd_motif', 'asd_justification',
    'asd_perimetre', 'asd_actions_json', 'asd_debute_le', 'asd_termine_le',
] as $colonne) {
    assert(str_contains($migration, $colonne), "Colonne manquante dans la migration du journal super_admin : {$colonne}");
}

// Le garde doit être branché dans BaseController et appelé par SocieteController.
assert(
    str_contains($baseController, 'function requireSuperAdminJustification'),
    'BaseController doit definir requireSuperAdminJustification().'
);
assert(
    (bool) preg_match('/function show\([^)]*\)[^{]*\{.*?requireSuperAdminJustification/s', $societeController),
    'SocieteController::show() doit appeler requireSuperAdminJustification() avant d\'afficher les donnees.'
);
assert(
    (bool) preg_match('/function edit\([^)]*\)[^{]*\{.*?requireSuperAdminJustification/s', $societeController),
    'SocieteController::edit() doit appeler requireSuperAdminJustification() avant d\'afficher les donnees.'
);

// Le formulaire de justification doit exiger motif + justification, et etre protege CSRF.
assert(
    str_contains($superAdminController, 'mb_strlen($justification) < 10'),
    'justificationSubmit() doit rejeter une justification trop courte.'
);
assert(
    str_contains($urls, "'POST /super-admin/justification'") && str_contains($urls, "'csrf'"),
    'La route POST de soumission de justification doit etre protegee par le middleware csrf.'
);

// La cloture doit avoir lieu a la deconnexion (duree calculable).
assert(
    str_contains($authService, 'SuperAdminAccessGuard::cloturerTout()'),
    'AuthService::deconnecter() doit cloturer les acces justifies ouverts (calcul de duree).'
);

echo "SuperAdminJustificationTest SUCCESS\n";
