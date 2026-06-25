<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Brands\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Brands\Models\BrandModel;

class BrandService implements ServiceInterface{
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

    public function find(int $id): ?array
    {
        return $this->brandModel->find($id);
    }

    public function save(?int $id, array $input, int $userId): array
    {
        $code = strtoupper(trim((string) ($input['brd_code'] ?? $input['soc_code'] ?? '')));
        $name = trim((string) ($input['brd_name'] ?? $input['soc_nom'] ?? ''));
        if ($code === '' || $name === '') {
            return ['success' => false, 'errors' => ['code_name' => 'Code et nom sont obligatoires.']];
        }

        $data = [
            'uuid' => $this->uuidV4(),
            'code' => substr($code, 0, 30),
            'name' => $name,
            'statut_id' => isset($input['brd_is_active']) && (int) $input['brd_is_active'] === 0 ? 2 : 1,
            'user_id' => $userId ?: null,
        ];

        if ($id) {
            unset($data['uuid']);
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

    private function uuidV4(): string
    {
        if (function_exists('generate_uuid')) {
            return (string) generate_uuid();
        }
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
