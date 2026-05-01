<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security;

use Nenad\Autosav\Core\Security\Class\CsrfProtection;
use Nenad\Autosav\Core\Security\Class\PasswordManager;
use Nenad\Autosav\Core\Security\Class\InputValidator;
use Nenad\Autosav\Core\Security\Class\XssProtection;
use Nenad\Autosav\Core\Security\Class\SessionHandler;

/**
 * AUTOSAV — Façade sécurité
 * Fichier : src/Core/Security/SecurityManager.php
 * Rôle    : Point d'accès centralisé aux utilitaires de sécurité.
 */
class SecurityManager
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        self::$instance ??= new self();
        return self::$instance;
    }

    public function csrf(): CsrfProtection  { return new CsrfProtection(); }
    public function password(): PasswordManager { return new PasswordManager(); }
    public function validator(): InputValidator  { return new InputValidator(); }
    public function xss(): XssProtection    { return new XssProtection(); }
    public function session(): SessionHandler { return new SessionHandler(); }

    public function verifyPassword(string $password, string $hash): bool
    {
        return PasswordManager::verify($password, $hash);
    }

    public function hashPassword(string $password): string
    {
        return PasswordManager::hash($password);
    }

    public function validatePasswordStrength(string $password): array
    {
        $errors = PasswordManager::validate($password);
        return ['valid' => $errors === [], 'errors' => $errors];
    }

    public function regenerateSession(): void
    {
        SessionHandler::regenerate();
    }

    public function destroySession(): void
    {
        SessionHandler::destroy();
    }

    private function __clone() {}
}
