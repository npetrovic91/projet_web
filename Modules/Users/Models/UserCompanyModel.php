<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

/** Compatibilité : ancienne classe mappée sur sav_adhesions_utilisateurs_societes. */
class UserCompanyModel extends UserModel
{
    public function forUser(int $userId): array
    {
        return $this->societesUtilisateur($userId);
    }
}
