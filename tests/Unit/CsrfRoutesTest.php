<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$routes = require SRC_PATH . '/config/urls.php';
$erreurs = [];
$totalPost = 0;

foreach ($routes as $definition => $route) {
    if (!str_starts_with((string) $definition, 'POST ')) {
        continue;
    }
    $totalPost++;
    $middlewares = $route[2] ?? [];
    if (!is_array($middlewares)) {
        $erreurs[] = $definition . ' : middlewares invalides';
        continue;
    }

    $middlewares = array_map('strval', $middlewares);
    $protege = in_array('csrf', $middlewares, true)
        || in_array('ajax', $middlewares, true)
        || in_array('api', $middlewares, true)
        || in_array('api-key', $middlewares, true);

    if (!$protege) {
        $erreurs[] = $definition . ' : route POST sans csrf/ajax/api';
    }
}

assert($totalPost > 0, 'Aucune route POST détectée.');
assert($erreurs === [], implode("\n", $erreurs));

echo "CsrfRoutesTest SUCCESS — {$totalPost} routes POST protégées\n";
