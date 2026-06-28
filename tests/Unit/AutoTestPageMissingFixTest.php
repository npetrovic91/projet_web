<?php
declare(strict_types=1);

/**
 * AUTOSAV — Pages autotest manquantes pour Core_Error et Shared
 *
 * Export /autotests (vu par l'utilisateur sur le compte super.admin.demo) :
 * resume.notok = 2, les deux seuls composants signalés étant Core_Error
 * (Core/Error/GlobalErrorHandler.php, ajouté lors du correctif 2.3, sans
 * sa page autotest.php associée — convention déjà respectée par tous les
 * autres sous-dossiers de Core/) et Shared (Modules/Shared/, qui ne
 * contient que des partials de vues, jamais de classe, mais doit malgré
 * tout avoir Views/autotest.php comme toute entrée du registre des
 * modules). AutoTestService::pageAutotestExiste() vérifie la présence de
 * ce fichier ; son absence compte comme une anomalie même si aucune
 * classe PHP n'est en cause.
 */

$root = dirname(__DIR__, 2);

assert(is_file($root . '/Core/Error/autotest.php'), 'Core/Error/autotest.php doit exister (requis par AutoTestService::pageAutotestExiste()).');
assert(is_file($root . '/Modules/Shared/Views/autotest.php'), 'Modules/Shared/Views/autotest.php doit exister.');

foreach (['Core/Error/autotest.php', 'Modules/Shared/Views/autotest.php'] as $path) {
    $content = file_get_contents($root . '/' . $path) ?: '';
    assert(
        str_contains($content, "SRC_PATH . '/Modules/AutoTests/Views/module.php'"),
        "{$path} doit suivre la convention déjà établie ailleurs (Core/Security/autotest.php, Modules/Abonnements/Views/autotest.php...) : inclure la page générique de test de module."
    );
}

echo "AutoTestPageMissingFixTest SUCCESS\n";
