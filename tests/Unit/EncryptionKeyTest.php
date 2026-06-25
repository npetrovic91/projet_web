<?php
declare(strict_types=1);

/**
 * AUTOSAV — Test de la clé de chiffrement
 *
 * Vérifie :
 *  - ENCRYPTION_KEY est définie
 *  - Elle n'est pas la valeur par défaut connue ('autosav-dev-only-key')
 *  - Elle est de longueur suffisante (32 octets minimum après hash)
 *  - ENCRYPTION_CIPHER est défini et valide
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$pass = 0;
$fail = 0;

function ok(bool $cond, string $label): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "[OK]   {$label}\n";
    } else {
        $fail++;
        echo "[FAIL] {$label}\n";
    }
}

ok(defined('ENCRYPTION_KEY'), 'ENCRYPTION_KEY est définie');
ok(defined('ENCRYPTION_CIPHER'), 'ENCRYPTION_CIPHER est définie');

if (defined('ENCRYPTION_KEY')) {
    $key = ENCRYPTION_KEY;
    ok(strlen($key) >= 32, 'ENCRYPTION_KEY fait au minimum 32 octets');
    // Valeur dérivée de 'autosav-dev-only-key' — ne doit jamais apparaître en prod
    $devKeyHash = hash('sha256', 'autosav-dev-only-key', true);
    ok(
        !defined('APP_ENV') || APP_ENV !== 'production' || $key !== $devKeyHash,
        'ENCRYPTION_KEY n\'est pas la clé de développement par défaut en production'
    );
}

if (defined('ENCRYPTION_CIPHER')) {
    ok(
        in_array(ENCRYPTION_CIPHER, openssl_get_cipher_methods(), true),
        'ENCRYPTION_CIPHER est un algorithme OpenSSL valide : ' . ENCRYPTION_CIPHER
    );
}

echo "\n--- Résultat : {$pass} OK / " . ($pass + $fail) . " total ---\n";
exit($fail > 0 ? 1 : 0);
