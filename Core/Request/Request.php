<?php
declare(strict_types=1);
// ============================================================
// src/Core/Request/Request.php
// Abstraction de la requête HTTP entrante
// Namespace : Nenad\Autosav\Core\Request
// ============================================================

namespace Nenad\Autosav\Core\Request;

class Request
{
    /** @var array<string,string> Paramètres extraits de la route ({id}, {token}...) */
    private array $routeParams = [];

    // ---- Méthode HTTP ----

    public function getMethod(): string
    {
        // Supporter le "method override" via _method (formulaires HTML)
        if (isset($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isGet(): bool    { return $this->getMethod() === 'GET'; }
    public function isPost(): bool   { return $this->getMethod() === 'POST'; }
    public function isPut(): bool    { return $this->getMethod() === 'PUT'; }
    public function isDelete(): bool { return $this->getMethod() === 'DELETE'; }

    // ---- URI ----

    public function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        // Supprimer le query string
        $uri = strtok($uri, '?') ?: '/';
        return '/' . ltrim($uri, '/');
    }

    public function getQueryString(): string
    {
        return $_SERVER['QUERY_STRING'] ?? '';
    }

    public function getFullUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    // ---- IP ----

    /**
     * Obtenir l'IP réelle du client.
     * Gère les proxies (X-Forwarded-For) avec validation.
     */
    public function getIp(): string
    {
        // En production sur Hostinger, l'IP est dans REMOTE_ADDR
        // X-Forwarded-For uniquement si on est derrière un reverse proxy de confiance
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Valider que c'est une IP valide
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
        // Fallback : retourner telle quelle (IPs locales en dev)
        return $ip;
    }

    // ---- User Agent ----

    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    // ---- AJAX ----

    public function isAjax(): bool
    {
        return ($this->header(AJAX_HEADER_NAME) === AJAX_HEADER_VALUE);
    }

    // ---- Paramètres ----

    /**
     * Paramètre GET (nettoyé).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? $this->clean($value) : $value;
    }

    /**
     * Paramètre POST (nettoyé).
     */
    public function post(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $default;
        return is_string($value) ? $this->clean($value) : $value;
    }

    /**
     * Tous les paramètres POST (nettoyés).
     * @return array<string,mixed>
     */
    public function all(): array
    {
        $data = [];
        foreach ($_POST as $key => $value) {
            $data[$key] = is_string($value) ? $this->clean($value) : $value;
        }
        return $data;
    }

    /**
     * Récupérer uniquement certains champs du POST.
     */
    public function only(string ...$keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->post($key);
        }
        return $data;
    }

    /**
     * Récupérer le corps JSON brut (requêtes AJAX avec Content-Type: application/json).
     */
    public function json(): array
    {
        if (empty($_POST)) {
            $body = file_get_contents('php://input');
            if (!empty($body)) {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }
        return $_POST;
    }

    /**
     * En-tête HTTP.
     */
    public function header(string $name): ?string
    {
        // Normaliser : X-CSRF-Token → HTTP_X_CSRF_TOKEN
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    /**
     * Fichier uploadé.
     */
    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }

    // ---- Paramètres de route ----

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function getRouteParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    // ---- Nettoyage ----

    /**
     * Nettoyage basique d'une chaîne (trim + strip tags).
     * Ne supprime PAS les caractères spéciaux — cela se fait à la validation.
     */
    private function clean(string $value): string
    {
        return trim(strip_tags($value));
    }

    // ---- Helpers ----

    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (($_SERVER['SERVER_PORT'] ?? null) == 443)
               || (($this->header('X-Forwarded-Proto') ?? '') === 'https');
    }
}
