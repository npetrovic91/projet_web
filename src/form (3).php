<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Services;

use Nenad\Autosav\Modules\Brands\Services\BrandService;
use Nenad\Autosav\Modules\Companies\Models\CompanyModel;
use Nenad\Autosav\Modules\Companies\Models\CompanyRelationModel;
use Nenad\Autosav\Modules\Companies\Models\CompanyTypeModel;

class CompanyService
{
    private CompanyModel $companyModel;
    private CompanyTypeModel $typeModel;
    private CompanyRelationModel $relationModel;
    private BrandService $brandService;

    public function __construct()
    {
        $this->companyModel = new CompanyModel();
        $this->typeModel = new CompanyTypeModel();
        $this->relationModel = new CompanyRelationModel();
        $this->brandService = new BrandService();
    }

    public function listCompanies(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        return $this->companyModel->paginate($filters, $page, $perPage);
    }

    public function getCompanyFull(int $id): ?array
    {
        $company = $this->companyModel->findFull($id);
        if (!$company) {
            return null;
        }
        return [
            'company' => $company,
            'relations' => $this->relationModel->getAllForCompany($id),
            'brands' => $this->brandService->getForCompany($id),
        ];
    }

    public function getTypes(): array { return $this->typeModel->getAllActive(); }
    public function getHoldings(): array { return $this->companyModel->byTypeCode('HOLDING'); }
    public function getConstructeurs(): array { return $this->companyModel->byTypeCode('CONSTRUCTEUR'); }
    public function getImportateurs(): array { return $this->companyModel->byTypeCode('IMPORTATEUR'); }

    public function saveCompany(?int $id, array $input, int $userId): array
    {
        $errors = $this->validate($input);
        if ($errors !== []) {
            return ['success' => false, 'id' => $id, 'errors' => $errors];
        }

        $data = [
            ':uuid' => generate_uuid(),
            ':type_id' => (int) $input['com_type_id'],
            ':holding_id' => !empty($input['com_holding_id']) ? (int) $input['com_holding_id'] : null,
            ':name' => trim((string) $input['com_name']),
            ':legal_name' => trim((string) ($input['com_legal_name'] ?? '')) ?: null,
            ':siret' => trim((string) ($input['com_siret'] ?? '')) ?: null,
            ':address' => trim((string) ($input['com_address'] ?? '')) ?: null,
            ':zipcode' => trim((string) ($input['com_zipcode'] ?? '')) ?: null,
            ':city' => trim((string) ($input['com_city'] ?? '')) ?: null,
            ':country' => trim((string) ($input['com_country'] ?? 'France')) ?: 'France',
            ':phone' => trim((string) ($input['com_phone'] ?? '')) ?: null,
            ':email' => trim((string) ($input['com_email'] ?? '')) ?: null,
            ':status' => trim((string) ($input['com_status'] ?? 'active')) ?: 'active',
            ':active' => isset($input['com_is_active']) ? (int) $input['com_is_active'] : 1,
            ':created_by' => $userId,
        ];

        if ($id) {
            unset($data[':uuid']);
            $this->companyModel->updateCompany($id, $data);
            return ['success' => true, 'id' => $id, 'errors' => []];
        }

        return ['success' => true, 'id' => $this->companyModel->create($data), 'errors' => []];
    }

    public function deleteCompany(int $id, int $userId): bool
    {
        return $this->companyModel->softDeleteCompany($id, $userId);
    }

    public function addRelation(int $parentId, int $childId, string $type, int $userId): array
    {
        if ($parentId === $childId) {
            return ['success' => false, 'message' => 'Une entreprise ne peut pas etre liee a elle-meme.'];
        }
        if ($this->relationModel->exists($parentId, $childId, $type)) {
            return ['success' => false, 'message' => 'Cette relation existe deja.'];
        }
        $this->relationModel->create($parentId, $childId, $type, $userId);
        return ['success' => true, 'message' => 'Relation creee.'];
    }

    public function removeRelation(int $id, int $userId): bool
    {
        return $this->relationModel->deactivate($id, $userId);
    }

    private function validate(array $input): array
    {
        $errors = [];
        if (empty($input['com_type_id'])) {
            $errors['com_type_id'] = 'Le type est obligatoire.';
        }
        if (trim((string) ($input['com_name'] ?? '')) === '') {
            $errors['com_name'] = 'Le nom est obligatoire.';
        }
        if (!empty($input['com_email']) && !filter_var($input['com_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['com_email'] = 'Email invalide.';
        }
        return $errors;
    }
}
