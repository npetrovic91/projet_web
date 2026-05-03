<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Maintenance\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Maintenance\Models\MaintenanceEventModel;
use Nenad\Autosav\Modules\Maintenance\Models\MaintenanceModel;
use Nenad\Autosav\Modules\Maintenance\Services\MaintenanceService;

class MaintenanceController extends BaseController
{
    private MaintenanceService $maintenance;

    public function __construct()
    {
        parent::__construct();
        $this->maintenance = new MaintenanceService(new MaintenanceModel(), new MaintenanceEventModel());
    }

    public function show(): never
    {
        http_response_code(503);
        $message = 'Application temporairement indisponible pour maintenance.';
        include MAINTENANCE_VIEW;
        exit;
    }

    public function admin(): void
    {
        $this->requirePermission('maintenance.read');
        $data = $this->maintenance->dashboard();
        $this->render('Maintenance/admin', [
            'state' => $data['state'],
            'indicators' => $data['indicators'],
            'events' => $data['events'],
            'csrf_token' => $this->csrfToken(),
            'page_title' => 'Maintenance applicative',
        ]);
    }

    public function toggle(): void
    {
        $this->requirePermission('maintenance.manage');
        $this->validateCsrf();
        $result = $this->maintenance->update($this->request->all(), (int) $this->getCurrentUser()['use_id'], client_ip());
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/admin/maintenance');
    }
}
