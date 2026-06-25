<?php
declare(strict_types=1);

use Nenad\Autosav\Core\Security\Encryption;

require_once dirname(__DIR__, 2) . '/bootstrap.php';

$key = str_repeat('A', 32);
$encryption = new Encryption($key, 'aes-256-cbc');
$plain = 'Donnée sensible AUTOSAV — test chiffrement';
$cipher = $encryption->encrypt($plain);
$decoded = $encryption->decrypt($cipher);

if ($decoded !== $plain) {
    fwrite(STDERR, "EncryptionTest NOTOK : valeur déchiffrée différente.\n");
    exit(1);
}

try {
    $encryption->decrypt(substr($cipher, 0, -4) . 'AAAA');
    fwrite(STDERR, "EncryptionTest NOTOK : payload altéré accepté.\n");
    exit(1);
} catch (Throwable) {
    // Succès attendu.
}

echo "EncryptionTest SUCCESS\n";
