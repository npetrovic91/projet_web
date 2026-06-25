<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

/** Compatibilité : l'historique est porté par les lignes archivées dans sav_adhesions_utilisateurs_societes. */
class UserCompanyHistoryModel extends UserModel
{
    public function forUser(int $userId): array
    {
        return $this->societesUtilisateur($userId);
    }
}
