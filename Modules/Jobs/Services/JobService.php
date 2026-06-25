<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Jobs\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Jobs\Models\JobCompanyTypeModel;
use Nenad\Autosav\Modules\Jobs\Models\JobModel;
use Nenad\Autosav\Modules\Jobs\Models\UserJobModel;

/**
 * Compatibilite Jobs : un metier est une fonction metier stockee dans sav_fonctions.
 */
class JobService implements ServiceInterface{
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

    public function companyTypes(): array
    {
        return $this->jobs->companyTypes();
    }

    public function getById(int $id, ?int $companyId = null): ?array
    {
        $job = $this->jobs->findByIdFull($id);
        if (!$job) {
            return null;
        }
        if ((int) $job['job_is_global'] !== 1 && $companyId !== null && (int) $job['job_company_id'] !== $companyId) {
            return null;
        }
        return $job;
    }

    public function validate(array $data, ?int $companyId, bool $isGlobal, ?int $excludeId = null): array
    {
        $errors = [];
        $code = strtoupper(trim((string) ($data['job_code'] ?? $data['fon_code'] ?? $data['code'] ?? '')));
        $label = trim((string) ($data['job_label'] ?? $data['fon_nom'] ?? $data['label'] ?? ''));

        if ($code === '' || !preg_match('/^[A-Z0-9_]{2,100}$/', $code)) {
            $errors['job_code'] = 'Le code doit contenir 2 a 100 caracteres A-Z, 0-9 ou _.';
        } elseif ($this->jobs->codeExists($code, $isGlobal ? null : $companyId, $excludeId)) {
            $errors['job_code'] = 'Ce code existe deja dans les fonctions actives.';
        }
        if ($label === '' || mb_strlen($label) > 120) {
            $errors['job_label'] = 'Le libelle est obligatoire et limite a 120 caracteres.';
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
            'code' => strtoupper(trim((string) ($data['job_code'] ?? $data['fon_code'] ?? $data['code']))),
            'label' => trim((string) ($data['job_label'] ?? $data['fon_nom'] ?? $data['label'])),
            'description' => trim((string) ($data['job_description'] ?? $data['fon_description'] ?? $data['description'] ?? '')) ?: null,
            'company_id' => $isGlobal ? null : $companyId,
            'company_type_id' => $this->nullableInt($data['job_company_type_id'] ?? $data['fon_type_societe_id'] ?? $data['company_type_id'] ?? null),
            'created_by' => $createdBy > 0 ? $createdBy : null,
        ]);

        if (function_exists('logger')) {
            logger('audit')->info('job_alias_created_on_sav_fonctions', ['fon_id' => $jobId, 'created_by' => $createdBy]);
        }

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
            'code' => strtoupper(trim((string) ($data['job_code'] ?? $data['fon_code'] ?? $data['code']))),
            'label' => trim((string) ($data['job_label'] ?? $data['fon_nom'] ?? $data['label'])),
            'description' => trim((string) ($data['job_description'] ?? $data['fon_description'] ?? $data['description'] ?? '')) ?: null,
            'company_type_id' => $this->nullableInt($data['job_company_type_id'] ?? $data['fon_type_societe_id'] ?? $data['company_type_id'] ?? null),
            'updated_by' => $updatedBy > 0 ? $updatedBy : null,
        ]);

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
        if (!$job || !(bool) $job['job_is_active']) {
            return ['success' => false, 'errors' => ['general' => 'Metier introuvable ou inactif.']];
        }
        if ($companyTypeId !== null && !$this->jobTypes->isAllowedForType($jobId, $companyTypeId)) {
            return ['success' => false, 'errors' => ['general' => 'Metier non disponible pour ce type de societe.']];
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

    public function syncUserJobs(int $userId, array $jobIds, int $primaryJobId, int $createdBy, ?int $companyId = null): array
    {
        $jobIds = array_values(array_unique(array_filter(array_map('intval', $jobIds), static fn(int $id): bool => $id > 0)));
        $this->userJobs->sync($userId, $jobIds, $primaryJobId, $createdBy, $companyId);
        return ['success' => true, 'errors' => []];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $int = (int) $value;
        return $int > 0 ? $int : null;
    }
}
