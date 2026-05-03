<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Dashboard\Services;

use Nenad\Autosav\Modules\Dashboard\Models\DashboardWidgetModel;

class DashboardService
{
    private DashboardWidgetModel $widgetModel;
    private string $widgetsPath;

    public function __construct()
    {
        $this->widgetModel = new DashboardWidgetModel();
        $this->widgetsPath = rtrim(DASHBOARD_WIDGETS_PATH, '/\\') . DIRECTORY_SEPARATOR;
    }

    public function getWidgetsForUser(int $userId, array $roles, array $context = []): array
    {
        $widgets = $this->widgetModel->getWidgetsForRoles($roles);
        $prefs = $this->widgetModel->getUserWidgetConfig($userId);
        $sync = [];
        $async = [];

        foreach ($widgets as $widget) {
            $id = (int) $widget['dwi_id'];
            $pref = $prefs[$id] ?? null;
            if ($pref && !(bool) $pref['udw_is_visible']) {
                continue;
            }
            $item = [
                'id' => $id,
                'code' => $widget['dwi_code'],
                'label' => $widget['dwi_label'],
                'view_file' => $widget['dwi_view_file'],
                'sort_order' => $pref ? (int) $pref['udw_sort_order'] : (int) $widget['dwi_default_order'],
                'ajax_endpoint' => $widget['dwi_ajax_endpoint'],
                'rendered_html' => null,
            ];
            if ($item['ajax_endpoint']) {
                $async[] = $item;
            } else {
                $item['rendered_html'] = $this->renderWidget($item['view_file'], $context);
                $sync[] = $item;
            }
        }

        usort($sync, fn(array $a, array $b) => $a['sort_order'] <=> $b['sort_order']);
        usort($async, fn(array $a, array $b) => $a['sort_order'] <=> $b['sort_order']);
        return ['sync' => $sync, 'async' => $async];
    }

    public function renderWidgetByCode(string $code, int $userId, array $roles, array $context = []): ?string
    {
        $widget = $this->widgetModel->getWidgetByCode($code);
        if (!$widget || !$this->isWidgetAllowed($widget, $roles)) {
            return null;
        }
        return $this->renderWidget($widget['dwi_view_file'], $context);
    }

    public function renderWidget(string $viewFile, array $context = []): string
    {
        $base = realpath($this->widgetsPath);
        $file = realpath($this->widgetsPath . basename($viewFile));
        if ($base === false || $file === false || !str_starts_with($file, $base)) {
            return '<div class="alert alert-danger">Widget indisponible.</div>';
        }

        ob_start();
        try {
            $widget_context = $context;
            include $file;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            logger('application')->error('dashboard_widget_error', ['file' => $viewFile, 'error' => $e->getMessage()]);
            return '<div class="alert alert-danger">Erreur de rendu du widget.</div>';
        }
    }

    private function isWidgetAllowed(array $widget, array $roles): bool
    {
        if (in_array(ROLE_SUPERADMIN, $roles, true)) {
            return true;
        }
        $allowed = json_decode((string) ($widget['dwi_roles_json'] ?? '[]'), true) ?: [];
        foreach ($roles as $role) {
            if (in_array($role, $allowed, true)) {
                return true;
            }
        }
        return false;
    }
}
