<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Society\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Modèle Société aligné sur la base SQL actuelle.
 * Table principale : sav_societes.
 * Types multiples : sav_affectations_types_societes + sav_types_societes.
 */
class ModeleSociete extends BaseModel
{
    protected string $table = 'sav_societes';
    protected string $colPrefix = 'soc_';

    /**
     * AUDIT 2026-06-21 — correctif point 4.5 : sav_journaux_audit n'était jamais
     * alimentée pour les sociétés, alors que la création, la modification et
     * surtout le "blocage entreprise" figurent explicitement dans la liste des
     * actions sensibles à journaliser du cahier des charges. Cette classe est
     * la classe mère réelle de Modules\Companies\Models\CompanyModel, seul
     * module Société effectivement routé (Modules/Society/Controllers n'est
     * routé nulle part dans config/urls.php — code mort à nettoyer un jour).
     */
    private function audit(?int $userId, string $action, int $targetId, array $meta = [], ?string $reason = null): void
    {
        try {
            $this->db->execute(
                "INSERT INTO sav_journaux_audit
                    (jau_utilisateur_id, jau_societe_id, jau_action, jau_table_cible, jau_id_cible,
                     jau_raison, jau_adresse_ip, jau_user_agent, jau_metadata_json, jau_cree_le)
                 VALUES
                    (:user_id, :societe_id, :action, 'sav_societes', :id_cible,
                     :raison, INET6_ATON(:ip), :user_agent, :metadata, NOW())",
                [
                    'user_id' => $userId ?: null,
                    'societe_id' => $targetId,
                    'action' => $action,
                    'id_cible' => $targetId,
                    'raison' => $reason,
                    'ip' => function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
                    'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    'metadata' => json_encode(['module' => 'Society'] + $meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable $e) {
            if (function_exists('logger')) {
                logger('audit')->error('Échec écriture sav_journaux_audit (Society)', [
                    'action' => $action, 'target_id' => $targetId, 'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function paginer(array $filtres = [], int $page = 1, int $parPage = 25): array
    {
        $where = ['s.soc_supprime_le IS NULL'];
        $params = [];

        if (($filtres['type'] ?? $filtres['type_code'] ?? '') !== '') {
            $where[] = "EXISTS (
                SELECT 1
                FROM sav_affectations_types_societes ats
                INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                WHERE ats.ats_societe_id = s.soc_id
                  AND ats.ats_supprime_le IS NULL
                  AND ats.ats_archive_le IS NULL
                  AND t.tso_supprime_le IS NULL
                  AND LOWER(t.tso_code) = LOWER(:type)
            )";
            $params['type'] = trim((string) ($filtres['type'] ?? $filtres['type_code']));
        }

        if (($filtres['recherche'] ?? $filtres['search'] ?? '') !== '') {
            $where[] = '(s.soc_nom LIKE :recherche OR s.soc_nom_legal LIKE :recherche OR s.soc_nom_court LIKE :recherche OR s.soc_siret LIKE :recherche OR s.soc_ville LIKE :recherche OR s.soc_code LIKE :recherche)';
            $params['recherche'] = '%' . trim((string) ($filtres['recherche'] ?? $filtres['search'])) . '%';
        }

        if (($filtres['statut'] ?? '') !== '') {
            $where[] = "EXISTS (
                SELECT 1 FROM sav_statuts sta
                WHERE sta.sta_id = s.soc_statut_id
                  AND LOWER(sta.sta_code) = LOWER(:statut)
            )";
            $params['statut'] = trim((string) $filtres['statut']);
        }

        if (($filtres['active'] ?? $filtres['is_active'] ?? '') !== '') {
            if ((int) ($filtres['active'] ?? $filtres['is_active']) === 1) {
                $where[] = 's.soc_statut_id = 1 AND s.soc_archive_le IS NULL';
            } else {
                $where[] = '(s.soc_statut_id <> 1 OR s.soc_statut_id IS NULL OR s.soc_archive_le IS NOT NULL)';
            }
        }

        if (!empty($filtres['holding_id'])) {
            $where[] = 's.soc_holding_id = :holding_id';
            $params['holding_id'] = (int) $filtres['holding_id'];
        }

        if (!empty($filtres['parent_id'])) {
            $where[] = 's.soc_societe_parente_id = :parent_id';
            $params['parent_id'] = (int) $filtres['parent_id'];
        }

        $sqlWhere = implode(' AND ', $where);
        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM sav_societes s WHERE {$sqlWhere}",
            $params
        )['total'] ?? 0);

        $pages = max(1, (int) ceil($total / max(1, $parPage)));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $parPage;

        $lignes = $this->db->fetchAll(
            $this->selectSocieteSql("WHERE {$sqlWhere} ORDER BY s.soc_nom ASC LIMIT :limite OFFSET :offset"),
            array_merge($params, ['limite' => $parPage, 'offset' => $offset])
        );

        return [
            'lignes' => $lignes,
            'rows' => $lignes,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'par_page' => $parPage,
            'perPage' => $parPage,
        ];
    }

    public function trouver(int $id): ?array
    {
        return $this->db->fetch(
            $this->selectSocieteSql('WHERE s.soc_id = :id AND s.soc_supprime_le IS NULL LIMIT 1'),
            ['id' => $id]
        );
    }

    public function creer(array $donnees): int
    {
        return (int) $this->db->transaction(function () use ($donnees): int {
            $typesIds = (array) ($donnees['types_ids'] ?? []);
            $utilisateurId = (int) ($donnees['cree_par'] ?? 0);
            $params = $donnees;
            unset($params['types_ids']);
            $params['modifie_par'] = $params['cree_par'] ?? null;

            $this->db->execute(
                "INSERT INTO sav_societes
                 (soc_uuid, soc_code, soc_nom, soc_nom_legal, soc_nom_court,
                  soc_siren, soc_siret, soc_siret_siege, soc_numero_tva,
                  soc_code_naf, soc_libelle_naf, soc_forme_juridique,
                  soc_date_creation, soc_capital_social, soc_rcs,
                  soc_adresse, soc_code_postal, soc_ville, soc_pays_id,
                  soc_telephone, soc_email, soc_site_web,
                  soc_logo_fichier_id, soc_est_holding, soc_societe_parente_id,
                  soc_holding_id, soc_cree_par_societe_id, soc_statut_id, soc_cree_le,
                  soc_cree_par_utilisateur_id, soc_modifie_par_utilisateur_id)
                 VALUES
                 (:uuid, :code, :nom, :raison_sociale, :nom_court,
                  :siren, :siret, :siret_siege, :tva,
                  :code_naf, :libelle_naf, :forme_juridique,
                  :date_creation, :capital_social, :rcs,
                  :adresse, :code_postal, :ville, :pays_id,
                  :telephone, :email, :site_web,
                  :logo_fichier_id, :est_holding, :parent_id,
                  :holding_id, :cree_par_societe_id, :statut_id, NOW(),
                  :cree_par, :modifie_par)",
                $params
            );
            $id = (int) $this->db->lastInsertId();
            $this->synchroniserTypes($id, $typesIds, $utilisateurId);

            $this->audit($utilisateurId ?: null, 'societe.creation', $id, [
                'nom' => $donnees['nom'] ?? null,
                'code' => $donnees['code'] ?? null,
            ]);

            return $id;
        });
    }

    public function modifier(int $id, array $donnees): bool
    {
        return (bool) $this->db->transaction(function () use ($id, $donnees): bool {
            $typesIds = (array) ($donnees['types_ids'] ?? []);
            $utilisateurId = (int) ($donnees['modifie_par'] ?? 0);
            $donnees['id'] = $id;
            $params = $donnees;
            unset($params['types_ids']);

            $this->db->execute(
                "UPDATE sav_societes
                 SET soc_code = :code,
                     soc_nom = :nom,
                     soc_nom_legal = :raison_sociale,
                     soc_nom_court = :nom_court,
                     soc_siren = :siren,
                     soc_siret = :siret,
                     soc_siret_siege = :siret_siege,
                     soc_numero_tva = :tva,
                     soc_code_naf = :code_naf,
                     soc_libelle_naf = :libelle_naf,
                     soc_forme_juridique = :forme_juridique,
                     soc_date_creation = :date_creation,
                     soc_capital_social = :capital_social,
                     soc_rcs = :rcs,
                     soc_adresse = :adresse,
                     soc_code_postal = :code_postal,
                     soc_ville = :ville,
                     soc_pays_id = :pays_id,
                     soc_telephone = :telephone,
                     soc_email = :email,
                     soc_site_web = :site_web,
                     soc_logo_fichier_id = :logo_fichier_id,
                     soc_est_holding = :est_holding,
                     soc_societe_parente_id = :parent_id,
                     soc_holding_id = :holding_id,
                     soc_statut_id = :statut_id,
                     soc_modifie_par_utilisateur_id = :modifie_par,
                     soc_modifie_le = NOW()
                 WHERE soc_id = :id AND soc_supprime_le IS NULL",
                $params
            );
            $this->synchroniserTypes($id, $typesIds, $utilisateurId);
            $this->audit($utilisateurId ?: null, 'societe.modification', $id);
            return true;
        });
    }

    public function desactiver(int $id, int $idUtilisateur): bool
    {
        $result = $this->changerStatut($id, 2, $idUtilisateur);
        if ($result) {
            $this->audit($idUtilisateur ?: null, 'societe.blocage', $id, [], 'Désactivation de la société');
        }
        return $result;
    }

    public function reactiver(int $id, int $idUtilisateur): bool
    {
        $result = $this->changerStatut($id, 1, $idUtilisateur);
        if ($result) {
            $this->audit($idUtilisateur ?: null, 'societe.reactivation', $id);
        }
        return $result;
    }

    public function supprimerLogiquement(int $id, int $idUtilisateur): bool
    {
        $result = $this->db->execute(
            "UPDATE sav_societes
             SET soc_supprime_le = NOW(),
                 soc_statut_id = 5,
                 soc_modifie_par_utilisateur_id = :utilisateur,
                 soc_supprime_par_utilisateur_id = :utilisateur,
                 soc_modifie_le = NOW()
             WHERE soc_id = :id AND soc_supprime_le IS NULL",
            ['id' => $id, 'utilisateur' => $idUtilisateur]
        );
        if ($result) {
            $this->audit($idUtilisateur ?: null, 'societe.suppression', $id);
        }
        return $result;
    }

    public function toutesActives(?string $codeType = null): array
    {
        $params = [];
        $where = ['s.soc_supprime_le IS NULL', 's.soc_archive_le IS NULL', 's.soc_statut_id = 1'];
        if ($codeType !== null && trim($codeType) !== '') {
            $where[] = "EXISTS (
                SELECT 1
                FROM sav_affectations_types_societes ats
                INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                WHERE ats.ats_societe_id = s.soc_id
                  AND ats.ats_supprime_le IS NULL
                  AND ats.ats_archive_le IS NULL
                  AND LOWER(t.tso_code) = LOWER(:type)
            )";
            $params['type'] = trim($codeType);
        }

        return $this->db->fetchAll(
            $this->selectSocieteSql('WHERE ' . implode(' AND ', $where) . ' ORDER BY s.soc_nom ASC'),
            $params
        );
    }

    public function holdings(): array
    {
        return $this->db->fetchAll(
            $this->selectSocieteSql(
                'WHERE s.soc_supprime_le IS NULL
                   AND s.soc_archive_le IS NULL
                   AND s.soc_statut_id = 1
                   AND s.soc_est_holding = 1
                 ORDER BY s.soc_nom ASC'
            )
        );
    }

    private function changerStatut(int $id, int $statutId, int $idUtilisateur): bool
    {
        return $this->db->execute(
            "UPDATE sav_societes
             SET soc_statut_id = :statut,
                 soc_modifie_par_utilisateur_id = :utilisateur,
                 soc_modifie_le = NOW()
             WHERE soc_id = :id AND soc_supprime_le IS NULL",
            ['id' => $id, 'statut' => $statutId, 'utilisateur' => $idUtilisateur]
        );
    }

    private function affecterType(int $idSociete, int $idType, int $idUtilisateur): void
    {
        if ($idType <= 0) {
            return;
        }

        $existant = $this->db->fetch(
            "SELECT ats_id FROM sav_affectations_types_societes
             WHERE ats_societe_id = :societe
               AND ats_type_societe_id = :type
               AND ats_supprime_le IS NULL
             ORDER BY IF(ats_archive_le IS NULL AND ats_termine_le IS NULL, 0, 1), ats_id DESC
             LIMIT 1",
            ['societe' => $idSociete, 'type' => $idType]
        );

        if ($existant) {
            $this->db->execute(
                "UPDATE sav_affectations_types_societes
                 SET ats_termine_le = NULL,
                     ats_statut_id = 1,
                     ats_archive_le = NULL,
                     ats_modifie_le = NOW(),
                     ats_modifie_par_utilisateur_id = :user_id
                 WHERE ats_id = :id",
                ['id' => (int) $existant['ats_id'], 'user_id' => $idUtilisateur ?: null]
            );
            return;
        }

        $this->db->execute(
            "INSERT INTO sav_affectations_types_societes
             (ats_societe_id, ats_type_societe_id, ats_debute_le, ats_statut_id, ats_cree_le, ats_cree_par_utilisateur_id)
             VALUES (:societe, :type, CURDATE(), 1, NOW(), :user_id)",
            ['societe' => $idSociete, 'type' => $idType, 'user_id' => $idUtilisateur ?: null]
        );
    }

    private function synchroniserTypes(int $idSociete, array $idsTypes, int $idUtilisateur): void
    {
        $idsTypes = array_values(array_unique(array_filter(
            array_map('intval', $idsTypes),
            static fn(int $id): bool => $id > 0
        )));

        $affectations = $this->db->fetchAll(
            "SELECT ats_id, ats_type_societe_id
             FROM sav_affectations_types_societes
             WHERE ats_societe_id = :societe
               AND ats_supprime_le IS NULL
               AND ats_archive_le IS NULL
               AND ats_termine_le IS NULL",
            ['societe' => $idSociete]
        );

        foreach ($affectations as $affectation) {
            if (in_array((int) $affectation['ats_type_societe_id'], $idsTypes, true)) {
                continue;
            }

            $this->db->execute(
                "UPDATE sav_affectations_types_societes
                 SET ats_termine_le = CURDATE(),
                     ats_statut_id = 2,
                     ats_archive_le = NOW(),
                     ats_modifie_le = NOW(),
                     ats_modifie_par_utilisateur_id = :user_id
                 WHERE ats_id = :id",
                ['id' => (int) $affectation['ats_id'], 'user_id' => $idUtilisateur ?: null]
            );
        }

        foreach ($idsTypes as $idType) {
            $this->affecterType($idSociete, $idType, $idUtilisateur);
        }
    }

    // ─── Entités liées ────────────────────────────────────────────────────

    /** @return array<int,array<string,mixed>> */
    public function dirigeants(int $societeId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM sav_dirigeants_societes
              WHERE dso_societe_id = :id
                AND dso_supprime_le IS NULL
              ORDER BY dso_est_dirigeant_principal DESC, dso_ordre ASC, dso_id ASC",
            ['id' => $societeId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function etablissements(int $societeId): array
    {
        return $this->db->fetchAll(
            "SELECT e.*, p.pay_nom AS pays_nom
               FROM sav_etablissements_societes e
               LEFT JOIN sav_pays p ON p.pay_id = e.ets_pays_id
              WHERE e.ets_societe_id = :id
                AND e.ets_supprime_le IS NULL
              ORDER BY e.ets_est_siege DESC, e.ets_ordre ASC, e.ets_id ASC",
            ['id' => $societeId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function contacts(int $societeId): array
    {
        // La table sav_contacts_societes est originale avec prefixe cts_
        // Elle a ete enrichie avec cts_fonction, cts_telephone, cts_est_principal, etc.
        return $this->db->fetchAll(
            "SELECT c.*, e.ets_nom AS etablissement_nom
               FROM sav_contacts_societes c
               LEFT JOIN sav_etablissements_societes e ON e.ets_id = c.cts_etablissement_id
              WHERE c.cts_societe_id = :id
                AND c.cts_supprime_le IS NULL
              ORDER BY c.cts_est_principal DESC, c.cts_ordre ASC, c.cts_id ASC",
            ['id' => $societeId]
        );
    }

    public function ficheComplete(int $id): ?array
    {
        $societe = $this->trouver($id);
        if (!$societe) {
            return null;
        }
        $societe['dirigeants']     = $this->dirigeants($id);
        $societe['etablissements'] = $this->etablissements($id);
        $societe['contacts']       = $this->contacts($id);
        return $societe;
    }

    private function selectSocieteSql(string $suffixe): string
    {
        return "SELECT
                s.*,
                p.pay_nom AS soc_pays_nom,
                parent.soc_nom AS parent_nom,
                holding.soc_nom AS holding_nom,
                sta.sta_code AS statut_code,
                sta.sta_libelle AS statut_libelle,
                s.soc_id AS com_id,
                s.soc_uuid AS com_uuid,
                s.soc_code AS com_code,
                s.soc_nom AS com_name,
                s.soc_nom_legal AS com_legal_name,
                s.soc_nom_court AS com_short_name,
                s.soc_siret AS com_siret,
                s.soc_numero_tva AS com_vat_number,
                s.soc_adresse AS com_address,
                s.soc_code_postal AS com_postal_code,
                s.soc_code_postal AS com_zipcode,
                s.soc_ville AS com_city,
                COALESCE(p.pay_nom, 'France') AS com_country,
                s.soc_telephone AS com_phone,
                s.soc_email AS com_email,
                s.soc_site_web AS com_website,
                NULL AS com_logo_url,
                IF(s.soc_statut_id = 1 AND s.soc_supprime_le IS NULL AND s.soc_archive_le IS NULL, 1, 0) AS com_is_active,
                COALESCE(sta.sta_code, 'inactif') AS com_status,
                s.soc_est_holding AS com_is_holding,
                s.soc_societe_parente_id AS com_parent_id,
                s.soc_holding_id AS com_holding_id,
                s.soc_cree_le AS com_created_at,
                s.soc_modifie_le AS com_updated_at,
                s.soc_supprime_le AS com_deleted_at,
                (
                    SELECT GROUP_CONCAT(DISTINCT t.tso_code ORDER BY t.tso_nom SEPARATOR ',')
                    FROM sav_affectations_types_societes ats
                    INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                    WHERE ats.ats_societe_id = s.soc_id
                      AND ats.ats_supprime_le IS NULL
                      AND ats.ats_archive_le IS NULL
                      AND t.tso_supprime_le IS NULL
                ) AS type_code,
                (
                    SELECT GROUP_CONCAT(DISTINCT t.tso_nom ORDER BY t.tso_nom SEPARATOR ', ')
                    FROM sav_affectations_types_societes ats
                    INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                    WHERE ats.ats_societe_id = s.soc_id
                      AND ats.ats_supprime_le IS NULL
                      AND ats.ats_archive_le IS NULL
                      AND t.tso_supprime_le IS NULL
                ) AS type_label,
                (
                    SELECT GROUP_CONCAT(DISTINCT t.tso_id ORDER BY t.tso_nom SEPARATOR ',')
                    FROM sav_affectations_types_societes ats
                    INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                    WHERE ats.ats_societe_id = s.soc_id
                      AND ats.ats_supprime_le IS NULL
                      AND ats.ats_archive_le IS NULL
                      AND t.tso_supprime_le IS NULL
                ) AS types_ids_csv,
                (
                    SELECT MIN(t.tso_id)
                    FROM sav_affectations_types_societes ats
                    INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                    WHERE ats.ats_societe_id = s.soc_id
                      AND ats.ats_supprime_le IS NULL
                      AND ats.ats_archive_le IS NULL
                      AND t.tso_supprime_le IS NULL
                ) AS com_type_id,
                (
                    SELECT COUNT(DISTINCT rma.rma_marque_societe_id)
                    FROM sav_representations_marques_societes rma
                    WHERE rma.rma_concession_societe_id = s.soc_id
                      AND rma.rma_supprime_le IS NULL
                      AND rma.rma_archive_le IS NULL
                      AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())
                ) AS nombre_marques
             FROM sav_societes s
             LEFT JOIN sav_pays p ON p.pay_id = s.soc_pays_id
             LEFT JOIN sav_societes parent ON parent.soc_id = s.soc_societe_parente_id
             LEFT JOIN sav_societes holding ON holding.soc_id = s.soc_holding_id
             LEFT JOIN sav_statuts sta ON sta.sta_id = s.soc_statut_id
             {$suffixe}";
    }
}