<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Middleware;

/**
 * AUTOSAV — Middleware AJAX
 * Vérifie que la requête est bien une requête AJAX (X-Requested-With).
 */
class AjaxMiddleware
{
    public static function check(): void
    {
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== AJAX_HEADER_VALUE) {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success'=>false,'code'=>400,'message'=>'Requête invalide.','data'=>null,'errors'=>null]);
            exit;
        }
    }
}
