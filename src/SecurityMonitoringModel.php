<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Services;

class AjaxResponseService
{
    public static function send(
        bool $success,
        int $code,
        string $message,
        mixed $data = null,
        ?array $errors = null,
        ?array $meta = null
    ): never {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        echo json_encode([
            'success' => $success,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
            'meta' => $meta,
            'version' => defined('AJAX_RESPONSE_VERSION') ? AJAX_RESPONSE_VERSION : '1.0',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(string $message = 'OK', mixed $data = null, ?array $meta = null): never
    {
        self::send(true, 200, $message, $data, null, $meta);
    }

    public static function created(string $message = 'Cree avec succes.', mixed $data = null): never
    {
        self::send(true, 201, $message, $data);
    }

    public static function badRequest(string $message = 'Requete invalide.', ?array $errors = null): never
    {
        self::send(false, 400, $message, null, $errors);
    }

    public static function unauthorized(string $message = 'Session expiree. Veuillez vous reconnecter.'): never
    {
        self::send(false, 401, $message);
    }

    public static function forbidden(string $message = 'Acces refuse.'): never
    {
        self::send(false, 403, $message);
    }

    public static function notFound(string $message = 'Ressource introuvable.'): never
    {
        self::send(false, 404, $message);
    }

    public static function validationError(array $errors, string $message = 'Erreur de validation.'): never
    {
        self::send(false, 422, $message, null, $errors);
    }

    public static function serverError(string $message = 'Erreur serveur.'): never
    {
        self::send(false, 500, $message);
    }
}
