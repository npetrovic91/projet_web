<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Helpers;

/**
 * AUTOSAV — Gestion des messages flash (one-time display)
 */
class FlashHelper
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
    }

    public static function has(string $type = null): bool
    {
        if ($type) return !empty($_SESSION['flash'][$type]);
        return !empty($_SESSION['flash']);
    }

    public static function get(string $type): array
    {
        $messages = $_SESSION['flash'][$type] ?? [];
        unset($_SESSION['flash'][$type]);
        return $messages;
    }

    public static function getAll(): array
    {
        $all = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $all;
    }

    /**
     * Génère le HTML des messages flash (AdminLTE alerts).
     */
    public static function render(): string
    {
        $all  = self::getAll();
        if (empty($all)) return '';

        $html = '';
        $map  = [
            'success' => 'success',
            'error'   => 'danger',
            'warning' => 'warning',
            'info'    => 'info',
        ];

        foreach ($all as $type => $messages) {
            $bsType = $map[$type] ?? 'secondary';
            foreach ($messages as $msg) {
                $html .= '<div class="alert alert-' . $bsType . ' alert-dismissible fade show" role="alert">';
                $html .= e($msg);
                $html .= '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>';
                $html .= '</div>';
            }
        }
        return $html;
    }
}
