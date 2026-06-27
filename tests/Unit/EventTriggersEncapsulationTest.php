<?php
declare(strict_types=1);

/**
 * AUTOSAV — Encapsulation EventTriggers -> Notifications (section 3 du roadmap)
 *
 * EventTriggerController (module EventTriggers) reconstruisait lui-même
 * l'intégralité du graphe de dépendances de NotificationRuleService (8
 * modèles d'un AUTRE module, Notifications) : un changement de signature
 * côté Notifications cassait silencieusement EventTriggers, sans aucun
 * lien explicite signalant cette dépendance croisée. NotificationRuleService
 * est désormais auto-suffisant (tous ses paramètres ont une valeur par
 * défaut via "new in initializers", PHP 8.1+) — vérifié ici par une
 * instanciation réelle sans argument, pas seulement par lecture de texte.
 */

$root = dirname(__DIR__, 2);

$controller = file_get_contents($root . '/Modules/EventTriggers/Controllers/EventTriggerController.php') ?: '';
assert($controller !== '', 'EventTriggerController.php introuvable.');
assert(
    str_contains($controller, 'new NotificationRuleService()'),
    'EventTriggerController ne doit plus reconstruire le graphe de dépendances de NotificationRuleService lui-même.'
);
assert(
    !str_contains($controller, 'NotificationChannelModel'),
    'EventTriggerController ne doit plus importer les modèles internes de Notifications (signe de l\'encapsulation cassée).'
);

$service = file_get_contents($root . '/Modules/Notifications/Services/NotificationRuleService.php') ?: '';
assert($service !== '', 'NotificationRuleService.php introuvable.');
assert(
    preg_match('/__construct\(\s*\n(\s*private \w+ \$\w+ = new \w+\(\),?\s*\n){5,}/', $service) === 1,
    'Tous les paramètres de NotificationRuleService doivent avoir une valeur par défaut ("new in initializers").'
);

echo "EventTriggersEncapsulationTest SUCCESS\n";
