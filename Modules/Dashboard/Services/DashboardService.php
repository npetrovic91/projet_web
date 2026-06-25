<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Dashboard\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Core\Security\Class\RoleResolver;
use Nenad\Autosav\Modules\Dashboard\Models\DashboardStatsModel;
use Nenad\Autosav\Modules\Dashboard\Models\DashboardWidgetModel;
use Nenad\Autosav\Modules\Users\Models\UserModel;

class DashboardService implements ServiceInterface{
    private DashboardWidgetModel $widgetModel;
    private DashboardStatsModel $statsModel;
    private string $widgetsPath;

    public function __construct()
    {
        $this->widgetModel = new DashboardWidgetModel();
        $this->statsModel = new DashboardStatsModel();
        $this->widgetsPath = rtrim(DASHBOARD_WIDGETS_PATH, '/\\') . DIRECTORY_SEPARATOR;
    }

    public function getWidgetsForUser(int $userId, array $roles, array $context = []): array
    {
        // Si le contexte a déjà été enrichi par withDashboardData() dans le contrôleur,
        // on l'utilise directement pour éviter de doubler toutes les requêtes BDD.
        if (!isset($context['dashboard_stats'])) {
            $context = $this->withDashboardData($userId, $roles, $context);
        }
        $widgets = $this->widgetModel->getWidgetsForRoles($roles);
        $prefs = $this->widgetModel->getUserWidgetConfig($userId);
        $sync = [];
        $async = [];

        foreach ($widgets as $widget) {
            $id = (int) $widget['dwi_id'];
            $pref = $prefs[$id] ?? null;
            if ($pref && !(bool) ($pref['udw_is_visible'] ?? true)) {
                continue;
            }
            $item = [
                'id' => $id,
                'code' => $widget['dwi_code'],
                'label' => $widget['dwi_label'],
                'view_file' => $widget['dwi_view_file'],
                'sort_order' => $pref ? (int) ($pref['udw_sort_order'] ?? $widget['dwi_default_order']) : (int) $widget['dwi_default_order'],
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
        return ['sync' => $sync, 'async' => $async, 'context' => $context];
    }

    public function renderWidgetByCode(string $code, int $userId, array $roles, array $context = []): ?string
    {
        $widget = $this->widgetModel->getWidgetByCode($code);
        if (!$widget || !$this->isWidgetAllowed($widget, $roles)) {
            return null;
        }
        return $this->renderWidget($widget['dwi_view_file'], $this->withDashboardData($userId, $roles, $context));
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
            if (function_exists('logger')) {
                logger('application')->error('dashboard_widget_error', ['file' => $viewFile, 'error' => $e->getMessage()]);
            }
            return '<div class="alert alert-danger">Erreur de rendu du widget.</div>';
        }
    }

    public function withDashboardData(int $userId, array $roles, array $context = []): array
    {
        $companyId = !empty($context['active_company_id']) ? (int) $context['active_company_id'] : null;
        $globalScope = $this->isSuperAdmin($roles);

        $stats = $this->statsModel->snapshot($companyId, $globalScope);
        $stats['notifications_unread'] = count($this->statsModel->unreadNotificationsForUser($userId, $companyId, 20));
        if (!$globalScope) {
            $stats['failed_logins_24h'] = 0;
            $stats['security_blocks_active'] = 0;
            $stats['gdpr_pending'] = 0;
            $stats['maintenance_active'] = false;
        }

        $context['user_id'] = $userId;
        $context['user_roles'] = $roles;
        $context['global_scope'] = $globalScope;
        $context['dashboard_sort_rule'] = 'utilisateur > société_appartenance > niveaux';
        $context['dashboard_stats'] = $stats;
        $context['user_company_levels'] = $this->statsModel->usersByCompanyAndLevels($companyId, $globalScope, 50);
        $context['company_level_summary'] = $this->statsModel->companyLevelSummary($companyId, $globalScope);
        $context['recent_security_attempts'] = $globalScope ? $this->statsModel->recentSecurityAttempts(8) : [];
        $context['unread_notifications'] = $this->statsModel->unreadNotificationsForUser($userId, $companyId, 5);

        // B-02 : données profil pour widget_profil.php (évite db() direct dans la vue)
        if ($userId > 0 && !isset($context['profil_user'])) {
            try {
                $context['profil_user'] = (new UserModel())->trouver($userId);
            } catch (\Throwable) {
                $context['profil_user'] = null;
            }
        }

        // ── Profils par type de société ───────────────────────────────────
        // Les codes de type de la société active sont injectés dans le contexte
        // sous la forme 'type_constructeur', 'type_importateur', 'type_marque', etc.
        // Le DashboardController les fusionne ensuite avec $roles pour filtrer
        // les widgets qui leur correspondent dans le catalogue.
        $typeCodes = $companyId ? $this->statsModel->getCompanyTypeCodes($companyId) : [];
        $context['active_company_type_codes'] = $typeCodes;

        if (in_array('constructeur', $typeCodes, true) && $companyId) {
            $context['constructeur_stats'] = $this->statsModel->constructeurStats($companyId);
        }
        if (in_array('importateur', $typeCodes, true) && $companyId) {
            $context['importateur_stats'] = $this->statsModel->importateurStats($companyId);
        }
        if (in_array('marque', $typeCodes, true) && $companyId) {
            $context['marque_stats'] = $this->statsModel->marqueStats($companyId);
        }

        return $context;
    }

    private function isWidgetAllowed(array $widget, array $roles): bool
    {
        if ($this->isSuperAdmin($roles)) {
            return true;
        }
        $allowed = json_decode((string) ($widget['dwi_roles_json'] ?? '[]'), true) ?: [];
        if (in_array('*', $allowed, true)) {
            return true;
        }
        foreach ($roles as $role) {
            if (in_array($role, $allowed, true)) {
                return true;
            }
        }
        return false;
    }

    private function isSuperAdmin(array $roles): bool
    {
        return RoleResolver::isSuperAdmin($roles);
    }
}