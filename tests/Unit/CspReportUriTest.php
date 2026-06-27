<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : endpoint de rapport CSP (LOW-3)
 *
 * Audit DevOps 2026-06-26 : aucun report-uri n'était configuré, privant
 * l'équipe de toute visibilité sur des violations CSP réelles (tentatives
 * XSS bloquées). public/csp-report.php reçoit ces rapports anonymes et les
 * journalise (canal security), avec limitation de débit anti-abus.
 */

$root = dirname(__DIR__, 2);

$security = file_get_contents($root . '/config/security.php') ?: '';
assert($security !== '', 'config/security.php introuvable.');
assert(
    str_contains($security, 'report-uri /csp-report.php'),
    'CSP_POLICY doit déclarer report-uri vers /csp-report.php.'
);

$endpoint = file_get_contents($root . '/public/csp-report.php') ?: '';
assert($endpoint !== '', 'public/csp-report.php introuvable.');
assert(
    str_contains($endpoint, "RateLimitMiddleware::check('csp-report'"),
    'csp-report.php doit être protégé par une limite de débit dédiée (anti-flood).'
);
assert(
    str_contains($endpoint, "logger('security')->warning("),
    'csp-report.php doit journaliser les violations sur le canal security.'
);
assert(
    str_contains($endpoint, "REQUEST_METHOD'] ?? '') !== 'POST'"),
    'csp-report.php ne doit accepter que des requêtes POST (format envoyé par les navigateurs).'
);

echo "CspReportUriTest SUCCESS\n";
