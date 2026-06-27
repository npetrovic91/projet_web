<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : détection de placeholder HEALTHCHECK_TOKEN (HIGH-3)
 *
 * Audit DevOps du 2026-06-26 : .env.production.template introduit le texte
 * de placeholder "REMPLACER_TOKEN_HEALTHCHECK_64_CHARS". Sans extension du
 * filtre de détection (qui ne couvrait que changer_/change_/generer_/
 * CHANGE_ME...), un oubli de remplacement de CE placeholder précis ne
 * serait jamais bloqué par PRODUCTION_HEALTH_TOKEN_IS_PLACEHOLDER ni par
 * check-production.php — un secret public resterait actif sans alerte.
 */

$root = dirname(__DIR__, 2);

$production = file_get_contents($root . '/config/production.php') ?: '';
assert($production !== '', 'config/production.php introuvable.');
assert(
    str_contains($production, 'remplacer'),
    'PRODUCTION_HEALTH_TOKEN_IS_PLACEHOLDER doit reconnaître le préfixe "remplacer" utilisé par .env.production.template.'
);
assert(
    str_contains($production, "preg_match('/^[0-9a-f]{64}\$/i'"),
    'PRODUCTION_HEALTH_TOKEN_IS_PLACEHOLDER doit exiger le format réel (64 caractères hexadécimaux), pas seulement l\'absence de préfixes connus.'
);

$checkProduction = file_get_contents($root . '/scripts/check-production.php') ?: '';
assert($checkProduction !== '', 'scripts/check-production.php introuvable.');
assert(
    str_contains($checkProduction, 'remplacer'),
    'check-production.php doit aussi reconnaître le préfixe "remplacer" pour ENCRYPTION_KEY.'
);

$template = file_get_contents($root . '/.env.production.template') ?: '';
assert($template !== '', '.env.production.template introuvable.');
assert(
    str_contains($template, 'REMPLACER'),
    '.env.production.template doit utiliser des placeholders explicites (REMPLACER_...) pour les secrets.'
);

echo "HealthTokenPlaceholderHardeningTest SUCCESS\n";
