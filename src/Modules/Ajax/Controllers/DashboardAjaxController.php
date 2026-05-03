<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Dashboard\Services\DashboardService;

class DashboardAjaxController extends AjaxController
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardService = new DashboardService();
    }

    public function widgets(): void
    {
        $context = $this->context();
        $widgets = $this->dashboardService->getWidgetsForUser($this->user['id'], $this->user['roles'], $context);
        $rendered = [];
        foreach ($widgets['async'] as $widget) {
            $rendered[] = [
                'code' => $widget['code'],
                'html' => $this->dashboardService->renderWidget($widget['view_file'], $context),
            ];
        }
        AjaxResponseService::success('Widgets charges.', ['widgets' => $rendered]);
    }

    public function singleWidget(string $code = ''): void
    {
        $code = $code !== '' ? $code : (string) ($_GET['code'] ?? '');
        if ($code === '') {
            AjaxResponseService::badRequest('Code widget manquant.');
        }
        $html = $this->dashboardService->renderWidgetByCode($code, $this->user['id'], $this->user['roles'], $this->context());
        if ($html === null) {
            AjaxResponseService::forbidden('Widget non autorise ou introuvable.');
        }
        AjaxResponseService::success('Widget charge.', ['code' => $code, 'html' => $html]);
    }

    private function context(): array
    {
        return [
            'user_id' => $this->user['id'],
            'user_roles' => $this->user['roles'],
            'active_company_id' => $_SESSION['active_company_id'] ?? null,
            'active_brand_id' => $_SESSION['active_brand_id'] ?? null,
            'manager_scope' => in_array(ROLE_MANAGER, $this->user['roles'], true),
        ];
    }
}
