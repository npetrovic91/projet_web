<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Qualifications\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Qualifications\Models\QualificationModel;
use Nenad\Autosav\Modules\Qualifications\Models\UserQualificationModel;

/**
 * Service Qualifications : couche de compatibilite vers les certifications SQL.
 */
class QualificationService implements ServiceInterface{
    public function __construct(
        private QualificationModel $qualifications,
        private UserQualificationModel $userQualifications
    ) {}

    public function getForContext(?int $companyId = null, bool $adminView = false, array $networkOwnerCompanyIds = []): array
    {
        return $this->qualifications->getForContext($companyId, $adminView, $networkOwnerCompanyIds);
    }

    public function search(string $query, ?int $companyId = null, array $networkOwnerCompanyIds = []): array
    {
        return $this->qualifications->search($query, $companyId, 20, $networkOwnerCompanyIds);
    }

    public function getById(int $id, ?int $companyId = null, array $networkOwnerCompanyIds = []): ?array
    {
        $qualification = $this->qualifications->findByIdFull($id);
        if (!$qualification) {
            return null;
        }
        if ((int) $qualification['qua_is_global'] === 1 || $companyId === null) {
            return $qualification;
        }
        $ownerId = (int) ($qualification['cer_societe_proprietaire_id'] ?? 0);
        if ($ownerId === $companyId) {
            return $qualification;
        }
        if (($qualification['cer_portee_code'] ?? $qualification['qua_scope_code'] ?? 'interne') === 'reseau'
            && in_array($ownerId, array_map('intval', $networkOwnerCompanyIds), true)) {
            return $qualification;
        }
        return null;
    }

    public function getUserQualifications(int $userId): array
    {
        return $this->userQualifications->getForUser($userId);
    }

    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        $code = strtoupper(trim((string) ($data['cer_code'] ?? $data['qua_code'] ?? $data['code'] ?? '')));
        $name = trim((string) ($data['cer_nom'] ?? $data['qua_name'] ?? $data['qua_label'] ?? $data['name'] ?? ''));
        if ($code === '' || !preg_match('/^[A-Z0-9_]{2,100}$/', $code)) {
            $errors['cer_code'] = 'Le code doit contenir 2 a 100 caracteres A-Z, 0-9 ou _.';
        } elseif ($this->qualifications->codeExists($code, $excludeId)) {
            $errors['cer_code'] = 'Ce code existe deja dans les certifications actives.';
        }
        if ($name === '' || mb_strlen($name) > 160) {
            $errors['cer_nom'] = 'Le nom est obligatoire et limite a 160 caracteres.';
        }
        return $errors;
    }

    public function create(array $data, ?int $companyId, int $createdBy, string $scopeCode): array
    {
        $ownerCompanyId = $scopeCode === 'plateforme' ? null : $companyId;
        if ($scopeCode !== 'plateforme' && ($ownerCompanyId ?? 0) <= 0) {
            return ['success' => false, 'errors' => ['company' => 'Selectionnez une societe active avant de creer une certification.']];
        }

        try {
            $errors = $this->validate($data);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'certification_validate_failed', ['error' => $e->getMessage(), 'company_id' => $ownerCompanyId]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la validation de la certification.']];
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $id = $this->qualifications->createQualification([
                'code' => strtoupper(trim((string) ($data['cer_code'] ?? $data['qua_code'] ?? $data['code']))),
                'name' => trim((string) ($data['cer_nom'] ?? $data['qua_name'] ?? $data['qua_label'] ?? $data['name'])),
                'description' => trim((string) ($data['cer_description'] ?? $data['qua_description'] ?? $data['description'] ?? '')) ?: null,
                'prerequisites_json' => $this->normalisePrerequisites($data['cer_prerequis_json'] ?? $data['qua_prerequisites'] ?? $data['prerequisites'] ?? null),
                'owner_company_id' => $ownerCompanyId,
                'scope_code' => $scopeCode,
                'created_by' => $createdBy > 0 ? $createdBy : null,
            ]);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'certification_create_failed', ['error' => $e->getMessage(), 'company_id' => $ownerCompanyId]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la creation de la certification. Verifiez le code, les prerequis et la societe active.']];
        }
        $this->safeLog('audit', 'info', 'certification_created', ['cer_id' => $id, 'created_by' => $createdBy]);
        return ['success' => true, 'id' => $id, 'errors' => []];
    }

    public function update(int $id, array $data, int $updatedBy): array
    {
        $current = $this->qualifications->findByIdFull($id);
        if (!$current) {
            return ['success' => false, 'errors' => ['general' => 'Certification introuvable.']];
        }
        try {
            $errors = $this->validate($data, $id);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'certification_validate_update_failed', ['error' => $e->getMessage(), 'cer_id' => $id]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la validation de la certification.']];
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }
        try {
            $this->qualifications->updateQualification($id, [
                'code' => strtoupper(trim((string) ($data['cer_code'] ?? $data['qua_code'] ?? $data['code']))),
                'name' => trim((string) ($data['cer_nom'] ?? $data['qua_name'] ?? $data['qua_label'] ?? $data['name'])),
                'description' => trim((string) ($data['cer_description'] ?? $data['qua_description'] ?? $data['description'] ?? '')) ?: null,
                'prerequisites_json' => $this->normalisePrerequisites($data['cer_prerequis_json'] ?? $data['qua_prerequisites'] ?? $data['prerequisites'] ?? null),
                'scope_code' => $data['cer_portee_code'] ?? $data['qua_scope_code'] ?? $current['cer_portee_code'] ?? $current['qua_scope_code'] ?? 'interne',
                'updated_by' => $updatedBy > 0 ? $updatedBy : null,
            ]);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'certification_update_failed', ['error' => $e->getMessage(), 'cer_id' => $id]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la mise a jour de la certification.']];
        }
        $this->safeLog('audit', 'info', 'certification_updated', ['cer_id' => $id, 'updated_by' => $updatedBy]);
        return ['success' => true, 'errors' => []];
    }

    public function setActive(int $id, bool $active, int $operatorId): array
    {
        $this->qualifications->setActive($id, $active, $operatorId);
        $this->safeLog('audit', 'info', 'certification_status_changed', ['cer_id' => $id, 'active' => $active, 'operator_id' => $operatorId]);
        return ['success' => true, 'errors' => []];
    }

    private function normalisePrerequisites(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return '[]';
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $value) ?: [])));
            return $lines === [] ? '[]' : json_encode($lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function safeLog(string $channel, string $level, string $message, array $context = []): void
    {
        try {
            if (function_exists('logger')) {
                logger($channel)->{$level}($message, $context);
            }
        } catch (\Throwable) {
            error_log('[AUTOSAV] ' . $message);
        }
    }
}
