<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Middleware;

/**
 * AUTOSAV — Middleware de contrôle de rôle
 */
class RoleMiddleware
{
    public static function check(string $requiredRole): void
    {
        if (!has_role($requiredRole) && !has_role(ROLE_SUPERADMIN)) {
            logger('security')->warning('Accès refusé — rôle insuffisant', [
                'required' => $requiredRole,
                'user_id'  => session('user_id'),
                'ip'       => client_ip(),
            ]);
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                http_response_code(403);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success'=>false,'code'=>403,'message'=>'Accès refusé.','data'=>null,'errors'=>null]);
                exit;
            }
            http_response_code(403);
            $view = SRC_PATH . '/Core/Theme/Views/errors/403.php';
            file_exists($view) ? include $view : print('<h1>403 — Accès refusé</h1>');
            exit;
        }
    }
}
