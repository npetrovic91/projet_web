<?php
declare(strict_types=1);

/**
 * AUTOSAV — Échec d'écriture d'audit transformé en alerte (section 3 du roadmap)
 *
 * UserModel::audit() journalisait déjà un échec d'écriture dans
 * sav_journaux_audit, mais uniquement sur le canal 'audit' au niveau
 * 'error' — noyé parmi des milliers d'entrées de succès routinières du
 * même canal. Un échec d'audit est un incident d'intégrité (perte de
 * traçabilité), pas un évènement d'audit comme un autre : il doit aussi
 * apparaître sur le canal 'error' (celui surveillé en priorité d'après
 * RUNBOOK_DEPLOIEMENT.md) au niveau 'critical'.
 */

$root = dirname(__DIR__, 2);
$source = file_get_contents($root . '/Modules/Users/Models/UserModel.php') ?: '';

assert($source !== '', 'UserModel.php introuvable.');
assert(
    str_contains($source, "logger('error')->critical("),
    'Un échec d\'écriture d\'audit doit aussi être journalisé en critical sur le canal error.'
);
assert(
    str_contains($source, "logger('audit')->error("),
    'Le canal audit doit conserver sa propre trace (comportement existant, ne pas régresser).'
);

echo "AuditWriteFailureAlertTest SUCCESS\n";
