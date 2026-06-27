<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Error;

use Nenad\Autosav\Core\View\BaseView;
use Throwable;

/**
 * AUTOSAV — Gestionnaire global des erreurs/exceptions non interceptées
 *
 * CORRECTIF 2.3 (convergence gestion d'erreurs) : avant ce fichier, aucun
 * gestionnaire global n'était enregistré. Une exception levée par un
 * Service (VerrouEntiteService, TcpdfReportService, GroupPropagationService,
 * ContexteActifService...) qui remontait sans être interceptée par le
 * contrôleur appelant produisait soit une page blanche, soit — si
 * APP_DEBUG est mal configuré — une fuite de stack trace en production.
 *
 * Ce filet de sécurité garantit que TOUT throwable non intercepté, quel
 * que soit le Service qui l'a levé, suit le même chemin : journalisation
 * complète côté serveur (jamais perdue), page générique côté client
 * (jamais de détail technique en production).
 */
final class GlobalErrorHandler
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        set_exception_handler(static function (Throwable $e): void {
            self::handle($e);
        });

        // Volontairement PAS de set_error_handler() ici : promouvoir chaque
        // warning/notice PHP existant en exception ferait planter des pages
        // qui fonctionnent aujourd'hui malgré un warning bénin (clé de
        // tableau absente, etc.) — bien au-delà du périmètre "convergence
        // des erreurs non interceptées". Seuls les uncaught Throwable et les
        // erreurs fatales irrécupérables (ci-dessous) sont concernés.
        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::handle(new \ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                ));
            }
        });
    }

    private static function handle(Throwable $e): void
    {
        try {
            if (function_exists('logger')) {
                logger('application')->critical('Exception non interceptée : ' . $e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        } catch (Throwable) {
            // Le logger lui-même ne doit jamais empêcher l'affichage de la page d'erreur.
            error_log('[critical] Exception non interceptée : ' . $e->getMessage());
        }

        if (headers_sent()) {
            return;
        }

        $isAjax = (
            ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== '' && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || str_starts_with((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || str_starts_with((string) ($_SERVER['REQUEST_URI'] ?? ''), '/ajax/');

        $debug = defined('APP_DEBUG') && APP_DEBUG;

        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'code' => 500,
                'message' => 'Une erreur interne est survenue.',
                'data' => null,
                'errors' => $debug ? ['exception' => $e->getMessage()] : [],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        BaseView::renderError(500, 'Erreur interne', $debug ? $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine() : '');
    }
}
