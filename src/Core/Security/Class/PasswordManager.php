<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security\Class;

class PasswordManager
{
    public static function hash(string $password): string
    {
        return password_hash($password, HASH_ALGO, HASH_ALGO_OPTIONS);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, HASH_ALGO, HASH_ALGO_OPTIONS);
    }

    public static function validate(string $password): array
    {
        $errors = [];
        $length = mb_strlen($password);

        if ($length < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Le mot de passe doit contenir au minimum ' . PASSWORD_MIN_LENGTH . ' caracteres.';
        }
        if ($length > PASSWORD_MAX_LENGTH) {
            $errors[] = 'Le mot de passe ne doit pas depasser ' . PASSWORD_MAX_LENGTH . ' caracteres.';
        }
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
        }
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
        }
        if (PASSWORD_REQUIRE_NUMBER && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }
        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractere special.';
        }

        return $errors;
    }

    public static function generateToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
