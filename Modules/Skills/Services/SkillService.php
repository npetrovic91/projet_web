<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Skills\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Skills\Models\SkillModel;
use Nenad\Autosav\Modules\Skills\Models\UserSkillModel;

/**
 * Service Skills : Competence = savoir-faire metier, niveau = maitrise utilisateur.
 */
class SkillService implements ServiceInterface{
    public function __construct(
        private SkillModel $skills,
        private UserSkillModel $userSkills
    ) {}

    public function getForContext(?int $companyId = null, bool $adminView = false, array $networkOwnerCompanyIds = []): array
    {
        return $this->skills->getForContext($companyId, $adminView, $networkOwnerCompanyIds);
    }

    public function search(string $query, ?int $companyId = null, array $networkOwnerCompanyIds = []): array
    {
        return $this->skills->search($query, $companyId, 20, $networkOwnerCompanyIds);
    }

    public function getById(int $id, ?int $companyId = null, array $networkOwnerCompanyIds = []): ?array
    {
        $skill = $this->skills->findByIdFull($id);
        if (!$skill) {
            return null;
        }
        if ((int) $skill['skl_is_global'] === 1 || $companyId === null) {
            return $skill;
        }
        $ownerId = (int) ($skill['cmp_societe_proprietaire_id'] ?? 0);
        if ($ownerId === $companyId) {
            return $skill;
        }
        if (($skill['cmp_portee_code'] ?? $skill['skl_scope_code'] ?? 'interne') === 'reseau'
            && in_array($ownerId, array_map('intval', $networkOwnerCompanyIds), true)) {
            return $skill;
        }
        return null;
    }

    public function getUserSkills(int $userId): array
    {
        return $this->userSkills->getForUser($userId);
    }

    public function levels(): array
    {
        return $this->userSkills->levels();
    }

    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        $code = strtoupper(trim((string) ($data['cmp_code'] ?? $data['skl_code'] ?? $data['code'] ?? '')));
        $name = trim((string) ($data['cmp_nom'] ?? $data['skl_name'] ?? $data['skl_label'] ?? $data['name'] ?? ''));

        if ($code === '' || !preg_match('/^[A-Z0-9_]{2,100}$/', $code)) {
            $errors['cmp_code'] = 'Le code doit contenir 2 a 100 caracteres A-Z, 0-9 ou _.';
        } elseif ($this->skills->codeExists($code, $excludeId)) {
            $errors['cmp_code'] = 'Ce code existe deja dans les competences actives.';
        }
        if ($name === '' || mb_strlen($name) > 160) {
            $errors['cmp_nom'] = 'Le nom est obligatoire et limite a 160 caracteres.';
        }

        return $errors;
    }

    public function create(array $data, ?int $companyId, int $createdBy, string $scopeCode): array
    {
        $ownerCompanyId = $scopeCode === 'plateforme' ? null : $companyId;
        if ($scopeCode !== 'plateforme' && ($ownerCompanyId ?? 0) <= 0) {
            return ['success' => false, 'errors' => ['company' => 'Selectionnez une societe active avant de creer une competence.']];
        }

        try {
            $errors = $this->validate($data);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'competence_validate_failed', ['error' => $e->getMessage(), 'company_id' => $ownerCompanyId]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la validation de la competence.']];
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $id = $this->skills->createSkill([
                'code' => strtoupper(trim((string) ($data['cmp_code'] ?? $data['skl_code'] ?? $data['code']))),
                'name' => trim((string) ($data['cmp_nom'] ?? $data['skl_name'] ?? $data['skl_label'] ?? $data['name'])),
                'description' => trim((string) ($data['cmp_description'] ?? $data['skl_description'] ?? $data['description'] ?? '')) ?: null,
                'owner_company_id' => $ownerCompanyId,
                'scope_code' => $scopeCode,
                'created_by' => $createdBy > 0 ? $createdBy : null,
            ]);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'competence_create_failed', ['error' => $e->getMessage(), 'company_id' => $ownerCompanyId]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la creation de la competence. Verifiez le code et la societe active.']];
        }

        $this->safeLog('audit', 'info', 'competence_created', ['cmp_id' => $id, 'created_by' => $createdBy]);

        return ['success' => true, 'id' => $id, 'errors' => []];
    }

    public function update(int $id, array $data, int $updatedBy): array
    {
        $current = $this->skills->findByIdFull($id);
        if (!$current) {
            return ['success' => false, 'errors' => ['general' => 'Competence introuvable.']];
        }

        try {
            $errors = $this->validate($data, $id);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'competence_validate_update_failed', ['error' => $e->getMessage(), 'cmp_id' => $id]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la validation de la competence.']];
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->skills->updateSkill($id, [
                'code' => strtoupper(trim((string) ($data['cmp_code'] ?? $data['skl_code'] ?? $data['code']))),
                'name' => trim((string) ($data['cmp_nom'] ?? $data['skl_name'] ?? $data['skl_label'] ?? $data['name'])),
                'description' => trim((string) ($data['cmp_description'] ?? $data['skl_description'] ?? $data['description'] ?? '')) ?: null,
                'scope_code' => $data['cmp_portee_code'] ?? $data['skl_scope_code'] ?? $current['cmp_portee_code'] ?? $current['skl_scope_code'] ?? 'interne',
                'updated_by' => $updatedBy > 0 ? $updatedBy : null,
            ]);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'competence_update_failed', ['error' => $e->getMessage(), 'cmp_id' => $id]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la mise a jour de la competence.']];
        }

        $this->safeLog('audit', 'info', 'competence_updated', ['cmp_id' => $id, 'updated_by' => $updatedBy]);

        return ['success' => true, 'errors' => []];
    }

    public function setActive(int $id, bool $active, int $operatorId): array
    {
        $this->skills->setActive($id, $active, $operatorId);
        $this->safeLog('audit', 'info', 'competence_status_changed', ['cmp_id' => $id, 'active' => $active, 'operator_id' => $operatorId]);
        return ['success' => true, 'errors' => []];
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
