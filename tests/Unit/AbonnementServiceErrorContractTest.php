<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : convergence de la gestion d'erreurs (AbonnementService)
 *
 * Sur 44 Services du projet, un seul (AbonnementService) utilisait des
 * exceptions (InvalidArgumentException) pour signaler des erreurs de
 * validation métier, alors que le reste du projet utilise un contrat de
 * retour ['success' => bool, 'message' => ..., 'errors' => [...]]. Les
 * exceptions doivent rester réservées aux pannes système/infra, jamais à
 * la validation métier (qui doit être un retour normal, pas un flux de
 * contrôle exceptionnel).
 */

$root = dirname(__DIR__, 2);

$service = file_get_contents($root . '/Modules/Abonnements/Services/AbonnementService.php') ?: '';
$controller = file_get_contents($root . '/Modules/Abonnements/Controllers/AbonnementController.php') ?: '';

assert($service !== '' && $controller !== '', 'Fichiers AbonnementService/AbonnementController introuvables.');

assert(
    !str_contains($service, 'InvalidArgumentException'),
    'AbonnementService ne doit plus lever d\'exception pour de la validation metier.'
);
assert(
    !str_contains($controller, 'InvalidArgumentException') && !str_contains($controller, 'catch ('),
    'AbonnementController ne doit plus attraper d\'exception pour gerer une erreur de validation.'
);

foreach ([
    'enregistrerAbonnement', 'enregistrerFormule', 'enregistrerEspace', 'enregistrerModuleSociete',
    'supprimerAbonnement', 'supprimerFormule', 'supprimerEspace', 'supprimerModuleSociete',
] as $methode) {
    assert(
        (bool) preg_match("/function {$methode}\\([^)]*\\): array/", $service),
        "AbonnementService::{$methode}() doit retourner array (contrat success/message/errors), pas void/int/bool."
    );
}

assert(
    str_contains($controller, "\$resultat['success']"),
    'AbonnementController doit lire \'success\' sur le retour du service plutot que de presumer la reussite.'
);

echo "AbonnementServiceErrorContractTest SUCCESS\n";
