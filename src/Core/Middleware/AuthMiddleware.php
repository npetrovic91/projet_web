<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Middleware;

/**
 * AUTOSAV — Middleware d'authentification
 * Vérifie que l'utilisateur est bien connecté.
 */
class AuthMiddleware
{
    public static function check(): void
    {
        if (!is_authenticated()) {
            // Sauvegarder l'URL demandée pour redirect post-login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';

            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(401);
                echo json_encode([
                    'success' => false, 'code' => 401,
                    'message' => 'Session expirée. Veuillez vous reconnecter.',
                    'data' => null, 'errors' => null,
                ]);
                exit;
            }
            header('Location: ' . url('/auth/login'), true, 302);
            exit;
        }
    }
}
