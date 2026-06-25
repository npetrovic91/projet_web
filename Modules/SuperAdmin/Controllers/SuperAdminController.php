<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\SuperAdmin\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\SuperAdmin\Services\SuperAdminService;

final class SuperAdminController extends BaseController
{
    private SuperAdminService $service;

    public function __construct(?\Nenad\Autosav\Core\Database\Database $database = null)
    {
        parent::__construct($database);
        $this->service = new SuperAdminService();
    }

    public function index(): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $this->render('SuperAdmin/index', [
            'pageTitle' => 'Super-admin — Gestion de l’application',
            'breadcrumb' => ['Super-admin' => '/super-admin'],
            'data' => $this->service->tableauDeBord(),
        ]);
    }

    public function application(): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $this->render('SuperAdmin/application', [
            'pageTitle' => 'Gestion globale de l’application',
            'breadcrumb' => ['Super-admin' => '/super-admin', 'Application' => '/super-admin/application'],
            'data' => $this->service->tableauDeBord(),
        ]);
    }

    public function exportJson(): never
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $this->json(true, $this->service->export(), 'Export de gestion application généré.');
    }
}
