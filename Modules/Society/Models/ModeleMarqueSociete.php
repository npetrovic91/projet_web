<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Society\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Représentation marque / société.
 * Dans le modèle cible, une marque est une société typée `marque`.
 * Liaison réelle : sav_representations_marques_societes.
 */
class ModeleMarqueSociete extends BaseModel
{
    protected string $table = 'sav_representations_marques_societes';
    protected string $colPrefix = 'rma_';

    public function marquesDeSociete(int $idSociete): array
    {
        return $this->db->fetchAll(
            "SELECT
                m.*,
                r.rma_id AS cbr_id,
                r.rma_id AS representation_id,
                0 AS cbr_is_primary,
                IF(r.rma_supprime_le IS NULL AND r.rma_archive_le IS NULL AND (r.rma_termine_le IS NULL OR r.rma_termine_le >= CURDATE()), 1, 0) AS cbr_is_active,
                r.rma_cree_le AS cbr_created_at,
                m.soc_id AS brd_id,
                m.soc_uuid AS brd_uuid,
                m.soc_code AS brd_code,
                m.soc_nom AS brd_name,
                NULL AS brd_logo_url,
                IF(m.soc_statut_id = 1 AND m.soc_supprime_le IS NULL AND m.soc_archive_le IS NULL, 1, 0) AS brd_is_active,
                m.soc_supprime_le AS brd_deleted_at
             FROM sav_representations_marques_societes r
             INNER JOIN sav_societes m ON m.soc_id = r.rma_marque_societe_id
             WHERE r.rma_concession_societe_id = :societe
               AND r.rma_supprime_le IS NULL
               AND r.rma_archive_le IS NULL
               AND (r.rma_termine_le IS NULL OR r.rma_termine_le >= CURDATE())
               AND m.soc_supprime_le IS NULL
             ORDER BY m.soc_nom ASC",
            ['societe' => $idSociete]
        );
    }

    public function marquesDisponibles(): array
    {
        return $this->db->fetchAll(
            $this->selectMarquesSql('ORDER BY s.soc_nom ASC')
        );
    }

    public function attacher(int $idSociete, int $idMarque, int $idUtilisateur, bool $principale = false): bool
    {
        $existe = $this->db->fetch(
            "SELECT rma_id FROM sav_representations_marques_societes
             WHERE rma_concession_societe_id = :societe
               AND rma_marque_societe_id = :marque
               AND rma_supprime_le IS NULL
             LIMIT 1",
            ['societe' => $idSociete, 'marque' => $idMarque]
        );

        if ($existe) {
            return $this->db->execute(
                "UPDATE sav_representations_marques_societes
                 SET rma_termine_le = NULL,
                     rma_statut_id = 1,
                     rma_archive_le = NULL,
                     rma_modifie_le = NOW(),
                     rma_modifie_par_utilisateur_id = :user_id
                 WHERE rma_id = :id",
                ['id' => (int) $existe['rma_id'], 'user_id' => $idUtilisateur ?: null]
            );
        }

        return $this->db->execute(
            "INSERT INTO sav_representations_marques_societes
             (rma_concession_societe_id, rma_marque_societe_id, rma_debute_le, rma_statut_id,
              rma_cree_le, rma_cree_par_utilisateur_id)
             VALUES (:societe, :marque, CURDATE(), 1, NOW(), :user_id)",
            ['societe' => $idSociete, 'marque' => $idMarque, 'user_id' => $idUtilisateur ?: null]
        );
    }

    public function detacher(int $idSociete, int $idMarque, int $idUtilisateur): bool
    {
        return $this->db->execute(
            "UPDATE sav_representations_marques_societes
             SET rma_termine_le = COALESCE(rma_termine_le, CURDATE()),
                 rma_statut_id = 4,
                 rma_archive_le = NOW(),
                 rma_modifie_le = NOW(),
                 rma_modifie_par_utilisateur_id = :user_id,
                 rma_archive_par_utilisateur_id = :user_id
             WHERE rma_concession_societe_id = :societe
               AND rma_marque_societe_id = :marque
               AND rma_supprime_le IS NULL",
            ['societe' => $idSociete, 'marque' => $idMarque, 'user_id' => $idUtilisateur ?: null]
        );
    }

    private function selectMarquesSql(string $suffixe): string
    {
        return "SELECT
                s.*,
                s.soc_id AS brd_id,
                s.soc_uuid AS brd_uuid,
                s.soc_code AS brd_code,
                s.soc_nom AS brd_name,
                NULL AS brd_logo_url,
                IF(s.soc_statut_id = 1 AND s.soc_supprime_le IS NULL AND s.soc_archive_le IS NULL, 1, 0) AS brd_is_active,
                s.soc_supprime_le AS brd_deleted_at
             FROM sav_societes s
             WHERE s.soc_supprime_le IS NULL
               AND s.soc_archive_le IS NULL
               AND EXISTS (
                   SELECT 1
                   FROM sav_affectations_types_societes ats
                   INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                   WHERE ats.ats_societe_id = s.soc_id
                     AND ats.ats_supprime_le IS NULL
                     AND ats.ats_archive_le IS NULL
                     AND LOWER(t.tso_code) = 'marque'
               )
             {$suffixe}";
    }
}
