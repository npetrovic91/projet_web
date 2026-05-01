<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Router;

/**
 * AUTOSAV — Définition d'une route
 * Fichier : src/Core/Router/Route.php
 */
class Route
{
    public readonly string $method;
    public readonly string $path;
    public readonly string $controller;
    public readonly string $action;
    public readonly array  $middlewares;

    /** Tableau des paramètres extraits de l'URL ({id}, {token}) */
    public array $params = [];

    public function __construct(
        string $method,
        string $path,
        string $controller,
        string $action,
        array  $middlewares = []
    ) {
        $this->method      = strtoupper($method);
        $this->path        = $path;
        $this->controller  = $controller;
        $this->action      = $action;
        $this->middlewares = $middlewares;
    }

    /**
     * Tente de faire correspondre une URL avec cette route.
     * Extrait les paramètres nommés ({id}, {token}).
     *
     * @return bool true si correspondance
     */
    public function matches(string $url): bool
    {
        // Construire le pattern regex à partir de la route
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $this->path);
        $pattern = '#^' . $pattern . '$#u';

        if (preg_match($pattern, $url, $matches)) {
            // Extraire uniquement les groupes nommés
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }
}
