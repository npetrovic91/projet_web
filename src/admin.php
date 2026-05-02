<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Jobs\Services;

use Nenad\Autosav\Modules\Jobs\Models\JobCompanyTypeModel;
use Nenad\Autosav\Modules\Jobs\Models\JobModel;
use Nenad\Autosav\Modules\Jobs\Models\UserJobModel;

class JobService
{
    public function __construct(
        private JobModel $jobs,
        private JobCompanyTypeModel $jobTypes,
        private UserJobModel $userJobs
    ) {}

    public function getForContext(?int $companyTypeId = null, ?int $companyId = null, bool $adminView = false): array
    {
        return $this->jobs->getForContext($companyTypeId, $companyId, $adminView);
    }

    public function search(string $query, ?int $companyTypeId = null, ?int $companyId = null): array
    {
        return $this->jobs->search($query, $companyTypeId, $companyId);
    }

    public function getById(int $id, ?int $companyId = null): ?array
    {
        $job = $this->jobs->findByIdFull($id);
        if (!$job) {
            return null;
        }
        if (!$job['job_is_global'] && $companyId !== null && (int) $job['job_company_id'] !== $companyId) {
            return null;
        }
        return $job;
    }

    public function validate(array $data, ?int $companyId, bool $isGlobal, ?int $excludeId = null): array
    {
        $errors = [];
        $code = strtoupper(trim((string) ($data['job_code'] ?? $data['code'] ?? '')));
        $label = trim((string) ($data['job_label'] ?? $data['label'] ?? ''));
        $typeIds = (array) ($data['company_type_ids'] ?? []);

        if ($code === '' || !preg_match('/^[A-Z0-9_]{2,80}$/', $code)) {
            $errors['job_code'] = 'Le code doit contenir 2 a 80 caracteres A-Z, 0-9 ou _.';
        } elseif ($this->jobs->codeExists($code, $isGlobal ? null : $companyId, $excludeId)) {
            $errors['job_code'] = 'Ce code existe deja dans cette portee.';
        }
        if ($label === '' || strlen($label) > 150) {
            $errors['job_label'] = 'Le libelle est obligatoire et limite a 150 caracteres.';
        }
        if (!$isGlobal && $typeIds === []) {
            $errors['company_type_ids'] = 'Un metier specifique doit cibler au moins un type entreprise.';
        }
        return $errors;
    }

    public function create(array $data, ?int $companyId, int $createdBy, bool $isGlobal): array
    {
        $isGlobal = $isGlobal && $companyId === null;
        $errors = $this->validate($data, $companyId, $isGlobal);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $jobId = $this->jobs->createJob([
            'uuid' => $this->uuid(),
            'code' => strtoupper(trim((string) ($data['job_code'] ?? $data['code']))),
            'label' => trim((string) ($data['job_label'] ?? $data['label'])),
            'description' => trim((string) ($data['job_description'] ?? $data['description'] ?? '')) ?: null,
            'company_id' => $isGlobal ? null : $companyId,
            'is_global' => $isGlobal,
            'created_by' => $createdBy,
        ]);

        if (!$isGlobal) {
            $this->jobTypes->syncTypes($jobId, (array) ($data['company_type_ids'] ?? []), $createdBy);
        }
        logger('audit')->info('job_created', ['job_id' => $jobId, 'created_by' => $createdBy]);
        return ['success' => true, 'id' => $jobId, 'errors' => []];
    }

    public function update(int $id, array $data, int $updatedBy): array
    {
        $current = $this->jobs->findByIdFull($id);
        if (!$current) {
            return ['success' => false, 'errors' => ['general' => 'Metier introuvable.']];
        }
        $companyId = $current['job_company_id'] ? (int) $current['job_company_id'] : null;
        $isGlobal = (bool) $current['job_is_global'];
        $errors = $this->validate($data, $companyId, $isGlobal, $id);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }
        $this->jobs->updateJob($id, [
            'code' => strtoupper(trim((string) ($data['job_code'] ?? $data['code']))),
            'label' => trim((string) ($data['job_label'] ?? $data['label'])),
            'description' => trim((string) ($data['job_description'] ?? $data['description'] ?? '')) ?: null,
            'updated_by' => $updatedBy,
        ]);
        if (!$isGlobal) {
            $this->jobTypes->syncTypes($id, (array) ($data['company_type_ids'] ?? []), $updatedBy);
        }
        return ['success' => true, 'errors' => []];
    }

    public function setActive(int $id, bool $active, int $operatorId): array
    {
        $this->jobs->setActive($id, $active, $operatorId);
        return ['success' => true, 'errors' => []];
    }

    public function getUserJobs(int $userId): array
    {
        return $this->userJobs->getForUser($userId);
    }

    public function assignToUser(int $userId, int $jobId, ?int $companyTypeId, ?int $companyId, bool $isPrimary, int $createdBy): array
    {
        $job = $this->jobs->findByIdFull($jobId);
        if (!$job || !$job['job_is_active']) {
            return ['success' => false, 'errors' => ['general' => 'Metier introuvable ou inactif.']];
        }
        if (!$job['job_is_global'] && $companyTypeId !== null && !$this->jobTypes->isAllowedForType($jobId, $companyTypeId)) {
            return ['success' => false, 'errors' => ['general' => 'Metier non disponible pour ce type entreprise.']];
        }
        if ($this->userJobs->hasJob($userId, $jobId, $companyId)) {
            return ['success' => false, 'errors' => ['general' => 'Metier deja attribue.']];
        }
        $this->userJobs->assign($userId, $jobId, $companyTypeId, $companyId, $isPrimary, $createdBy);
        return ['success' => true, 'errors' => []];
    }

    public function unassignFromUser(int $userId, int $jobId): array
    {
        $this->userJobs->unassign($userId, $jobId);
        return ['success' => true, 'errors' => []];
    }

    public function syncUserJobs(int $userId, array $jobIds, int $primaryJobId, int $createdBy): array
    {
        $jobIds = array_values(array_unique(array_map('intval', $jobIds)));
        if ($jobIds !== [] && !in_array($primaryJobId, $jobIds, true)) {
            $primaryJobId = $jobIds[0];
        }
        $this->userJobs->sync($userId, $jobIds, $primaryJobId, $createdBy);
        return ['success' => true, 'errors' => []];
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
