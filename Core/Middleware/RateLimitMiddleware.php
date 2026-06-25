<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Middleware;

require_once dirname(__DIR__) . '/Helpers/AuthHelper.php';

/**
 * Limitation de fréquence légère, sans dépendance base de données.
 * Utilisée notamment par les endpoints AJAX pour éviter les abus.
 */
final class RateLimitMiddleware
{
    public static function check(string $scope = 'global', ?int $maxRequests = null, ?int $windowSeconds = null): void
    {
        if (defined('AJAX_RATE_LIMIT_ENABLED') && AJAX_RATE_LIMIT_ENABLED === false) {
            return;
        }

        $max = $maxRequests ?? (defined('AJAX_RATE_LIMIT_MAX_REQUESTS') ? (int) AJAX_RATE_LIMIT_MAX_REQUESTS : 60);
        $window = $windowSeconds ?? (defined('AJAX_RATE_LIMIT_WINDOW_SECONDS') ? (int) AJAX_RATE_LIMIT_WINDOW_SECONDS : 60);
        if ($max < 1 || $window < 1) {
            return;
        }

        $ip = function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $user = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 'anon';
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = strtok((string)($_SERVER['REQUEST_URI'] ?? '/'), '?') ?: '/';
        $bucket = hash('sha256', $scope . '|' . $ip . '|' . $user . '|' . $method . '|' . $uri);

        $dir = defined('AJAX_RATE_LIMIT_BUCKET_DIR') ? (string) AJAX_RATE_LIMIT_BUCKET_DIR : sys_get_temp_dir() . '/autosav_rate_limit';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return; // ne bloque pas l'application si le cache n'est pas accessible
        }

        $file = rtrim($dir, '/\\') . '/' . $bucket . '.json';
        $now = time();
        $payload = ['start' => $now, 'count' => 0];
        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded)) {
                $payload = $decoded + $payload;
            }
        }

        if (($now - (int)($payload['start'] ?? $now)) >= $window) {
            $payload = ['start' => $now, 'count' => 0];
        }

        $payload['count'] = (int)($payload['count'] ?? 0) + 1;
        file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE), LOCK_EX);

        if ($payload['count'] > $max) {
            $retry = max(1, $window - ($now - (int)$payload['start']));
            http_response_code(429);
            header('Content-Type: application/json; charset=UTF-8');
            header('Retry-After: ' . $retry);
            echo json_encode([
                'success' => false,
                'code' => 429,
                'message' => 'Trop de requêtes. Réessayez dans quelques instants.',
                'data' => null,
                'errors' => ['retry_after_seconds' => $retry],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}
