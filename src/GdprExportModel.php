<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Jobs\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Database\Database;
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
        $this->requireAuth();
        $this->render('Jobs/index', [
            'jobs' => $this->service->getForContext(null, $this->activeCompanyId(), true),
            'page_title' => 'Metiers',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->render('Jobs/form', [
            'job' => null,
            'companyTypes' => $this->companyTypes(),
            'action' => '/jobs/store',
            'errors' => [],
            'isSuperAdmin' => has_role('SUPERADMIN'),
            'page_title' => 'Nouveau metier',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $data = $this->request->all();
        $isGlobal = has_role('SUPERADMIN') && !empty($data['job_is_global']);
        $companyId = $isGlobal ? null : $this->activeCompanyId();
        $result = $this->service->create($data, $companyId, (int) $this->getCurrentUser()['use_id'], $isGlobal);
        if ($result['success']) {
            $this->flash('success', 'Metier cree.');
            $this->redirect('/jobs');
        }
        $this->render('Jobs/form', [
            'job' => $data,
            'companyTypes' => $this->companyTypes(),
            'action' => '/jobs/store',
            'errors' => $result['errors'],
            'isSuperAdmin' => has_role('SUPERADMIN'),
            'page_title' => 'Nouveau metier',
            'csrf_token' => $this->csrfToken(),
        ], 'main', 422);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $job = $this->service->getById((int) $id, $this->activeCompanyId());
        if (!$job) {
            $this->flash('error', 'Metier introuvable ou hors perimetre.');
            $this->redirect('/jobs');
        }
        $this->render('Jobs/form', [
            'job' => $job,
            'companyTypes' => $this->companyTypes(),
            'action' => '/jobs/' . (int) $id . '/update',
            'errors' => [],
            'isSuperAdmin' => has_role('SUPERADMIN'),
            'page_title' => 'Modifier metier',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $result = $this->service->update((int) $id, $this->request->all(), (int) $this->getCurrentUser()['use_id']);
        if ($result['success']) {
            $this->flash('success', 'Metier mis a jour.');
            $this->redirect('/jobs');
        }
        $this->flash('error', implode(' ', $result['errors']));
        $this->redirect('/jobs/' . (int) $id . '/edit');
    }

    public function toggle(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $job = $this->service->getById((int) $id, $this->activeCompanyId());
        if ($job) {
            $this->service->setActive((int) $id, !((bool) $job['job_is_active']), (int) $this->getCurrentUser()['use_id']);
        }
        $this->redirect('/jobs');
    }

    private function companyTypes(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT cty_id, cty_code, cty_label FROM sav_company_types WHERE cty_is_active = 1 ORDER BY cty_sort_order ASC'
        );
    }
}
