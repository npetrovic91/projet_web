<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Router;

use Nenad\Autosav\Core\Middleware\AuthMiddleware;
use Nenad\Autosav\Core\Middleware\CsrfMiddleware;
use Nenad\Autosav\Core\Middleware\AjaxMiddleware;
use Nenad\Autosav\Core\Middleware\MaintenanceMiddleware;
use Nenad\Autosav\Core\Middleware\RoleMiddleware;

/**
 * AUTOSAV — Routeur principal
 * Fichier : src/Core/Router/Router.php
 * Rôle    : Résoudre les URLs entrantes vers les contrôleurs/actions
 *           et appliquer les middlewares configurés pour chaque route.
 */
class Router
{
    /** @var Route[] */
    private array $routes = [];

    /**
     * Enregistre une route.
     */
    public function add(
        string $method,
        string $path,
        string $controller,
        string $action,
        array  $middlewares = []
    ): void {
        $this->routes[] = new Route($method, $path, $controller, $action, $middlewares);
    }

    /**
     * Dispatche la requête entrante.
     */
    public function dispatch(string $method, string $uri): void
    {
        // Normaliser l'URI (supprimer query string, trailing slash)
        $uri = strtok($uri, '?');
        $uri = '/' . trim($uri, '/');

        $method = strtoupper($method);

        // Vérification maintenance (TOUJOURS en premier, toutes routes)
        MaintenanceMiddleware::check($uri);

        foreach ($this->routes as $route) {
            if ($route->method !== $method) {
                continue;
            }

            if (!$route->matches($uri)) {
                continue;
            }

            // Route trouvée → appliquer les middlewares dans l'ordre
            $this->applyMiddlewares($route);

            // Instancier le contrôleur
            $controllerClass = 'Nenad\\Autosav\\Modules\\' . $route->controller;
            if (!class_exists($controllerClass)) {
                $this->sendNotFound("Contrôleur introuvable : {$controllerClass}");
                return;
            }

            $controller = new $controllerClass();
            $action     = $route->action;

            if (!method_exists($controller, $action)) {
                $this->sendNotFound("Action introuvable : {$action}");
                return;
            }

            // Appel de l'action avec les parametres extraits dans l'ordre de l'URL.
            $controller->$action(...array_values($route->params));
            return;
        }

        // Aucune route trouvée
        $this->sendNotFound("Route non trouvée : {$method} {$uri}");
    }

    /**
     * Applique les middlewares dans l'ordre déclaré.
     * Un middleware peut interrompre l'exécution (exit/redirect).
     */
    private function applyMiddlewares(Route $route): void
    {
        foreach ($route->middlewares as $middleware) {
            // Middleware avec paramètre : "role:SUPERADMIN"
            if (str_contains($middleware, ':')) {
                [$name, $param] = explode(':', $middleware, 2);
            } else {
                $name  = $middleware;
                $param = null;
            }

            match ($name) {
                'auth'  => AuthMiddleware::check(),
                'csrf'  => CsrfMiddleware::check(),
                'ajax'  => AjaxMiddleware::check(),
                'role'  => RoleMiddleware::check($param ?? ''),
                'maintenance' => MaintenanceMiddleware::check($_SERVER['REQUEST_URI'] ?? '/'),
                default => null,
            };
        }
    }

    /**
     * Gestion des routes non trouvées (404).
     */
    private function sendNotFound(string $debugMsg = ''): void
    {
        http_response_code(404);

        // Réponse JSON si requête AJAX
        if (
            ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        ) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'code'    => 404,
                'message' => 'Ressource introuvable.',
                'data'    => null,
                'errors'  => null,
            ]);
            exit;
        }

        // Réponse HTML
        if (APP_DEBUG && $debugMsg) {
            echo '<pre style="font-family:monospace;padding:1em;background:#fdd">';
            echo '<strong>404 — ' . htmlspecialchars($debugMsg) . '</strong>';
            echo '</pre>';
        } else {
            $errorView = SRC_PATH . '/Core/Theme/Views/errors/404.php';
            if (file_exists($errorView)) {
                include $errorView;
            } else {
                echo '<h1>404 — Page introuvable</h1>';
            }
        }
        exit;
    }
}
