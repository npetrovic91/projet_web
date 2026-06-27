<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : détection silencieuse de vol de session (HIGH-4)
 *
 * Audit sécurité du 2026-06-26 : un changement de user-agent en cours de
 * session (signe possible de vol de cookie) détruisait la session sans
 * aucune trace journalisée. Corrigé : un événement "security" est loggé
 * avant destruction. md5() remplacé par hash('sha256', ...) + hash_equals()
 * (durcissement, sans changer la nature du contrôle).
 */

$root = dirname(__DIR__, 2);
$source = file_get_contents($root . '/Core/Security/Class/SessionHandler.php') ?: '';

assert($source !== '', 'SessionHandler.php introuvable.');
assert(
    !str_contains($source, "md5(\$_SERVER['HTTP_USER_AGENT']"),
    'Le binding user-agent ne doit plus utiliser md5().'
);
assert(
    str_contains($source, "hash('sha256', \$_SERVER['HTTP_USER_AGENT']"),
    'Le binding user-agent doit utiliser hash(\'sha256\', ...).'
);
assert(
    str_contains($source, 'hash_equals(') ,
    'La comparaison du hash de user-agent doit utiliser hash_equals().'
);
assert(
    str_contains($source, "logger('security')->warning(") && str_contains($source, 'vol de session possible'),
    'Un changement de user-agent détecté doit être journalisé (canal security) avant destruction de la session.'
);

echo "SessionUaHijackLoggingTest SUCCESS\n";
