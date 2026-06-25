<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Middleware;

use Nenad\Autosav\Modules\Connectors\Services\ConnectorService;

/**
 * Middleware optionnel pour les routes API internes.
 * La clé envoyée via Authorization: Bearer <secret> ou X-API-Key est vérifiée
 * contre sav_cles_api. Le secret clair n'est jamais stocké.
 */
class ApiKeyMiddleware
{
    public function handle(callable $next): mixed
    {
        $secret = $this->extractSecret();
        if ($secret === '') {
            $this->deny('Clé API manquante.');
        }

        $service = new ConnectorService();
        $apiKey = $service->verifierCleApi($secret);
        if (!$apiKey) {
            $this->deny('Clé API invalide, expirée ou révoquée.');
        }

        $_SERVER['AUTOSAV_API_KEY_ID'] = (string) ($apiKey['cap_id'] ?? '');
        $_SERVER['AUTOSAV_API_CONNECTOR_ID'] = (string) ($apiKey['cap_connecteur_id'] ?? '');
        $_SERVER['AUTOSAV_API_SCOPES'] = json_encode($apiKey['portees_decodees'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $next();
    }

    private function extractSecret(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (is_string($header) && preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            return trim($m[1]);
        }
        return trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
    }

    private function deny(string $message): never
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
