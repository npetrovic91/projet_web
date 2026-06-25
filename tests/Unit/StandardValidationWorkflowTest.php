<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : workflow de validation des standards (ACC-008)
 *
 * Cahier des charges : "Un chef_departement ne peut pas publier ou
 * valider directement ses propres standards. Tout standard créé par
 * chef_departement doit être validé par un niveau supérieur avant d'être
 * actif." Un audit du 2026-06-25 avait trouvé qu'une version créée était
 * immédiatement "actif"/applicable, sans aucune notion de brouillon ni de
 * validation par un tiers.
 */

$root = dirname(__DIR__, 2);

$model = file_get_contents($root . '/Modules/Standards/Models/StandardModel.php') ?: '';
$service = file_get_contents($root . '/Modules/Standards/Services/StandardService.php') ?: '';
$controller = file_get_contents($root . '/Modules/Standards/Controllers/StandardController.php') ?: '';
$migration = file_get_contents($root . '/database/migrations/0004_workflow_validation_standards.sql') ?: '';
$urls = file_get_contents($root . '/config/urls.php') ?: '';

assert($model !== '' && $service !== '' && $controller !== '', 'Fichiers du module Standards introuvables.');

// Les 5 etats du workflow doivent exister dans le schema.
foreach (['draft', 'pending_validation', 'approved', 'rejected', 'archived'] as $etat) {
    assert(str_contains($migration, "'{$etat}'"), "Etat manquant dans la migration du workflow standards : {$etat}");
}

// Une nouvelle version doit demarrer en brouillon, jamais directement applicable.
assert(
    str_contains($model, "VALUES\n                (:standard_id, :version, :valide_du, :valide_au, :statut_id, 'draft', :user_id, :user_id)"),
    'createVersion() doit creer la version avec vst_etat_validation = draft.'
);

// Auto-validation interdite : le controle doit comparer l'auteur (vst_cree_par_utilisateur_id) a l'acteur.
assert(
    str_contains($service, "(int) (\$version['vst_cree_par_utilisateur_id'] ?? 0) === \$userId"),
    'StandardService::validerVersion() doit refuser que l\'auteur valide sa propre version.'
);
assert(
    str_contains($service, 'RoleResolver::applicationLevel($roleCodes) > self::NIVEAU_CHEF_DEPARTEMENT'),
    'StandardService doit exiger un niveau strictement superieur a chef_de_departement pour valider/rejeter.'
);

// Les versions "applicables" doivent etre filtrees sur l'etat approuve,
// pas juste sur le statut generique actif/inactif.
assert(
    str_contains($model, "vst_etat_validation = 'approved'"),
    'versionsApprouvees() doit filtrer explicitement sur l\'etat approuve.'
);

// Routes branchees et protegees CSRF.
foreach (['soumettre', 'valider', 'rejeter'] as $action) {
    assert(
        str_contains($urls, "standards/versions/{id}/{$action}") && str_contains($urls, "'csrf'"),
        "Route POST manquante ou non protegee CSRF pour l'action {$action} sur une version de standard."
    );
}

echo "StandardValidationWorkflowTest SUCCESS\n";
