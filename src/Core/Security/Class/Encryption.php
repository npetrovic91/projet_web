<?php
declare(strict_types=1);
// ============================================================
// src/Core/Security/Class/Encryption.php
// Chiffrement/déchiffrement symétrique AES-256-CBC
// Namespace : Nenad\Autosav\Core\Security
// ============================================================

namespace Nenad\Autosav\Core\Security;

class Encryption
{
    private string $key;
    private string $cipher;

    public function __construct()
    {
        $this->key    = ENCRYPTION_KEY;
        $this->cipher = ENCRYPTION_CIPHER;
    }

    /**
     * Chiffrer une valeur.
     * Retourne : base64(IV + ':' + ciphertext)
     */
    public function encrypt(string $value): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv       = random_bytes($ivLength);
        $encrypted = openssl_encrypt($value, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            throw new \RuntimeException('Échec du chiffrement.');
        }
        $mac     = hash_hmac('sha256', $iv . $encrypted, $this->key, true);
        return base64_encode($mac . $iv . $encrypted);
    }

    /**
     * Déchiffrer une valeur.
     */
    public function decrypt(string $payload): string
    {
        $decoded  = base64_decode($payload, true);
        if ($decoded === false) {
            throw new \RuntimeException('Payload de déchiffrement invalide.');
        }
        $macLength = 32; // SHA-256 = 32 bytes
        $ivLength  = openssl_cipher_iv_length($this->cipher);

        $mac       = substr($decoded, 0, $macLength);
        $iv        = substr($decoded, $macLength, $ivLength);
        $ciphertext = substr($decoded, $macLength + $ivLength);

        // Vérification HMAC
        $expectedMac = hash_hmac('sha256', $iv . $ciphertext, $this->key, true);
        if (!hash_equals($expectedMac, $mac)) {
            throw new \RuntimeException('HMAC invalide — payload altéré.');
        }

        $decrypted = openssl_decrypt($ciphertext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            throw new \RuntimeException('Échec du déchiffrement.');
        }
        return $decrypted;
    }
}
