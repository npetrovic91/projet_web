<?php
declare(strict_types=1);
// ============================================================
// AUTOSAV - Chiffrement/dechiffrement symetrique
// Namespace : Nenad\Autosav\Core\Security
// ============================================================

namespace Nenad\Autosav\Core\Security;

final class Encryption
{
    private string $key;
    private string $cipher;

    public function __construct(?string $key = null, ?string $cipher = null)
    {
        $this->cipher = $cipher ?: (defined('ENCRYPTION_CIPHER') ? (string) ENCRYPTION_CIPHER : 'aes-256-cbc');
        $this->key = $key ?: (defined('ENCRYPTION_KEY') ? (string) ENCRYPTION_KEY : '');
        $this->assertConfigurationValide();
    }

    public function encrypt(string $value): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        if ($ivLength === false || $ivLength <= 0) {
            throw new \RuntimeException('Longueur IV invalide pour le chiffrement configuré.');
        }

        $iv = random_bytes($ivLength);
        $encrypted = openssl_encrypt($value, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            throw new \RuntimeException('Échec du chiffrement.');
        }

        $mac = hash_hmac('sha256', $iv . $encrypted, $this->key, true);
        return base64_encode($mac . $iv . $encrypted);
    }

    public function decrypt(string $payload): string
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new \RuntimeException('Payload de déchiffrement invalide.');
        }

        $macLength = 32;
        $ivLength = openssl_cipher_iv_length($this->cipher);
        if ($ivLength === false || $ivLength <= 0 || strlen($decoded) <= ($macLength + $ivLength)) {
            throw new \RuntimeException('Payload de déchiffrement incomplet.');
        }

        $mac = substr($decoded, 0, $macLength);
        $iv = substr($decoded, $macLength, $ivLength);
        $ciphertext = substr($decoded, $macLength + $ivLength);

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

    private function assertConfigurationValide(): void
    {
        if ($this->cipher === '' || !in_array($this->cipher, openssl_get_cipher_methods(), true)) {
            throw new \RuntimeException('Algorithme de chiffrement invalide : ' . $this->cipher);
        }
        if (strlen($this->key) < 32) {
            throw new \RuntimeException('ENCRYPTION_KEY doit contenir au minimum 32 octets.');
        }
    }
}
