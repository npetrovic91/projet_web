<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Functions\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Functions\Models\FunctionModel;
use Nenad\Autosav\Modules\Functions\Models\UserFunctionModel;

/**
 * Service Functions : Fonction = metier reel, Role = droit applicatif, Permission = action technique.
 */
class FunctionService implements ServiceInterface{
    public function __construct(
        private FunctionModel $functions,
        private UserFunctionModel $userFunctions
    ) {}

    public function getForContext(?int $companyId = null, bool $adminView = false, array $networkOwnerCompanyIds = []): array
    {
        return $this->functions->getForContext($companyId, $adminView, $networkOwnerCompanyIds);
    }

    public function search(string $query, ?int $companyId = null, array $networkOwnerCompanyIds = []): array
    {
        return $this->functions->search($query, $companyId, 20, $networkOwnerCompanyIds);
    }

    public function companyTypes(): array
    {
        return $this->functions->companyTypes();
    }

    public function getById(int $id, ?int $companyId = null, array $networkOwnerCompanyIds = []): ?array
    {
        $function = $this->functions->findByIdFull($id);
        if (!$function) {
            return null;
        }
        if ((int) $function['fnc_is_global'] === 1 || $companyId === null) {
            return $function;
        }
        $ownerId = (int) ($function['fon_societe_proprietaire_id'] ?? 0);
        if ($ownerId === $companyId) {
            return $function;
        }
        if (($function['fon_portee_code'] ?? $function['fnc_scope_code'] ?? 'interne') === 'reseau'
            && in_array($ownerId, array_map('intval', $networkOwnerCompanyIds), true)) {
            return $function;
        }
        return null;
    }

    public function validate(array $data, ?int $companyId, ?int $excludeId = null): array
    {
        $errors = [];
        $code = strtoupper(trim((string) ($data['fon_code'] ?? $data['fnc_code'] ?? $data['job_code'] ?? $data['code'] ?? '')));
        $name = trim((string) ($data['fon_nom'] ?? $data['fnc_label'] ?? $data['job_label'] ?? $data['label'] ?? $data['name'] ?? ''));

        if ($code === '' || !preg_match('/^[A-Z0-9_]{2,80}$/', $code)) {
            $errors['fon_code'] = 'Le code doit contenir 2 a 80 caracteres A-Z, 0-9 ou _.';
        } elseif ($this->functions->codeExists($code, $companyId, $excludeId)) {
            $errors['fon_code'] = 'Ce code existe deja dans les fonctions actives.';
        }
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['fon_nom'] = 'Le nom est obligatoire et limite a 120 caracteres.';
        }

        return $errors;
    }

    public function create(array $data, ?int $companyId, int $createdBy, string $scopeCode): array
    {
        $ownerCompanyId = $scopeCode === 'plateforme' ? null : $companyId;
        if ($scopeCode !== 'plateforme' && ($ownerCompanyId ?? 0) <= 0) {
            return ['success' => false, 'errors' => ['company' => 'Selectionnez une societe active avant de creer une fonction.']];
        }

        try {
            $errors = $this->validate($data, $ownerCompanyId);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'fonction_validate_failed', ['error' => $e->getMessage(), 'company_id' => $ownerCompanyId]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la validation de la fonction.']];
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $id = $this->functions->createFunction([
                'code' => strtoupper(trim((string) ($data['fon_code'] ?? $data['fnc_code'] ?? $data['job_code'] ?? $data['code']))),
                'name' => trim((string) ($data['fon_nom'] ?? $data['fnc_label'] ?? $data['job_label'] ?? $data['label'] ?? $data['name'])),
                'description' => trim((string) ($data['fon_description'] ?? $data['fnc_description'] ?? $data['job_description'] ?? $data['description'] ?? '')) ?: null,
                'owner_company_id' => $ownerCompanyId,
                'company_type_id' => $this->nullableInt($data['fon_type_societe_id'] ?? $data['fnc_company_type_id'] ?? $data['company_type_id'] ?? null),
                'scope_code' => $scopeCode,
                'created_by' => $createdBy > 0 ? $createdBy : null,
            ]);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'fonction_create_failed', ['error' => $e->getMessage(), 'company_id' => $ownerCompanyId]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la creation de la fonction. Verifiez le code, la societe active et le type de societe.']];
        }

        $this->safeLog('audit', 'info', 'fonction_created', ['fon_id' => $id, 'created_by' => $createdBy]);

        return ['success' => true, 'id' => $id, 'errors' => []];
    }

    public function update(int $id, array $data, int $updatedBy): array
    {
        $current = $this->functions->findByIdFull($id);
        if (!$current) {
            return ['success' => false, 'errors' => ['general' => 'Fonction introuvable.']];
        }

        $companyId = $current['fon_societe_proprietaire_id'] ? (int) $current['fon_societe_proprietaire_id'] : null;
        try {
            $errors = $this->validate($data, $companyId, $id);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'fonction_validate_update_failed', ['error' => $e->getMessage(), 'fon_id' => $id]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la validation de la fonction.']];
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->functions->updateFunction($id, [
                'code' => strtoupper(trim((string) ($data['fon_code'] ?? $data['fnc_code'] ?? $data['job_code'] ?? $data['code']))),
                'name' => trim((string) ($data['fon_nom'] ?? $data['fnc_label'] ?? $data['job_label'] ?? $data['label'] ?? $data['name'])),
                'description' => trim((string) ($data['fon_description'] ?? $data['fnc_description'] ?? $data['job_description'] ?? $data['description'] ?? '')) ?: null,
                'company_type_id' => $this->nullableInt($data['fon_type_societe_id'] ?? $data['fnc_company_type_id'] ?? $data['company_type_id'] ?? null),
                'scope_code' => $data['fon_portee_code'] ?? $data['fnc_scope_code'] ?? $current['fon_portee_code'] ?? $current['fnc_scope_code'] ?? 'interne',
                'updated_by' => $updatedBy > 0 ? $updatedBy : null,
            ]);
        } catch (\Throwable $e) {
            $this->safeLog('database', 'error', 'fonction_update_failed', ['error' => $e->getMessage(), 'fon_id' => $id]);
            return ['success' => false, 'errors' => ['general' => 'Erreur technique pendant la mise a jour de la fonction.']];
        }

        $this->safeLog('audit', 'info', 'fonction_updated', ['fon_id' => $id, 'updated_by' => $updatedBy]);

        return ['success' => true, 'errors' => []];
    }

    public function setActive(int $id, bool $active, int $operatorId): array
    {
        $this->functions->setActive($id, $active, $operatorId);
        $this->safeLog('audit', 'info', 'fonction_status_changed', ['fon_id' => $id, 'active' => $active, 'operator_id' => $operatorId]);
        return ['success' => true, 'errors' => []];
    }

    public function getUserFunctions(int $userId): array
    {
        return $this->userFunctions->getForUser($userId);
    }

    public function assignToUser(int $userId, int $functionId, ?int $companyId, bool $isPrimary, int $createdBy): array
    {
        if ($userId <= 0 || $functionId <= 0) {
            return ['success' => false, 'errors' => ['general' => 'Utilisateur ou fonction invalide.']];
        }
        if ($this->userFunctions->hasFunction($userId, $functionId, $companyId)) {
            return ['success' => false, 'errors' => ['general' => 'Fonction deja attribuee.']];
        }
        $this->userFunctions->assign($userId, $functionId, $companyId, $isPrimary, $createdBy);
        return ['success' => true, 'errors' => []];
    }

    public function unassignFromUser(int $userId, int $functionId): array
    {
        $this->userFunctions->unassign($userId, $functionId);
        return ['success' => true, 'errors' => []];
    }

    public function syncUserFunctions(int $userId, array $functionIds, int $primaryFunctionId, int $createdBy, ?int $companyId = null): array
    {
        $functionIds = array_values(array_unique(array_filter(array_map('intval', $functionIds), static fn(int $id): bool => $id > 0)));
        $this->userFunctions->sync($userId, $functionIds, $primaryFunctionId, $createdBy, $companyId);
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
