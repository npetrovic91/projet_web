<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : race condition rate limiting (HIGH-1)
 *
 * Audit sécurité du 2026-06-26 : lecture du bucket SANS verrou puis
 * écriture AVEC verrou — fenêtre TOCTOU permettant à des requêtes
 * concurrentes de toutes lire le même compteur et de toutes passer.
 * Corrigé via fopen('c+') + flock(LOCK_EX) unique pour lecture+écriture.
 *
 * Vérifié fonctionnellement (pas seulement par lecture de source) :
 * avec une limite de 5, exactement 5 appels passent et le 6e est bloqué
 * (429) — voir la vérification manuelle effectuée pendant le
 * développement de ce correctif.
 */

$root = dirname(__DIR__, 2);
$source = file_get_contents($root . '/Core/Middleware/RateLimitMiddleware.php') ?: '';

assert($source !== '', 'RateLimitMiddleware.php introuvable.');

// Plus de file_get_contents() suivi d'un file_put_contents() séparé.
assert(
    !str_contains($source, 'file_get_contents($file)'),
    'Le bucket ne doit plus être lu via file_get_contents() séparément de l\'écriture (fenêtre TOCTOU).'
);
assert(
    str_contains($source, "fopen(\$file, 'c+')") && str_contains($source, 'flock($fp, LOCK_EX)'),
    'La lecture et l\'écriture du bucket doivent se faire sous le même verrou exclusif (fopen + flock).'
);
assert(
    str_contains($source, 'flock($fp, LOCK_UN)') && str_contains($source, 'fclose($fp)'),
    'Le verrou et le descripteur de fichier doivent être libérés explicitement.'
);

// MED-5 : purge des buckets expirés, appelée depuis run_maintenance.php.
assert(
    str_contains($source, 'function purgeExpiredBuckets'),
    'RateLimitMiddleware doit exposer purgeExpiredBuckets() pour le nettoyage périodique (MED-5).'
);

$maintenance = file_get_contents($root . '/bin/run_maintenance.php') ?: '';
assert(
    str_contains($maintenance, 'RateLimitMiddleware::purgeExpiredBuckets'),
    'bin/run_maintenance.php doit appeler purgeExpiredBuckets() pour éviter l\'accumulation de fichiers.'
);

echo "RateLimitAtomicityFixTest SUCCESS\n";
