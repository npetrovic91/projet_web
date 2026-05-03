<?php
declare(strict_types=1);
// ============================================================
// src/Core/Alertes/Class/SweetAlertGenerator.php
// Génération de configurations SweetAlert2 côté serveur
// Namespace : Nenad\Autosav\Core\Alertes
// ============================================================

namespace Nenad\Autosav\Core\Alertes;

class SweetAlertGenerator
{
    /** @var array<int, array> File d'attente des alertes à afficher */
    private static array $queue = [];

    // ---- Méthodes de type d'alerte ----

    public static function success(string $title, string $text = '', array $options = []): void
    {
        static::queue('success', $title, $text, $options);
    }

    public static function error(string $title, string $text = '', array $options = []): void
    {
        static::queue('error', $title, $text, $options);
    }

    public static function warning(string $title, string $text = '', array $options = []): void
    {
        static::queue('warning', $title, $text, $options);
    }

    public static function info(string $title, string $text = '', array $options = []): void
    {
        static::queue('info', $title, $text, $options);
    }

    /**
     * Alerte de confirmation (retourne la config JSON à passer à SweetAlert2).
     */
    public static function confirmConfig(
        string $title,
        string $text,
        string $confirmText  = 'Confirmer',
        string $cancelText   = 'Annuler',
        string $confirmColor = '#d33'
    ): string {
        return json_encode([
            'title'              => $title,
            'text'               => $text,
            'icon'               => 'warning',
            'showCancelButton'   => true,
            'confirmButtonColor' => $confirmColor,
            'cancelButtonColor'  => '#6c757d',
            'confirmButtonText'  => $confirmText,
            'cancelButtonText'   => $cancelText,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Ajouter une alerte en file d'attente.
     */
    private static function queue(string $icon, string $title, string $text, array $options): void
    {
        static::$queue[] = array_merge([
            'icon'  => $icon,
            'title' => $title,
            'text'  => $text,
            'timer' => match ($icon) { 'success' => 3000, 'info' => 4000, default => null },
        ], $options);
    }

    /**
     * Générer le JavaScript SweetAlert2 pour toutes les alertes en file d'attente.
     * À appeler dans le layout après l'inclusion de sweetalert2.
     */
    public static function render(): string
    {
        if (empty(static::$queue)) {
            return '';
        }
        $js = '';
        foreach (static::$queue as $alert) {
            $config = json_encode($alert, JSON_UNESCAPED_UNICODE);
            $js    .= "Swal.fire({$config});\n";
        }
        static::$queue = [];
        return "<script>\n{$js}</script>";
    }

    /**
     * Générer à partir des messages flash de session.
     * Appelé automatiquement dans le layout principal.
     */
    public static function renderFromFlash(array $flashMessages): string
    {
        if (empty($flashMessages)) {
            return '';
        }
        $flashMessages = self::normalizeFlashMessages($flashMessages);
        $alerts = [];
        foreach ($flashMessages as $flash) {
            $icon = match ($flash['type']) {
                'success' => 'success',
                'error'   => 'error',
                'warning' => 'warning',
                'info'    => 'info',
                default   => 'info',
            };
            $config = json_encode([
                'icon'  => $icon,
                'title' => (string) $flash['message'],
                'timer' => $icon === 'success' ? 3000 : null,
                'timerProgressBar' => $icon === 'success',
            ], JSON_UNESCAPED_UNICODE);
            $alerts[] = "Swal.fire({$config});";
        }
        if (empty($alerts)) { return ''; }
        return "<script>\ndocument.addEventListener('DOMContentLoaded', function() {\n  " . implode("\n  ", $alerts) . "\n});\n</script>";
    }

    private static function normalizeFlashMessages(array $flashMessages): array
    {
        $normalized = [];
        foreach ($flashMessages as $key => $value) {
            if (is_array($value) && isset($value['type'], $value['message'])) {
                $normalized[] = [
                    'type' => (string) $value['type'],
                    'message' => (string) $value['message'],
                ];
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $message) {
                    $normalized[] = [
                        'type' => is_string($key) ? $key : 'info',
                        'message' => (string) $message,
                    ];
                }
                continue;
            }

            $normalized[] = [
                'type' => is_string($key) ? $key : 'info',
                'message' => (string) $value,
            ];
        }

        return $normalized;
    }
}
