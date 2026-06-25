<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

/** Compatibilité : ancienne classe mappée sur sav_roles_contextuels_utilisateurs. */
class UserRoleModel extends UserModel
{
    public function forUser(int $userId): array
    {
        return $this->rolesUtilisateur($userId);
    }
}
