<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Society\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Relations entre sociétés.
 * Aligné sur sav_relations_societes + sav_types_relations_societes.
 */
class ModeleRelationSociete extends BaseModel
{
    protected string $table = 'sav_relations_societes';
    protected string $colPrefix = 'rso_';

    public function relationsDeSociete(int $idSociete): array
    {
        return $this->db->fetchAll(
            "SELECT
                r.*,
                r.rso_id AS cor_id,
                r.rso_societe_source_id AS cor_parent_company_id,
                r.rso_societe_cible_id AS cor_child_company_id,
                tr.tre_code AS cor_relation_type,
                tr.tre_code AS crl_type,
                IF(r.rso_supprime_le IS NULL AND r.rso_archive_le IS NULL AND (r.rso_termine_le IS NULL OR r.rso_termine_le >= CURDATE()), 1, 0) AS cor_is_active,
                p.soc_nom AS parent_nom,
                e.soc_nom AS enfant_nom,
                p.soc_nom AS parent_name,
                e.soc_nom AS child_name
             FROM sav_relations_societes r
             INNER JOIN sav_societes p ON p.soc_id = r.rso_societe_source_id
             INNER JOIN sav_societes e ON e.soc_id = r.rso_societe_cible_id
             LEFT JOIN sav_types_relations_societes tr ON tr.tre_id = r.rso_type_relation_societe_id
             WHERE (r.rso_societe_source_id = :id1 OR r.rso_societe_cible_id = :id2)
               AND r.rso_supprime_le IS NULL
               AND r.rso_archive_le IS NULL
             ORDER BY r.rso_cree_le DESC",
            ['id1' => $idSociete, 'id2' => $idSociete]
        );
    }

    public function enfants(int $idSociete): array
    {
        return $this->db->fetchAll(
            "SELECT
                e.*,
                e.soc_id AS com_id,
                e.soc_nom AS com_name,
                e.soc_nom_legal AS com_legal_name,
                e.soc_nom_court AS com_short_name,
                e.soc_ville AS com_city,
                e.soc_supprime_le AS com_deleted_at
             FROM sav_relations_societes r
             INNER JOIN sav_societes e ON e.soc_id = r.rso_societe_cible_id
             WHERE r.rso_societe_source_id = :id
               AND r.rso_supprime_le IS NULL
               AND r.rso_archive_le IS NULL
               AND e.soc_supprime_le IS NULL
             ORDER BY e.soc_nom ASC",
            ['id' => $idSociete]
        );
    }

    public function existe(int $idParent, int $idEnfant, string $type): bool
    {
        $ligne = $this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM sav_relations_societes r
             INNER JOIN sav_types_relations_societes tr ON tr.tre_id = r.rso_type_relation_societe_id
             WHERE r.rso_societe_source_id = :parent
               AND r.rso_societe_cible_id = :enfant
               AND LOWER(tr.tre_code) = LOWER(:type)
               AND r.rso_supprime_le IS NULL
               AND r.rso_archive_le IS NULL
               AND (r.rso_termine_le IS NULL OR r.rso_termine_le >= CURDATE())",
            ['parent' => $idParent, 'enfant' => $idEnfant, 'type' => trim($type)]
        );
        return (int) ($ligne['total'] ?? 0) > 0;
    }

    public function creer(int $idParent, int $idEnfant, string $type, int $idUtilisateur): int
    {
        $idType = $this->trouverOuCreerTypeRelation($type, $idUtilisateur);
        $this->db->execute(
            "INSERT INTO sav_relations_societes
             (rso_societe_source_id, rso_societe_cible_id, rso_type_relation_societe_id, rso_debute_le,
              rso_statut_id, rso_cree_le, rso_cree_par_utilisateur_id)
             VALUES (:parent, :enfant, :type_id, CURDATE(), 1, NOW(), :user_id)",
            ['parent' => $idParent, 'enfant' => $idEnfant, 'type_id' => $idType, 'user_id' => $idUtilisateur ?: null]
        );
        return (int) $this->db->lastInsertId();
    }

    public function desactiver(int $idRelation, int $idUtilisateur): bool
    {
        return $this->db->execute(
            "UPDATE sav_relations_societes
             SET rso_termine_le = COALESCE(rso_termine_le, CURDATE()),
                 rso_statut_id = 4,
                 rso_archive_le = NOW(),
                 rso_modifie_le = NOW(),
                 rso_modifie_par_utilisateur_id = :user_id,
                 rso_archive_par_utilisateur_id = :user_id
             WHERE rso_id = :id
               AND rso_supprime_le IS NULL",
            ['id' => $idRelation, 'user_id' => $idUtilisateur ?: null]
        );
    }

    /**
     * Importateur actuellement rattaché à une concession (relation
     * 'importateur_distribue_concession' active), ou null si aucun.
     * Cahier des charges : "Chaque concession doit avoir un
     * importateur_id obligatoire."
     */
    public function importateurDeConcession(int $idConcession): ?int
    {
        $ligne = $this->db->fetch(
            "SELECT r.rso_societe_source_id AS importateur_id
             FROM sav_relations_societes r
             INNER JOIN sav_types_relations_societes tr ON tr.tre_id = r.rso_type_relation_societe_id
             WHERE r.rso_societe_cible_id = :concession
               AND LOWER(tr.tre_code) = LOWER('importateur_distribue_concession')
               AND r.rso_supprime_le IS NULL
               AND r.rso_archive_le IS NULL
               AND (r.rso_termine_le IS NULL OR r.rso_termine_le >= CURDATE())
             ORDER BY r.rso_cree_le DESC
             LIMIT 1",
            ['concession' => $idConcession]
        );
        return $ligne ? (int) $ligne['importateur_id'] : null;
    }

    /**
     * Pose ou met à jour la relation obligatoire importateur->concession.
     * Si un autre importateur était déjà rattaché, l'ancienne relation est
     * désactivée (historisée) avant la création de la nouvelle.
     */
    public function definirImportateur(int $idConcession, int $idImportateur, int $idUtilisateur): void
    {
        $actuel = $this->importateurDeConcession($idConcession);
        if ($actuel === $idImportateur) {
            return;
        }

        if ($actuel !== null) {
            $ancienne = $this->db->fetch(
                "SELECT r.rso_id
                 FROM sav_relations_societes r
                 INNER JOIN sav_types_relations_societes tr ON tr.tre_id = r.rso_type_relation_societe_id
                 WHERE r.rso_societe_cible_id = :concession
                   AND r.rso_societe_source_id = :importateur
                   AND LOWER(tr.tre_code) = LOWER('importateur_distribue_concession')
                   AND r.rso_supprime_le IS NULL
                 LIMIT 1",
                ['concession' => $idConcession, 'importateur' => $actuel]
            );
            if ($ancienne) {
                $this->desactiver((int) $ancienne['rso_id'], $idUtilisateur);
            }
        }

        $this->creer($idImportateur, $idConcession, 'importateur_distribue_concession', $idUtilisateur);
    }

    private function trouverOuCreerTypeRelation(string $type, int $idUtilisateur): int
    {
        $code = strtolower(trim($type)) ?: 'relation';
        $ligne = $this->db->fetch(
            "SELECT tre_id FROM sav_types_relations_societes
             WHERE LOWER(tre_code) = LOWER(:code)
               AND tre_supprime_le IS NULL
             LIMIT 1",
            ['code' => $code]
        );
        if ($ligne) {
            return (int) $ligne['tre_id'];
        }

        $nom = ucfirst(str_replace('_', ' ', $code));
        $this->db->execute(
            "INSERT INTO sav_types_relations_societes
             (tre_code, tre_nom, tre_description, tre_est_directionnel, tre_statut_id, tre_cree_le, tre_cree_par_utilisateur_id)
             VALUES (:code, :nom, :description, 1, 1, NOW(), :user_id)",
            [
                'code' => $code,
                'nom' => $nom,
                'description' => 'Type de relation créé automatiquement par le module Sociétés aligné sur le modèle SQL courant.',
                'user_id' => $idUtilisateur ?: null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }
}
