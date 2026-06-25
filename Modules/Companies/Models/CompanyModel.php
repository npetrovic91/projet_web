<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Models;

use Nenad\Autosav\Modules\Society\Models\ModeleSociete;

class CompanyModel extends ModeleSociete
{
    public function paginate(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $mapped = [
            'type' => $filters['type_code'] ?? $filters['type'] ?? '',
            'recherche' => $filters['search'] ?? $filters['recherche'] ?? '',
            'active' => $filters['is_active'] ?? $filters['active'] ?? '',
        ];
        $result = $this->paginer($mapped, $page, $perPage);
        return ['rows' => $result['lignes'], 'total' => $result['total'], 'page' => $result['page'], 'pages' => $result['pages'], 'perPage' => $result['par_page']];
    }

    public function findFull(int $id): ?array
    {
        return $this->trouver($id);
    }

    public function create(array $data): int
    {
        return $this->creer($data);
    }

    public function updateCompany(int $id, array $data): bool
    {
        return $this->modifier($id, $data);
    }

    public function softDeleteCompany(int $id, int $userId): bool
    {
        return $this->supprimerLogiquement($id, $userId);
    }

    public function byTypeCode(string $code): array
    {
        return $this->toutesActives($code);
    }
}
