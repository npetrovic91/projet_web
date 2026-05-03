<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Jobs\Models\JobCompanyTypeModel;
use Nenad\Autosav\Modules\Jobs\Models\JobModel;
use Nenad\Autosav\Modules\Jobs\Models\UserJobModel;
use Nenad\Autosav\Modules\Jobs\Services\JobService;

class JobsAjaxController extends AjaxController
{
    private JobService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new JobService(new JobModel(), new JobCompanyTypeModel(), new UserJobModel());
    }

    public function list(): void
    {
        $companyTypeId = $this->request->get('company_type_id') ? (int) $this->request->get('company_type_id') : null;
        $companyId = $this->request->get('company_id') ? (int) $this->request->get('company_id') : ($this->user['active_company_id'] ?? null);
        AjaxResponseService::success('Metiers charges.', [
            'jobs' => $this->service->getForContext($companyTypeId, $companyId ? (int) $companyId : null)
        ]);
    }

    public function byCompanyType(): void
    {
        $this->list();
    }

    public function search(): void
    {
        $q = trim((string) $this->request->get('q', ''));
        $companyTypeId = $this->request->get('company_type_id') ? (int) $this->request->get('company_type_id') : null;
        $companyId = $this->request->get('company_id') ? (int) $this->request->get('company_id') : ($this->user['active_company_id'] ?? null);
        $items = $q === '' ? [] : $this->service->search($q, $companyTypeId, $companyId ? (int) $companyId : null);
        AjaxResponseService::success('Recherche effectuee.', [
            'results' => array_map(static fn(array $row): array => [
                'id' => (int) $row['job_id'],
                'text' => $row['job_label'],
                'code' => $row['job_code'],
                'is_global' => (bool) $row['job_is_global'],
            ], $items),
        ]);
    }

    public function forUser(string $id): void
    {
        AjaxResponseService::success('Metiers utilisateur charges.', [
            'jobs' => $this->service->getUserJobs((int) $id)
        ]);
    }

    public function assignToUser(string $id): void
    {
        $data = $this->request->json();
        $result = $this->service->assignToUser(
            (int) $id,
            (int) ($data['job_id'] ?? 0),
            isset($data['company_type_id']) && $data['company_type_id'] !== '' ? (int) $data['company_type_id'] : null,
            isset($data['company_id']) && $data['company_id'] !== '' ? (int) $data['company_id'] : null,
            (bool) ($data['is_primary'] ?? false),
            (int) $this->user['id']
        );
        $this->sendResult($result);
    }

    public function unassignFromUser(string $id): void
    {
        $data = $this->request->json();
        $this->sendResult($this->service->unassignFromUser((int) $id, (int) ($data['job_id'] ?? 0)));
    }

    public function syncForUser(string $id): void
    {
        $data = $this->request->json();
        $this->sendResult($this->service->syncUserJobs(
            (int) $id,
            (array) ($data['job_ids'] ?? []),
            (int) ($data['primary_job_id'] ?? 0),
            (int) $this->user['id']
        ));
    }

    private function sendResult(array $result): void
    {
        if (!($result['success'] ?? false)) {
            AjaxResponseService::validationError($result['errors'] ?? [], $result['errors']['general'] ?? 'Action impossible.');
        }
        AjaxResponseService::success('Action realisee.');
    }
}
