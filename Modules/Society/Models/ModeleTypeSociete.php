<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Society\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Référentiel des types de sociétés.
 * Aligné sur la base SQL actuelle : sav_types_societes.
 */
class ModeleTypeSociete extends BaseModel
{
    protected string $table = 'sav_types_societes';
    protected string $colPrefix = 'tso_';

    public function tousActifs(): array
    {
        return $this->db->fetchAll(
            "SELECT
                t.*,
                t.tso_id AS cty_id,
                t.tso_code AS cty_code,
                t.tso_nom AS cty_label,
                t.tso_nom AS cty_name,
                100 AS cty_sort_order,
                IF(t.tso_statut_id = 1 AND t.tso_supprime_le IS NULL AND t.tso_archive_le IS NULL, 1, 0) AS cty_is_active
             FROM sav_types_societes t
             WHERE t.tso_supprime_le IS NULL
               AND t.tso_archive_le IS NULL
               AND (t.tso_statut_id IS NULL OR t.tso_statut_id = 1)
             ORDER BY t.tso_nom ASC"
        );
    }

    public function trouverParCode(string $code): ?array
    {
        return $this->db->fetch(
            "SELECT
                t.*,
                t.tso_id AS cty_id,
                t.tso_code AS cty_code,
                t.tso_nom AS cty_label,
                t.tso_nom AS cty_name
             FROM sav_types_societes t
             WHERE LOWER(t.tso_code) = LOWER(:code)
               AND t.tso_supprime_le IS NULL
             LIMIT 1",
            ['code' => trim($code)]
        );
    }

    public function trouverOuCreerParCode(string $code, string $nom, int $idUtilisateur = 0): int
    {
        $code = strtolower(trim($code));
        $existant = $this->trouverParCode($code);
        if ($existant) {
            return (int) $existant['tso_id'];
        }

        $this->db->execute(
            "INSERT INTO sav_types_societes
             (tso_code, tso_nom, tso_description, tso_statut_id, tso_cree_le, tso_cree_par_utilisateur_id)
             VALUES (:code, :nom, :description, 1, NOW(), :user_id)",
            [
                'code' => $code,
                'nom' => $nom,
                'description' => 'Type créé automatiquement par le module Sociétés aligné sur le modèle SQL courant.',
                'user_id' => $idUtilisateur ?: null,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function tousAdministrables(): array
    {
        return $this->db->fetchAll(
            "SELECT
                t.*,
                (
                    SELECT COUNT(DISTINCT ats.ats_societe_id)
                    FROM sav_affectations_types_societes ats
                    WHERE ats.ats_type_societe_id = t.tso_id
                      AND ats.ats_supprime_le IS NULL
                      AND ats.ats_archive_le IS NULL
                      AND ats.ats_termine_le IS NULL
                ) AS societes_total
             FROM sav_types_societes t
             WHERE t.tso_supprime_le IS NULL
             ORDER BY t.tso_nom ASC"
        );
    }

    public function trouver(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT *
             FROM sav_types_societes
             WHERE tso_id = :id
               AND tso_supprime_le IS NULL
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function enregistrer(array $donnees, ?int $id = null, int $idUtilisateur = 0): int
    {
        $code = strtolower(trim((string) ($donnees['tso_code'] ?? '')));
        $nom = trim((string) ($donnees['tso_nom'] ?? ''));
        if ($code === '' || $nom === '') {
            throw new \InvalidArgumentException('Le code et le nom du type de société sont obligatoires.');
        }
        if (!preg_match('/^[a-z0-9_]+$/', $code)) {
            throw new \InvalidArgumentException('Le code doit contenir uniquement des lettres minuscules, des chiffres et des tirets bas.');
        }

        $params = [
            'code' => $code,
            'nom' => $nom,
            'description' => trim((string) ($donnees['tso_description'] ?? '')) ?: null,
            'statut_id' => !empty($donnees['tso_statut_id']) ? (int) $donnees['tso_statut_id'] : 1,
            'user_id' => $idUtilisateur ?: null,
        ];
        $doublonParams = ['code' => $code];
        $doublonSql = "SELECT tso_id FROM sav_types_societes
                       WHERE LOWER(tso_code) = LOWER(:code)
                         AND tso_supprime_le IS NULL";
        if ($id !== null) {
            $doublonSql .= ' AND tso_id <> :id';
            $doublonParams['id'] = $id;
        }
        if ($this->db->fetch($doublonSql . ' LIMIT 1', $doublonParams)) {
            throw new \InvalidArgumentException('Ce code de type de société existe déjà.');
        }

        if ($id !== null) {
            $params['id'] = $id;
            $this->db->execute(
                "UPDATE sav_types_societes
                 SET tso_code = :code,
                     tso_nom = :nom,
                     tso_description = :description,
                     tso_statut_id = :statut_id,
                     tso_modifie_le = NOW(),
                     tso_modifie_par_utilisateur_id = :user_id
                 WHERE tso_id = :id
                   AND tso_supprime_le IS NULL",
                $params
            );
            $this->journaliser('type_societe.modifier', $id, $idUtilisateur, ['code' => $code]);
            return $id;
        }

        $this->db->execute(
            "INSERT INTO sav_types_societes
             (tso_code, tso_nom, tso_description, tso_statut_id, tso_cree_le, tso_cree_par_utilisateur_id)
             VALUES (:code, :nom, :description, :statut_id, NOW(), :user_id)",
            $params
        );
        $newId = (int) $this->db->lastInsertId();
        $this->journaliser('type_societe.creer', $newId, $idUtilisateur, ['code' => $code]);
        return $newId;
    }

    public function supprimerLogiquement(int $id, int $idUtilisateur = 0): void
    {
        $utilisation = $this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM sav_affectations_types_societes
             WHERE ats_type_societe_id = :id
               AND ats_supprime_le IS NULL
               AND ats_archive_le IS NULL
               AND ats_termine_le IS NULL",
            ['id' => $id]
        );
        if ((int) ($utilisation['total'] ?? 0) > 0) {
            throw new \InvalidArgumentException('Ce type est encore affecté à une ou plusieurs sociétés.');
        }

        $this->db->execute(
            "UPDATE sav_types_societes
             SET tso_statut_id = 5,
                 tso_supprime_le = NOW(),
                 tso_supprime_par_utilisateur_id = :user_id,
                 tso_modifie_le = NOW(),
                 tso_modifie_par_utilisateur_id = :user_id
             WHERE tso_id = :id
               AND tso_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $idUtilisateur ?: null]
        );
        $this->journaliser('type_societe.supprimer', $id, $idUtilisateur, []);
    }

    /** CORRECTIF 2.3 (audit) : aucune mutation n'était journalisée. */
    private function journaliser(string $action, int $id, ?int $userId, array $metadata): void
    {
        try {
            $this->db->execute(
                'INSERT INTO sav_journaux_audit
                    (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le)
                 VALUES
                    (:user_id, :action, :table_cible, :id_cible, :ip, :metadata, NOW())',
                [
                    'user_id' => $userId ?: null,
                    'action' => $action,
                    'table_cible' => 'sav_types_societes',
                    'id_cible' => $id ?: null,
                    'ip' => function_exists('client_ip') ? @inet_pton((string) client_ip()) : null,
                    'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable) {
            // L'audit ne doit jamais bloquer une opération métier.
        }
    }
}
