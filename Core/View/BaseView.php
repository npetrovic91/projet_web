<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\View;

use Nenad\Autosav\Core\Security\Class\CspNonce;

/**
 * AUTOSAV — Moteur de rendu central
 * Fichier : src/Core/View/BaseView.php
 *
 * Layouts disponibles :
 *   'main'    — AdminLTE complet (pages authentifiées)
 *   'public'  — Sans sidebar (pages publiques)
 *   'minimal' — Wrapper centré (maintenance, erreurs)
 *   'none'    — Aucun layout (login, forgot-password)
 */
final class BaseView
{
    public static function render(
        string $viewPath,
        array  $data   = [],
        string $layout = 'main'
    ): void {
        CspNonce::sendHeaders();
        $viewFile = self::resolveViewPath($viewPath);

        if (!file_exists($viewFile)) {
            self::renderError(404, 'Vue introuvable', "Fichier manquant : {$viewFile}");
            return;
        }

        $content = self::captureView($viewFile, $data);

        match ($layout) {
            'none'    => self::renderNone($content),
            'minimal' => self::renderMinimal($content, $data),
            'public'  => self::renderPublic($content, $data),
            default   => self::renderMain($content, $data),
        };
    }

    // ----------------------------------------------------------------
    // RÉSOLUTION DU CHEMIN
    // ----------------------------------------------------------------

    private static function resolveViewPath(string $viewPath): string
    {
        $viewPath = ltrim($viewPath, '/');
        $viewPath = str_replace(['../', '..\\'], '', $viewPath);

        $candidates = [
            SRC_PATH . '/Modules/' . $viewPath . '.php',
            SRC_PATH . '/' . $viewPath . '.php',
        ];

        // Compatibilite modules : certains controleurs historiques appellent
        // $this->render('Module/vue') alors que les vues sont stockees dans
        // Modules/Module/Views/vue.php. Cette resolution centrale evite de
        // dupliquer les chemins dans chaque controleur.
        if (!str_contains($viewPath, '/Views/')) {
            $segments = explode('/', $viewPath, 2);
            if (count($segments) === 2 && $segments[0] !== '' && $segments[1] !== '') {
                $candidates[] = SRC_PATH . '/Modules/' . $segments[0] . '/Views/' . $segments[1] . '.php';
            }
        }

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return SRC_PATH . '/Modules/' . $viewPath . '.php';
    }

    // ----------------------------------------------------------------
    // CAPTURE DU RENDU
    // ----------------------------------------------------------------

    private static function captureView(string $viewFile, array $data): string
    {
        $safeData = array_filter(
            $data,
            fn($key) => is_string($key) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key),
            ARRAY_FILTER_USE_KEY
        );

        $safeData['e'] = static function (mixed $value, bool $doubleEncode = true): string {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
        };
        $safeData['esc'] = $safeData['e'];
        $safeData['h'] = $safeData['e'];
        $safeData['csrfField'] = function_exists('csrf_field') ? csrf_field() : '';
        $safeData['cspNonce'] = CspNonce::value();
        $safeData['nonce'] = $safeData['cspNonce'];
        $safeData['nonceAttr'] = CspNonce::attribute();

        $safeData = ViewDataNormalizer::normalize($safeData);

        extract($safeData, EXTR_SKIP);

        ob_start();
        try {
            include $viewFile;
        } catch (\Throwable $e) {
            ob_end_clean();
            error_log(sprintf(
                '[AUTOSAV][VIEW] %s in %s:%d (view: %s)',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $viewFile
            ));
            self::renderError(500, 'Erreur dans la vue', $e->getMessage());
            exit;
        }

        return ob_get_clean() ?: '';
    }

    // ----------------------------------------------------------------
    // LAYOUTS
    // ----------------------------------------------------------------

    private static function renderNone(string $content): void
    {
        echo $content;
    }

    private static function renderMinimal(string $content, array $data): void
    {
        $appName   = defined('APP_NAME') ? APP_NAME : 'Autosav';
        $title     = htmlspecialchars((string)($data['pageTitle'] ?? $appName), ENT_QUOTES, 'UTF-8');
        $vendorUrl = defined('VENDOR_URL') ? VENDOR_URL : '/assets/vendor';
        $nonceAttr = CspNonce::attribute();

        echo "<!DOCTYPE html>\n<html lang=\"fr\">\n<head>\n";
        echo "<meta charset=\"UTF-8\">\n";
        echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        echo "<title>{$title}</title>\n";
        echo "<link rel=\"stylesheet\" href=\"{$vendorUrl}/adminlte/css/adminlte.min.css\">\n";
        echo "<link rel=\"stylesheet\" href=\"{$vendorUrl}/adminlte/plugins/fontawesome-free/css/all.min.css\">\n";
        echo "</head>\n";
        echo "<body class=\"hold-transition\" style=\"background:#f4f6f9;display:flex;align-items:center;justify-content:center;min-height:100vh\">\n";
        echo "<div style=\"width:100%;max-width:600px;padding:1rem\">\n";
        echo $content;
        echo "\n</div>\n</body>\n</html>";
    }

    private static function renderPublic(string $content, array $data): void
    {
        $appName   = defined('APP_NAME') ? APP_NAME : 'Autosav';
        $title     = htmlspecialchars((string)($data['pageTitle'] ?? $appName), ENT_QUOTES, 'UTF-8');
        $vendorUrl = defined('VENDOR_URL') ? VENDOR_URL : '/assets/vendor';
        $nonceAttr = CspNonce::attribute();

        echo "<!DOCTYPE html>\n<html lang=\"fr\">\n<head>\n";
        echo "<meta charset=\"UTF-8\">\n";
        echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        echo "<title>{$title}</title>\n";
        echo "<link rel=\"stylesheet\" href=\"{$vendorUrl}/adminlte/css/adminlte.min.css\">\n";
        echo "<link rel=\"stylesheet\" href=\"{$vendorUrl}/adminlte/plugins/fontawesome-free/css/all.min.css\">\n";
        echo "</head>\n<body class=\"hold-transition\">\n<div class=\"wrapper\">\n";
        echo $content;
        echo "\n</div>\n";
        echo "<script{$nonceAttr} src=\"{$vendorUrl}/adminlte/plugins/jquery/jquery.min.js\"></script>\n";
        echo "<script{$nonceAttr} src=\"{$vendorUrl}/adminlte/js/adminlte.min.js\"></script>\n";
        echo "</body>\n</html>";
    }

    private static function renderMain(string $content, array $data): void
    {
        $vueClass = 'Nenad\\Autosav\\Core\\Theme\\Vue';

        if (class_exists($vueClass)) {
            $appName    = defined('APP_NAME') ? APP_NAME : 'Autosav';
            $title      = (string)($data['pageTitle'] ?? $appName);
            $breadcrumb = (array)($data['breadcrumb'] ?? []);
            $vue        = new $vueClass($title, $content, $data, $breadcrumb);
            $vue->render();
            return;
        }

        // Fallback AdminLTE minimal
        $appName   = defined('APP_NAME') ? APP_NAME : 'Autosav';
        $title     = htmlspecialchars((string)($data['pageTitle'] ?? $appName), ENT_QUOTES, 'UTF-8');
        $vendorUrl = defined('VENDOR_URL') ? VENDOR_URL : '/assets/vendor';
        $nonceAttr = CspNonce::attribute();

        echo "<!DOCTYPE html>\n<html lang=\"fr\">\n<head>\n";
        echo "<meta charset=\"UTF-8\">\n";
        echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        echo "<title>{$title}</title>\n";
        echo "<link rel=\"stylesheet\" href=\"{$vendorUrl}/adminlte/css/adminlte.min.css\">\n";
        echo "<link rel=\"stylesheet\" href=\"{$vendorUrl}/adminlte/plugins/fontawesome-free/css/all.min.css\">\n";
        echo "</head>\n";
        echo "<body class=\"hold-transition sidebar-mini layout-fixed\">\n";
        echo "<div class=\"wrapper\">\n";
        echo "<nav class=\"main-header navbar navbar-expand navbar-white navbar-light\">";
        echo "<ul class=\"navbar-nav\"><li class=\"nav-item\"><a class=\"nav-link\" data-widget=\"pushmenu\" href=\"#\"><i class=\"fas fa-bars\"></i></a></li></ul>";
        echo "</nav>\n";
        echo "<aside class=\"main-sidebar sidebar-dark-primary elevation-4\">";
        echo "<a href=\"/\" class=\"brand-link\"><span class=\"brand-text font-weight-light\">{$appName}</span></a>";
        echo "<div class=\"sidebar\"><nav class=\"mt-2\"><ul class=\"nav nav-pills nav-sidebar flex-column\" data-widget=\"treeview\"></ul></nav></div>";
        echo "</aside>\n";
        echo "<div class=\"content-wrapper\"><div class=\"content\"><div class=\"container-fluid pt-3\">";
        echo $content;
        echo "</div></div></div>\n";
        echo "<footer class=\"main-footer\"><strong>{$appName}</strong></footer>\n";
        echo "</div>\n";
        echo "<script{$nonceAttr} src=\"{$vendorUrl}/adminlte/plugins/jquery/jquery.min.js\"></script>\n";
        echo "<script{$nonceAttr} src=\"{$vendorUrl}/adminlte/js/adminlte.min.js\"></script>\n";
        echo "</body>\n</html>";
    }

    // ----------------------------------------------------------------
    // PAGE D'ERREUR
    // ----------------------------------------------------------------

    public static function renderError(int $code, string $title, string $message = ''): void
    {
        http_response_code($code);

        $errorView = defined('SRC_PATH')
            ? SRC_PATH . '/Core/Theme/Views/errors/' . $code . '.php'
            : '';

        if ($errorView !== '' && file_exists($errorView)) {
            include $errorView;
            return;
        }

        $nonceAttr = CspNonce::attribute();
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $showDebug = defined('APP_DEBUG') && APP_DEBUG && $message !== '';
        $safeMsg   = $showDebug ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : '';
        $msgBlock  = $showDebug ? "<p><code>{$safeMsg}</code></p>" : '';

        echo "<!DOCTYPE html><html lang=\"fr\"><head><meta charset=\"UTF-8\">";
        echo "<title>{$code} — {$safeTitle}</title>";
        echo "<style{$nonceAttr}>body{font-family:sans-serif;background:#f4f6f9;display:flex;align-items:center;";
        echo "justify-content:center;min-height:100vh;margin:0}";
        echo ".box{background:#fff;padding:3rem;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);text-align:center;max-width:500px}";
        echo "h1{color:#e74c3c;font-size:4rem;margin:0}h2{color:#333}p{color:#666;font-size:.9rem}</style>";
        echo "</head><body><div class=\"box\">";
        echo "<h1>{$code}</h1><h2>{$safeTitle}</h2>{$msgBlock}";
        echo "<a href=\"/\" style=\"color:#3498db\">&larr; Retour &agrave; l'accueil</a>";
        echo "</div></body></html>";
    }
}
