<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Middleware;

use Nenad\Autosav\Core\Security\Class\CsrfProtection;

/**
 * AUTOSAV — Middleware CSRF
 */
class CsrfMiddleware
{
    public static function check(): void
    {
        if (!CsrfProtection::validate()) {
            logger('security')->warning('CSRF token invalide', [
                'ip'  => client_ip(),
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(403);
                echo json_encode(['success'=>false,'code'=>403,'message'=>'Token de sécurité invalide.','data'=>null,'errors'=>null]);
                exit;
            }
            http_response_code(403);
            echo '<h1>403 — Requête invalide</h1><p>Token de sécurité manquant ou expiré. Veuillez recharger la page.</p>';
            exit;
        }
    }
}
