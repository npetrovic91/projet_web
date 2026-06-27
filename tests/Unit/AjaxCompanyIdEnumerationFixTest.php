<?php
declare(strict_types=1);

/**
 * AUTOSAV — company_id en GET retiré des catalogues Ajax (section 3 du roadmap)
 *
 * JobsAjaxController::list()/search() et
 * FunctionsAjaxController::requestedCompanyId() acceptaient un company_id
 * arbitraire fourni par le client pour filtrer un catalogue (métiers /
 * fonctions) qui inclut des entrées spécifiques à une société, pas
 * seulement globales. Le middleware tenant bloque l'accès aux données
 * métier sensibles, mais ce filtre permettait de sonder l'EXISTENCE
 * d'entrées de catalogue propres à n'importe quelle société, y compris
 * une dont l'utilisateur n'est pas membre. Aucune vue/JS du projet
 * n'envoyait réellement ce paramètre (vérifié) : suppression sans perte
 * fonctionnelle, uniquement la société active de la session désormais.
 *
 * UsersAjaxController::getSubordinates() conserve son filtre company_id :
 * il ne fait que NARROWER une liste déjà autorisée par
 * assertCanManage($targetId) (jamais l'élargir), ce n'est pas le même
 * risque.
 */

$root = dirname(__DIR__, 2);

$jobs = file_get_contents($root . '/Modules/Ajax/Controllers/JobsAjaxController.php') ?: '';
assert($jobs !== '', 'JobsAjaxController.php introuvable.');
assert(
    !str_contains($jobs, "request->get('company_id')"),
    'JobsAjaxController ne doit plus accepter company_id depuis la requête.'
);

$functions = file_get_contents($root . '/Modules/Ajax/Controllers/FunctionsAjaxController.php') ?: '';
assert($functions !== '', 'FunctionsAjaxController.php introuvable.');
assert(
    !str_contains($functions, "request->get('company_id')"),
    'FunctionsAjaxController ne doit plus accepter company_id depuis la requête.'
);

echo "AjaxCompanyIdEnumerationFixTest SUCCESS\n";
