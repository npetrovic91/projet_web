<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Jobs\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Compatibilite : l'ancienne liaison metier/type de societe n'existe pas.
 * La cible SQL actuelle est fon_type_societe_id dans sav_fonctions.
 */
class JobCompanyTypeModel extends BaseModel
{
    protected string $table = 'sav_fonctions';
    protected string $colPrefix = 'fon_';

    public function syncTypes(int $jobId, array $typeIds, int $createdBy): void
    {
        $typeId = null;
        foreach (array_values(array_unique(array_map('intval', $typeIds))) as $candidate) {
            if ($candidate > 0) {
                $typeId = $candidate;
                break;
            }
        }

        $this->db->execute(
            'UPDATE sav_fonctions
             SET fon_type_societe_id = :type_id,
                 fon_modifie_par_utilisateur_id = :updated_by
             WHERE fon_id = :job_id
               AND fon_supprime_le IS NULL
               AND fon_archive_le IS NULL',
            ['job_id' => $jobId, 'type_id' => $typeId, 'updated_by' => $createdBy]
        );
    }

    public function isAllowedForType(int $jobId, int $companyTypeId): bool
    {
        return (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM sav_fonctions
             WHERE fon_id = :job_id
               AND (fon_type_societe_id IS NULL OR fon_type_societe_id = :type_id)
               AND fon_supprime_le IS NULL
               AND fon_archive_le IS NULL',
            ['job_id' => $jobId, 'type_id' => $companyTypeId]
        ) > 0;
    }
}
