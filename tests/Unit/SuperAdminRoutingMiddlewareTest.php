<?php
declare(strict_types=1);

/**
 * AUTOSAV — Middleware de routing pour les routes super-admin (section 3 du roadmap)
 *
 * Avant ce correctif, les 6 routes /super-admin (et alias /admin/application)
 * n'étaient protégées qu'au niveau de chaque action de SuperAdminController
 * via $this->requireRole(ROLE_SUPERADMIN) — fonctionnellement correct
 * aujourd'hui, mais fragile : une future action qui oublierait cet appel
 * resterait accessible à n'importe quel utilisateur authentifié. Le
 * RoleMiddleware existait déjà (Core/Middleware/RoleMiddleware.php,
 * dispatché par Router::applyMiddlewares() via le tag "role:xxx") mais
 * n'était utilisé par AUCUNE route du projet. Ajouté en défense en
 * profondeur sur les 6 routes super-admin, sans retirer les
 * requireRole() existants dans le contrôleur.
 */

$root = dirname(__DIR__, 2);
$urls = file_get_contents($root . '/config/urls.php') ?: '';
assert($urls !== '', 'config/urls.php introuvable.');

$superAdminRoutes = [
    "'GET /super-admin'",
    "'GET /admin/application'",
    "'GET /super-admin/application'",
    "'GET /super-admin/export.json'",
    "'GET /super-admin/justification'",
    "'POST /super-admin/justification'",
];

foreach ($superAdminRoutes as $routeKey) {
    $pos = strpos($urls, $routeKey);
    assert($pos !== false, "Route {$routeKey} introuvable dans config/urls.php.");

    $lineEnd = strpos($urls, "\n", $pos);
    $line = substr($urls, $pos, $lineEnd - $pos);

    assert(
        str_contains($line, "'role:super_administrateur'"),
        "La route {$routeKey} doit porter le middleware 'role:super_administrateur'."
    );
    assert(
        strpos($line, "'auth'") < strpos($line, "'role:super_administrateur'"),
        "Sur la route {$routeKey}, 'auth' doit precder 'role:super_administrateur' (le controle de role depend de la session authentifiee)."
    );
}

$router = file_get_contents($root . '/Core/Router/Router.php') ?: '';
assert($router !== '', 'Router.php introuvable.');
assert(
    str_contains($router, "'role'  => RoleMiddleware::check(\$param ?? '')"),
    'Router::applyMiddlewares() doit dispatcher le tag "role:xxx" vers RoleMiddleware::check().'
);

// Defense en profondeur : le requireRole() dans le controleur doit rester
// (ne pas avoir ete retire au profit du seul middleware de routage).
$controller = file_get_contents($root . '/Modules/SuperAdmin/Controllers/SuperAdminController.php') ?: '';
assert($controller !== '', 'SuperAdminController.php introuvable.');
assert(
    substr_count($controller, 'requireRole(') >= 5,
    'SuperAdminController doit conserver ses requireRole() existants (defense en profondeur, pas un remplacement).'
);

echo "SuperAdminRoutingMiddlewareTest SUCCESS\n";
