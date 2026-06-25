<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Skills\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Affectations de competences aux utilisateurs.
 * Source SQL unique : sav_competences_utilisateurs.
 */
class UserSkillModel extends BaseModel
{
    protected string $table = 'sav_competences_utilisateurs';
    protected string $colPrefix = 'cut_';

    public function getForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT
                    cu.*,
                    cu.cut_id AS usk_id,
                    cu.cut_utilisateur_id AS usk_user_id,
                    cu.cut_competence_id AS usk_skill_id,
                    cu.cut_niveau_competence_id AS usk_level_id,
                    cu.cut_societe_id AS usk_company_id,
                    cu.cut_marque_societe_id AS usk_brand_company_id,
                    cu.cut_debute_le AS usk_started_at,
                    cu.cut_termine_le AS usk_ended_at,
                    cu.cut_statut_id AS usk_status_id,
                    c.cmp_id AS skl_id,
                    c.cmp_code AS skl_code,
                    c.cmp_nom AS skl_name,
                    c.cmp_nom AS skl_label,
                    c.cmp_description AS skl_description,
                    n.nco_code AS usk_level_code,
                    n.nco_nom AS usk_level,
                    n.nco_nom AS skill_level_name,
                    n.nco_rang AS usk_level_rank,
                    s.soc_nom AS company_name,
                    b.soc_nom AS brand_name
             FROM sav_competences_utilisateurs cu
             INNER JOIN sav_competences c ON c.cmp_id = cu.cut_competence_id
             LEFT JOIN sav_niveaux_competences n ON n.nco_id = cu.cut_niveau_competence_id
             LEFT JOIN sav_societes s ON s.soc_id = cu.cut_societe_id
             LEFT JOIN sav_societes b ON b.soc_id = cu.cut_marque_societe_id
             WHERE cu.cut_utilisateur_id = :user_id
               AND cu.cut_supprime_le IS NULL
               AND cu.cut_archive_le IS NULL
               AND c.cmp_supprime_le IS NULL
               AND c.cmp_archive_le IS NULL
             ORDER BY c.cmp_nom ASC",
            ['user_id' => $userId]
        );
    }

    public function levels(): array
    {
        return $this->db->fetchAll(
            "SELECT nco_id, nco_code, nco_nom, nco_rang
             FROM sav_niveaux_competences
             WHERE nco_supprime_le IS NULL
             ORDER BY nco_rang ASC, nco_nom ASC"
        );
    }
}
