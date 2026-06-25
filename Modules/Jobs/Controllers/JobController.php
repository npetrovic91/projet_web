<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Jobs\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Jobs\Models\JobCompanyTypeModel;
use Nenad\Autosav\Modules\Jobs\Models\JobModel;
use Nenad\Autosav\Modules\Jobs\Models\UserJobModel;
use Nenad\Autosav\Modules\Jobs\Services\JobService;

class JobController extends BaseController
{
    private JobService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new JobService(new JobModel(), new JobCompanyTypeModel(), new UserJobModel());
    }

    public function index(): void
    {
        $this->requirePermission('fonction.gerer');
        $this->render('Jobs/index', [
            'jobs' => $this->service->getForContext(null, $this->activeCompanyId(), true),
            'companyTypes' => $this->service->companyTypes(),
            'page_title' => 'Métiers',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('fonction.gerer');
        $this->render('Jobs/form', [
            'job' => null,
            'companyTypes' => $this->service->companyTypes(),
            'action' => '/jobs/store',
            'errors' => [],
            'isSuperAdmin' => has_role(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur'),
            'page_title' => 'Nouveau métier',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('fonction.gerer');
        $data = $this->request->all();
        $isGlobal = has_role(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur') && !empty($data['job_is_global']);
        $companyId = $isGlobal ? null : $this->activeCompanyId();
        $result = $this->service->create($data, $companyId, $this->operatorId(), $isGlobal);
        if ($result['success']) {
            $this->flash('success', 'Métier créé comme fonction métier.');
            $this->redirect('/jobs');
        }
        $this->render('Jobs/form', [
            'job' => $data,
            'companyTypes' => $this->service->companyTypes(),
            'action' => '/jobs/store',
            'errors' => $result['errors'],
            'isSuperAdmin' => has_role(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur'),
            'page_title' => 'Nouveau métier',
            'csrf_token' => $this->csrfToken(),
        ], 'main', 422);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('fonction.gerer');
        $job = $this->service->getById((int) $id, $this->activeCompanyId());
        if (!$job) {
            $this->flash('error', 'Métier introuvable ou hors périmètre.');
            $this->redirect('/jobs');
        }
        $this->render('Jobs/form', [
            'job' => $job,
            'companyTypes' => $this->service->companyTypes(),
            'action' => '/jobs/' . (int) $id . '/update',
            'errors' => [],
            'isSuperAdmin' => has_role(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur'),
            'page_title' => 'Modifier métier',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('fonction.gerer');
        $result = $this->service->update((int) $id, $this->request->all(), $this->operatorId());
        if ($result['success']) {
            $this->flash('success', 'Métier mis à jour.');
            $this->redirect('/jobs');
        }
        $this->flash('error', implode(' ', $result['errors']));
        $this->redirect('/jobs/' . (int) $id . '/edit');
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('fonction.gerer');
        $job = $this->service->getById((int) $id, $this->activeCompanyId());
        if ($job) {
            $this->service->setActive((int) $id, !((bool) $job['job_is_active']), $this->operatorId());
        }
        $this->redirect('/jobs');
    }

    protected function operatorId(): int
    {
        return (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
    }
}
