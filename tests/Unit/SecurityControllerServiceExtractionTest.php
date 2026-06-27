<?php
declare(strict_types=1);

/**
 * AUTOSAV — Extraction de la logique métier de SecurityController (section 3 du roadmap)
 *
 * La validation de la raison de déblocage, le choix IP/compte et la
 * gestion d'erreur vivaient directement dans le contrôleur — logique
 * métier non testable indépendamment du HTTP. Extraite vers
 * SecurityMonitoringService::debloquer(), avec le shape de retour
 * structuré déjà dominant ailleurs dans le projet (cf.
 * Core/Services/Concerns/ServiceResponse.php).
 */

$root = dirname(__DIR__, 2);

$servicePath = $root . '/Modules/Administration/Services/SecurityMonitoringService.php';
$service = file_get_contents($servicePath) ?: '';
assert($service !== '', 'SecurityMonitoringService.php introuvable.');
assert(str_contains($service, 'function debloquer('), 'Le Service doit exposer debloquer().');
assert(str_contains($service, 'function tableauDeBord('), 'Le Service doit exposer tableauDeBord().');
assert(
    str_contains($service, "if (\$reason === '')"),
    'La validation de la raison obligatoire doit vivre dans le Service, pas dans le contrôleur.'
);

$controller = file_get_contents($root . '/Modules/Administration/Controllers/SecurityController.php') ?: '';
assert($controller !== '', 'SecurityController.php introuvable.');
assert(
    str_contains($controller, 'new SecurityMonitoringService()'),
    'SecurityController doit déléguer au Service plutôt qu\'au Model directement.'
);
assert(
    !str_contains($controller, 'La raison du déblocage est obligatoire'),
    'La validation métier ne doit plus être dupliquée dans le contrôleur.'
);

echo "SecurityControllerServiceExtractionTest SUCCESS\n";
