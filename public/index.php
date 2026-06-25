<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Nenad\Autosav\Core\Router\Router;

$routes = require AUTOSAV_ROOT . '/config/urls.php';
$router = new Router();
foreach ($routes as $definition => $target) {
    [$method, $path] = explode(' ', $definition, 2);
    [$controller, $action, $middlewares] = $target + [null, null, []];
    $router->add($method, $path, $controller, $action, $middlewares ?? []);
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
