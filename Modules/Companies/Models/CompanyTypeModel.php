<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Models;

use Nenad\Autosav\Modules\Society\Models\ModeleTypeSociete;

class CompanyTypeModel extends ModeleTypeSociete
{
    public function getAllActive(): array
    {
        return $this->tousActifs();
    }

    public function findByCode(string $code): ?array
    {
        return $this->trouverParCode($code);
    }
}
