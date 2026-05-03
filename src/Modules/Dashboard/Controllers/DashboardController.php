<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Dashboard\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Ajax\Models\ContextModel;
use Nenad\Autosav\Modules\Dashboard\Services\DashboardService;

class DashboardController extends BaseController
{
    private DashboardService $dashboardService;
    private ContextModel $contextModel;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardService = new DashboardService();
        $this->contextModel = new ContextModel();
    }

    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $roles = (array) ($_SESSION['user_roles'] ?? [ROLE_USER]);
        $companies = $this->contextModel->getUserCompanies($userId);
        $activeCompanyId = isset($_SESSION['active_company_id']) ? (int) $_SESSION['active_company_id'] : null;
        if ($activeCompanyId === null && isset($companies[0]['com_id'])) {
            $activeCompanyId = (int) $companies[0]['com_id'];
            $_SESSION['active_company_id'] = $activeCompanyId;
        }

        $brands = $activeCompanyId ? $this->contextModel->getBrandsForCompany($activeCompanyId) : [];
        $activeBrandId = isset($_SESSION['active_brand_id']) ? (int) $_SESSION['active_brand_id'] : null;

        $context = [
            'user_id' => $userId,
            'user_name' => trim(($_SESSION['user_firstname'] ?? '') . ' ' . ($_SESSION['user_lastname'] ?? '')),
            'user_roles' => $roles,
            'active_company_id' => $activeCompanyId,
            'active_brand_id' => $activeBrandId,
            'manager_scope' => in_array(ROLE_MANAGER, $roles, true),
        ];

        $widgets = $this->dashboardService->getWidgetsForUser($userId, $roles, $context);

        $this->render('Dashboard/Views/dashboard', [
            'page_title' => 'Tableau de bord',
            'csrf_token' => csrf_token(),
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
