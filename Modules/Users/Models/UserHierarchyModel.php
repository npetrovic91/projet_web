<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

/** Compatibilité : ancienne classe mappée sur sav_hierarchie_utilisateurs. */
class UserHierarchyModel extends UserModel
{
    public function managers(int $userId): array
    {
        return $this->managersUtilisateur($userId);
    }

    public function subordinates(int $userId, ?int $companyId = null): array
    {
        return $this->subordonnesUtilisateur($userId, $companyId);
    }
}
