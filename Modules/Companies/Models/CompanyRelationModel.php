<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Models;

use Nenad\Autosav\Modules\Society\Models\ModeleRelationSociete;

class CompanyRelationModel extends ModeleRelationSociete
{
    public function getAllForCompany(int $companyId): array
    {
        return $this->relationsDeSociete($companyId);
    }

    public function exists(int $parentId, int $childId, string $type): bool
    {
        return $this->existe($parentId, $childId, $type);
    }

    public function create(int $parentId, int $childId, string $type, int $userId): int
    {
        return $this->creer($parentId, $childId, $type, $userId);
    }

    public function deactivate(int $id, int $userId): bool
    {
        return $this->desactiver($id, $userId);
    }
}
