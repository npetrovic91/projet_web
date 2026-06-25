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
        // AUDIT 2026-06-21 — correctif point 4.4 :
        // Pour les requêtes AJAX, on utilise validateAjaxToken() qui NE régénère PAS
        // le jeton après validation. CsrfProtection::validate() régénère le jeton à
        // chaque succès : c'est correct pour un formulaire classique (la page se
        // recharge de toute façon), mais cela invaliderait le jeton détenu par le
        // JavaScript dès le deuxième appel AJAX réalisé sans rechargement complet
        // de page (ex. changement de marque dans SidebarContext._setBrand()).
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        if ($isAjax) {
            $token = (string) ($_POST[CSRF_TOKEN_NAME]
                ?? $_SERVER['HTTP_' . str_replace('-', '_', strtoupper(CSRF_HEADER_NAME))]
                ?? '');
            $valid = CsrfProtection::validateAjaxToken($token);
        } else {
            $valid = CsrfProtection::validate();
        }

        if (!$valid) {
            try {
                logger('security')->warning('CSRF token invalide', [
                    'ip'  => client_ip(),
                    'uri' => $_SERVER['REQUEST_URI'] ?? '',
                ]);
            } catch (\Throwable) {
                error_log('[AUTOSAV] CSRF token invalide.');
            }
            if ($isAjax) {
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