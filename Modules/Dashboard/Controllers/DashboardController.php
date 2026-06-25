<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Dashboard\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Dashboard\Services\DashboardService;

class DashboardController extends BaseController
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardService = new DashboardService();
    }

    public function index(): void
    {
        $this->requireAuth();

        $sessionUser = $_SESSION['user'] ?? [];
        $sessionSociety = $_SESSION['society'] ?? [];

        $userId = (int)($sessionUser['id'] ?? $_SESSION['user_id'] ?? 0);
        $roles = (array)($sessionUser['role_codes'] ?? $_SESSION['user_roles'] ?? []);
        $companies = (array)($_SESSION['available_companies'] ?? $sessionUser['companies'] ?? $sessionUser['societes'] ?? []);
        $brands = (array)($_SESSION['available_brands'] ?? $sessionSociety['marques'] ?? []);

        $activeCompanyId = isset($sessionSociety['soc_id'])
            ? (int)$sessionSociety['soc_id']
            : (isset($sessionSociety['id'])
                ? (int)$sessionSociety['id']
                : (isset($sessionUser['actual_society_id']) ? (int)$sessionUser['actual_society_id'] : ($_SESSION['active_company_id'] ?? null)));

        $activeBrandId = isset($sessionUser['actual_brand'])
            ? (int)$sessionUser['actual_brand']
            : (isset($sessionUser['active_brand_id']) ? (int)$sessionUser['active_brand_id'] : ($_SESSION['active_brand_id'] ?? null));

        $context = [
            'user_id' => $userId,
            'user_name' => (string)($sessionUser['nom_complet'] ?? $sessionUser['name'] ?? ''),
            'user_roles' => $roles,
            'user_permissions' => (array)($sessionUser['permissions'] ?? $_SESSION['user_permissions'] ?? []),
            'active_company_id' => $activeCompanyId,
            'active_brand_id' => $activeBrandId,
            'society' => $sessionSociety,
            'companies' => $companies,
            'brands' => $brands,
            'manager_scope' => in_array('manager', $roles, true),
        ];

        // withDashboardData() est appelé UNE SEULE FOIS ici pour charger le contexte complet.
        // getWidgetsForUser() reçoit ce contexte directement — il ne rappelle plus withDashboardData.
        $preContext = $this->dashboardService->withDashboardData($userId, $roles, $context);
        $typePseudoRoles = array_map(
            static fn(string $c): string => 'type_' . $c,
            $preContext['active_company_type_codes'] ?? []
        );
        $effectiveRoles = array_values(array_unique(array_merge($roles, $typePseudoRoles)));

        $widgets = $this->dashboardService->getWidgetsForUser($userId, $effectiveRoles, $preContext);
        $context = $widgets['context'] ?? $preContext;

        $this->render('Dashboard/Views/dashboard', [
            'page_title' => 'Tableau de bord',
            'csrf_token' => function_exists('csrf_token') ? csrf_token() : '',
            'companies' => $companies,
            'brands' => $brands,
            'active_company_id' => $activeCompanyId,
            'active_brand_id' => $activeBrandId,
            'sync_widgets' => $widgets['sync'],
            'async_widgets' => $widgets['async'],
            'context' => $context,
            'terms_pending' => $_SESSION['terms_pending'] ?? false,
        ]);
    }
}