<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Brands\Services;

use Nenad\Autosav\Modules\Brands\Models\BrandModel;

class BrandService
{
    private BrandModel $brandModel;

    public function __construct()
    {
        $this->brandModel = new BrandModel();
    }

    public function listBrands(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        return $this->brandModel->paginate($filters, $page, $perPage);
    }

    public function allActive(): array
    {
        return $this->brandModel->allActive();
    }

    public function getForCompany(int $companyId): array
    {
        return $this->brandModel->getBrandsForCompany($companyId);
    }

    public function save(?int $id, array $input, int $userId): array
    {
        $code = strtoupper(trim((string) ($input['brd_code'] ?? '')));
        $name = trim((string) ($input['brd_name'] ?? ''));
        if ($code === '' || $name === '') {
            return ['success' => false, 'errors' => ['code_name' => 'Code et nom sont obligatoires.']];
        }

        $data = [
            ':uuid' => generate_uuid(),
            ':code' => $code,
            ':name' => $name,
            ':logo_url' => trim((string) ($input['brd_logo_url'] ?? '')) ?: null,
            ':active' => isset($input['brd_is_active']) ? (int) $input['brd_is_active'] : 1,
            ':user_id' => $userId,
        ];

        if ($id) {
            unset($data[':uuid']);
            $this->brandModel->updateBrand($id, $data);
            return ['success' => true, 'id' => $id, 'errors' => []];
        }

        return ['success' => true, 'id' => $this->brandModel->create($data), 'errors' => []];
    }

    public function attachToCompany(int $companyId, int $brandId, int $userId, bool $primary = false): bool
    {
        return $this->brandModel->attachToCompany($companyId, $brandId, $userId, $primary);
    }

    public function detachFromCompany(int $companyId, int $brandId, int $userId): bool
    {
        return $this->brandModel->detachFromCompany($companyId, $brandId, $userId);
    }
}
