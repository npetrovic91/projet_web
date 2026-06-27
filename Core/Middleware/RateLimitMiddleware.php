<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Middleware;

require_once dirname(__DIR__) . '/Helpers/AuthHelper.php';

/**
 * Limitation de fréquence légère, sans dépendance base de données.
 * Utilisée notamment par les endpoints AJAX pour éviter les abus.
 *
 * CORRECTIF HIGH-1 (audit sécurité 2026-06-26) :
 * La version précédente lisait le bucket (file_get_contents) SANS verrou,
 * puis écrivait avec LOCK_EX. Cette fenêtre permettait à N requêtes
 * concurrentes de lire count=5 simultanément et de toutes passer la
 * limite avant qu'aucune n'écrive count=6. Corrigé : lecture ET écriture
 * sous un seul verrou exclusif (fopen + flock), l'opération est
 * désormais atomique sur le système de fichiers local.
 *
 * Pour les environnements multi-serveurs ou à très forte charge, migrer
 * vers Redis (INCR/EXPIRE) reste la solution la plus robuste à terme.
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

        // ── Lecture + écriture atomique sous verrou exclusif unique ──────────
        // fopen 'c+' : ouvre ou crée sans tronquer, curseur au début.
        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            return; // système de fichiers inaccessible, on laisse passer
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return;
        }

        try {
            $raw = stream_get_contents($fp);
            $payload = is_string($raw) ? (json_decode($raw, true) ?? []) : [];
            if (!is_array($payload)) {
                $payload = [];
            }

            if (($now - (int) ($payload['start'] ?? 0)) >= $window) {
                $payload = ['start' => $now, 'count' => 0];
            }

            $payload['count'] = (int) ($payload['count'] ?? 0) + 1;
            $count = $payload['count'];
            $windowStart = (int) ($payload['start'] ?? $now);

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) json_encode($payload, JSON_UNESCAPED_UNICODE));
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        if ($count > $max) {
            $retry = max(1, $window - ($now - $windowStart));
            http_response_code(429);
            header('Content-Type: application/json; charset=UTF-8');
            header('Retry-After: ' . $retry);
            header('X-RateLimit-Limit: ' . $max);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . ($now + $retry));
            echo json_encode([
                'success' => false,
                'code' => 429,
                'message' => 'Trop de requêtes. Réessayez dans quelques instants.',
                'data' => null,
                'errors' => ['retry_after_seconds' => $retry],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('X-RateLimit-Limit: ' . $max);
        header('X-RateLimit-Remaining: ' . max(0, $max - $count));
    }

    /**
     * Nettoie les buckets expirés du dossier de rate limiting (CORRECTIF
     * MED-5 : sans ça, les fichiers de bucket s'accumulent indéfiniment
     * sur le disque, jamais nettoyés après leur fenêtre temporelle).
     * À appeler depuis bin/run_maintenance.php.
     */
    public static function purgeExpiredBuckets(int $olderThanSeconds = 3600): int
    {
        $dir = defined('AJAX_RATE_LIMIT_BUCKET_DIR')
            ? (string) AJAX_RATE_LIMIT_BUCKET_DIR
            : sys_get_temp_dir() . '/autosav_rate_limit';

        if (!is_dir($dir)) {
            return 0;
        }

        $deleted = 0;
        $limit = time() - max(60, $olderThanSeconds);
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $limit) {
                @unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
