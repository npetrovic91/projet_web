<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : filet de sécurité global pour les erreurs non interceptées (2.3)
 *
 * Avant ce correctif, aucun set_exception_handler() n'était enregistré :
 * une exception levée par un Service et jamais catchée par le contrôleur
 * (ex. VerrouEntiteService::verrouiller() qui throw RuntimeException sur
 * conflit) produisait une page blanche, voire une fuite de stack trace si
 * APP_DEBUG était mal configuré. Désormais, tout throwable non intercepté
 * est journalisé puis affiché via une page générique (HTML) ou un JSON
 * structuré (requêtes AJAX) selon le contexte.
 */

$root = dirname(__DIR__, 2);

$handler = file_get_contents($root . '/Core/Error/GlobalErrorHandler.php') ?: '';
assert($handler !== '', 'Core/Error/GlobalErrorHandler.php introuvable.');
assert(
    str_contains($handler, 'set_exception_handler('),
    'GlobalErrorHandler doit enregistrer un set_exception_handler().'
);
assert(
    str_contains($handler, 'register_shutdown_function('),
    'GlobalErrorHandler doit aussi intercepter les erreurs fatales via register_shutdown_function().'
);
assert(
    str_contains($handler, "logger('application')->critical("),
    'Toute exception non interceptée doit être journalisée (canal application, niveau critical).'
);
assert(
    !str_contains($handler, 'set_error_handler(static function'),
    'GlobalErrorHandler ne doit PAS enregistrer un set_error_handler() actif : promouvoir les warnings/notices PHP existants en exceptions fatales serait hors périmètre et risquerait une régression massive (pages qui fonctionnent aujourd\'hui malgré un warning bénin).'
);

$bootstrap = file_get_contents($root . '/bootstrap.php') ?: '';
assert($bootstrap !== '', 'bootstrap.php introuvable.');
assert(
    str_contains($bootstrap, 'GlobalErrorHandler::register()'),
    'bootstrap.php doit enregistrer GlobalErrorHandler le plus tôt possible.'
);

echo "GlobalErrorHandlerTest SUCCESS\n";
