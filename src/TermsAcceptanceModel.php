<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Functions\Services;

use Nenad\Autosav\Modules\Functions\Models\FunctionModel;
use Nenad\Autosav\Modules\Functions\Models\UserFunctionModel;

class FunctionService
{
    public function __construct(
        private FunctionModel $functions,
        private UserFunctionModel $userFunctions
    ) {}

    public function getForContext(?int $companyId = null, bool $adminView = false): array
    {
        return $this->functions->getForContext($companyId, $adminView);
    }

    public function search(string $query, ?int $companyId = null): array
    {
        return $this->functions->search($query, $companyId);
    }

    public function getById(int $id, ?int $companyId = null): ?array
    {
        $function = $this->functions->findByIdFull($id);
        if (!$function) {
            return null;
        }
        if (!$function['fnc_is_global'] && $companyId !== null && (int) $function['fnc_company_id'] !== $companyId) {
            return null;
        }
        return $function;
    }

    public function validate(array $data, ?int $companyId, ?int $excludeId = null): array
    {
        $errors = [];
        $code = strtoupper(trim((string) ($data['fnc_code'] ?? $data['code'] ?? '')));
        $label = trim((string) ($data['fnc_label'] ?? $data['label'] ?? ''));

        if ($code === '' || !preg_match('/^[A-Z0-9_]{2,80}$/', $code)) {
            $errors['fnc_code'] = 'Le code doit contenir 2 a 80 caracteres A-Z, 0-9 ou _.';
        } elseif ($this->functions->codeExists($code, $companyId, $excludeId)) {
            $errors['fnc_code'] = 'Ce code existe deja dans cette portee.';
        }
        if ($label === '' || strlen($label) > 150) {
            $errors['fnc_label'] = 'Le libelle est obligatoire et limite a 150 caracteres.';
        }

        return $errors;
    }

    public function create(array $data, ?int $companyId, int $createdBy, bool $isGlobal): array
    {
        $targetCompanyId = $isGlobal ? null : $companyId;
        $errors = $this->validate($data, $targetCompanyId);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $id = $this->functions->createFunction([
            'uuid' => $this->uuid(),
            'code' => strtoupper(trim((string) ($data['fnc_code'] ?? $data['code']))),
            'label' => trim((string) ($data['fnc_label'] ?? $data['label'])),
            'description' => trim((string) ($data['fnc_description'] ?? $data['description'] ?? '')) ?: null,
            'company_id' => $targetCompanyId,
            'is_global' => $isGlobal,
            'created_by' => $createdBy,
        ]);

        logger('audit')->info('function_created', ['function_id' => $id, 'created_by' => $createdBy]);
        return ['success' => true, 'id' => $id, 'errors' => []];
    }

    public function update(int $id, array $data, int $updatedBy): array
    {
        $current = $this->functions->findByIdFull($id);
        if (!$current) {
            return ['success' => false, 'errors' => ['general' => 'Fonction introuvable.']];
        }
        $errors = $this->validate($data, $current['fnc_company_id'] ? (int) $current['fnc_company_id'] : null, $id);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->functions->updateFunction($id, [
            'code' => strtoupper(trim((string) ($data['fnc_code'] ?? $data['code']))),
            'label' => trim((string) ($data['fnc_label'] ?? $data['label'])),
            'description' => trim((string) ($data['fnc_description'] ?? $data['description'] ?? '')) ?: null,
            'updated_by' => $updatedBy,
        ]);

        logger('audit')->info('function_updated', ['function_id' => $id, 'updated_by' => $updatedBy]);
        return ['success' => true, 'errors' => []];
    }

    public function setActive(int $id, bool $active, int $operatorId): array
    {
        $this->functions->setActive($id, $active, $operatorId);
        logger('audit')->info('function_status_changed', ['function_id' => $id, 'active' => $active, 'operator_id' => $operatorId]);
        return ['success' => true, 'errors' => []];
    }

    public function getUserFunctions(int $userId): array
    {
        return $this->userFunctions->getForUser($userId);
    }

    public function assignToUser(int $userId, int $functionId, ?int $companyId, bool $isPrimary, int $createdBy): array
    {
        if ($this->userFunctions->hasFunction($userId, $functionId, $companyId)) {
            return ['success' => false, 'errors' => ['general' => 'Fonction deja attribuee.']];
        }
        $this->userFunctions->assign($userId, $functionId, $companyId, $isPrimary, $createdBy);
        logger('audit')->info('user_function_assigned', ['user_id' => $userId, 'function_id' => $functionId, 'created_by' => $createdBy]);
        return ['success' => true, 'errors' => []];
    }

    public function unassignFromUser(int $userId, int $functionId): array
    {
        $this->userFunctions->unassign($userId, $functionId);
        return ['success' => true, 'errors' => []];
    }

    public function syncUserFunctions(int $userId, array $functionIds, int $primaryFunctionId, int $createdBy): array
    {
        $functionIds = array_values(array_unique(array_map('intval', $functionIds)));
        if ($functionIds !== [] && !in_array($primaryFunctionId, $functionIds, true)) {
            $primaryFunctionId = $functionIds[0];
        }
        $this->userFunctions->sync($userId, $functionIds, $primaryFunctionId, $createdBy);
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
