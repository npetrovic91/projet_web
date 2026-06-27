<?php
declare(strict_types=1);

/**
 * AUTOSAV — Réception des rapports de violation CSP (CORRECTIF LOW-3)
 *
 * Audit DevOps 2026-06-26 : aucun report-uri n'était configuré, privant
 * l'équipe de toute visibilité sur des tentatives XSS bloquées par la CSP
 * en conditions réelles. Endpoint volontairement autonome (comme
 * health.php) : les navigateurs l'appellent en POST anonyme avec
 * Content-Type "application/csp-report" ou "application/json", sans
 * cookie/CSRF — un contrôleur du framework attendrait une session/un
 * jeton CSRF qui n'a pas de sens ici.
 *
 * Anti-abus : RateLimitMiddleware (scope dédié) + troncature stricte,
 * jamais de requête SQL dynamique, jamais de contenu réfléchi au client.
 */

require dirname(__DIR__) . '/bootstrap.php';

use Nenad\Autosav\Core\Middleware\RateLimitMiddleware;

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Limite large mais réelle : un navigateur peut envoyer plusieurs rapports
// rapprochés pour une même page (script-src + style-src par ex.), mais un
// flux massif est un signe d'abus (scanner, flood).
RateLimitMiddleware::check('csp-report', 30, 60);

$raw = file_get_contents('php://input') ?: '';
$decoded = json_decode($raw, true);
$report = is_array($decoded) ? ($decoded['csp-report'] ?? $decoded) : [];

if (is_array($report) && $report !== [] && function_exists('logger')) {
    logger('security')->warning('Violation CSP signalée par un navigateur.', [
        'document_uri' => mb_substr((string) ($report['document-uri'] ?? ''), 0, 500),
        'violated_directive' => mb_substr((string) ($report['violated-directive'] ?? $report['effective-directive'] ?? ''), 0, 200),
        'blocked_uri' => mb_substr((string) ($report['blocked-uri'] ?? ''), 0, 500),
        'source_file' => mb_substr((string) ($report['source-file'] ?? ''), 0, 500),
        'line_number' => $report['line-number'] ?? null,
        'ip' => function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? null),
    ]);
}

http_response_code(204);
