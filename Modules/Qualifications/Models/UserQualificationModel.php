<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Qualifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Certifications affectees aux utilisateurs.
 * Source SQL unique : sav_certifications_utilisateurs.
 */
class UserQualificationModel extends BaseModel
{
    protected string $table = 'sav_certifications_utilisateurs';
    protected string $colPrefix = 'ceu_';

    public function getForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT
                    cu.*,
                    cu.ceu_id AS uql_id,
                    cu.ceu_id AS uqu_id,
                    cu.ceu_utilisateur_id AS uql_user_id,
                    cu.ceu_certification_id AS uql_qualification_id,
                    cu.ceu_societe_id AS uql_company_id,
                    cu.ceu_marque_societe_id AS uql_brand_company_id,
                    cu.ceu_delivree_par_societe_id AS uql_issuer_company_id,
                    cu.ceu_delivree_le AS uql_issued_at,
                    cu.ceu_expire_le AS uql_expires_at,
                    cu.ceu_expire_le AS uqu_expires_at,
                    cu.ceu_statut_id AS uql_status_id,
                    c.cer_id AS qua_id,
                    c.cer_code AS qua_code,
                    c.cer_nom AS qua_name,
                    c.cer_nom AS qua_label,
                    c.cer_description AS qua_description,
                    c.cer_prerequis_json AS qua_prerequisites_json,
                    s.soc_nom AS company_name,
                    b.soc_nom AS brand_name,
                    i.soc_nom AS issuer_company_name
             FROM sav_certifications_utilisateurs cu
             INNER JOIN sav_certifications c ON c.cer_id = cu.ceu_certification_id
             LEFT JOIN sav_societes s ON s.soc_id = cu.ceu_societe_id
             LEFT JOIN sav_societes b ON b.soc_id = cu.ceu_marque_societe_id
             LEFT JOIN sav_societes i ON i.soc_id = cu.ceu_delivree_par_societe_id
             WHERE cu.ceu_utilisateur_id = :user_id
               AND cu.ceu_supprime_le IS NULL
               AND cu.ceu_archive_le IS NULL
               AND c.cer_supprime_le IS NULL
               AND c.cer_archive_le IS NULL
             ORDER BY cu.ceu_expire_le IS NULL ASC, cu.ceu_expire_le ASC, c.cer_nom ASC",
            ['user_id' => $userId]
        );
    }
}
