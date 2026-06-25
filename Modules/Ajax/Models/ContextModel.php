<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class ContextModel extends BaseModel
{
    protected string $table = 'sav_historique_contextes_utilisateurs';
    protected string $colPrefix = 'hcu_';

    public function getUserCompanies(int $userId): array
    {
        return $this->db()->fetchAll(
            "SELECT
                s.soc_id AS com_id,
                s.soc_id AS soc_id,
                s.soc_nom AS com_name,
                s.soc_nom AS soc_nom,
                s.soc_nom_court AS com_short_name,
                NULL AS com_logo_url,
                (
                    SELECT GROUP_CONCAT(DISTINCT t.tso_code ORDER BY t.tso_nom SEPARATOR ',')
                    FROM sav_affectations_types_societes ats
                    INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                    WHERE ats.ats_societe_id = s.soc_id
                      AND ats.ats_supprime_le IS NULL
                      AND ats.ats_archive_le IS NULL
                ) AS company_type_code,
                (
                    SELECT GROUP_CONCAT(DISTINCT t.tso_nom ORDER BY t.tso_nom SEPARATOR ', ')
                    FROM sav_affectations_types_societes ats
                    INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                    WHERE ats.ats_societe_id = s.soc_id
                      AND ats.ats_supprime_le IS NULL
                      AND ats.ats_archive_le IS NULL
                ) AS company_type_label,
                0 AS ucm_is_primary
             FROM sav_adhesions_utilisateurs_societes aus
             INNER JOIN sav_societes s ON s.soc_id = aus.aus_societe_id
             INNER JOIN sav_statuts st_aus ON st_aus.sta_id = aus.aus_statut_id
                  AND st_aus.sta_domaine = 'general' AND st_aus.sta_code = 'actif'
             INNER JOIN sav_statuts st_soc ON st_soc.sta_id = s.soc_statut_id
                  AND st_soc.sta_domaine = 'general' AND st_soc.sta_code = 'actif'
             INNER JOIN sav_espaces_applicatifs eap ON eap.eap_societe_id = s.soc_id
                  AND eap.eap_supprime_le IS NULL AND eap.eap_bloque_le IS NULL
             INNER JOIN sav_statuts st_eap ON st_eap.sta_id = eap.eap_statut_id
                  AND st_eap.sta_domaine = 'general' AND st_eap.sta_code = 'actif'
             INNER JOIN sav_abonnements_societes abo ON abo.abo_id = eap.eap_abonnement_societe_id
                  AND abo.abo_societe_id = s.soc_id AND abo.abo_supprime_le IS NULL AND abo.abo_archive_le IS NULL
                  AND (abo.abo_debute_le IS NULL OR abo.abo_debute_le <= NOW())
                  AND (abo.abo_termine_le IS NULL OR abo.abo_termine_le >= NOW())
             INNER JOIN sav_statuts st_abo ON st_abo.sta_id = abo.abo_statut_abonnement_id
                  AND st_abo.sta_domaine = 'abonnement' AND st_abo.sta_code IN ('actif', 'essai')
             INNER JOIN sav_statuts st_pay ON st_pay.sta_id = abo.abo_statut_paiement_id
                  AND st_pay.sta_domaine = 'paiement' AND st_pay.sta_code = 'a_jour'
             WHERE aus.aus_utilisateur_id = :user_id
               AND aus.aus_supprime_le IS NULL
               AND aus.aus_archive_le IS NULL
               AND (aus.aus_debute_le IS NULL OR aus.aus_debute_le <= NOW())
               AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= NOW())
               AND s.soc_supprime_le IS NULL AND s.soc_archive_le IS NULL
             ORDER BY s.soc_nom ASC",
            ['user_id' => $userId]
        );
    }

    public function getBrandsForCompany(int $companyId): array
    {
        return $this->db()->fetchAll(
            "SELECT
                b.soc_id AS brd_id,
                b.soc_id AS soc_id,
                b.soc_code AS brd_code,
                b.soc_nom AS brd_name,
                NULL AS brd_logo_url,
                0 AS cbr_is_primary
             FROM sav_representations_marques_societes r
             INNER JOIN sav_societes b ON b.soc_id = r.rma_marque_societe_id
             WHERE r.rma_concession_societe_id = :company_id
               AND r.rma_supprime_le IS NULL
               AND r.rma_archive_le IS NULL
               AND (r.rma_termine_le IS NULL OR r.rma_termine_le >= CURDATE())
               AND b.soc_supprime_le IS NULL
             ORDER BY b.soc_nom ASC",
            ['company_id' => $companyId]
        );
    }

    public function getCompany(int $companyId): ?array
    {
        return $this->db()->fetch(
            "SELECT * FROM sav_societes
             WHERE soc_id = :company_id
               AND soc_supprime_le IS NULL
               AND soc_archive_le IS NULL
             LIMIT 1",
            ['company_id' => $companyId]
        );
    }

    public function userOwnsCompany(int $userId, int $companyId): bool
    {
        $row = $this->db()->fetch(
            "SELECT COUNT(*) AS cnt
             FROM sav_adhesions_utilisateurs_societes aus
             INNER JOIN sav_statuts st_aus ON st_aus.sta_id = aus.aus_statut_id
                  AND st_aus.sta_domaine = 'general' AND st_aus.sta_code = 'actif'
             INNER JOIN sav_societes s ON s.soc_id = aus.aus_societe_id
                  AND s.soc_supprime_le IS NULL AND s.soc_archive_le IS NULL
             INNER JOIN sav_statuts st_soc ON st_soc.sta_id = s.soc_statut_id
                  AND st_soc.sta_domaine = 'general' AND st_soc.sta_code = 'actif'
             INNER JOIN sav_espaces_applicatifs eap ON eap.eap_societe_id = s.soc_id
                  AND eap.eap_supprime_le IS NULL AND eap.eap_bloque_le IS NULL
             INNER JOIN sav_statuts st_eap ON st_eap.sta_id = eap.eap_statut_id
                  AND st_eap.sta_domaine = 'general' AND st_eap.sta_code = 'actif'
             INNER JOIN sav_abonnements_societes abo ON abo.abo_id = eap.eap_abonnement_societe_id
                  AND abo.abo_societe_id = s.soc_id AND abo.abo_supprime_le IS NULL AND abo.abo_archive_le IS NULL
                  AND (abo.abo_debute_le IS NULL OR abo.abo_debute_le <= NOW())
                  AND (abo.abo_termine_le IS NULL OR abo.abo_termine_le >= NOW())
             INNER JOIN sav_statuts st_abo ON st_abo.sta_id = abo.abo_statut_abonnement_id
                  AND st_abo.sta_domaine = 'abonnement' AND st_abo.sta_code IN ('actif', 'essai')
             INNER JOIN sav_statuts st_pay ON st_pay.sta_id = abo.abo_statut_paiement_id
                  AND st_pay.sta_domaine = 'paiement' AND st_pay.sta_code = 'a_jour'
             WHERE aus.aus_utilisateur_id = :user_id
               AND aus.aus_societe_id = :company_id
               AND aus.aus_supprime_le IS NULL
               AND aus.aus_archive_le IS NULL
               AND (aus.aus_debute_le IS NULL OR aus.aus_debute_le <= NOW())
               AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= NOW())",
            ['user_id' => $userId, 'company_id' => $companyId]
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }

    public function companyHasBrand(int $companyId, int $brandId): bool
    {
        $row = $this->db()->fetch(
            "SELECT COUNT(*) AS cnt
             FROM sav_representations_marques_societes
             WHERE rma_concession_societe_id = :company_id
               AND rma_marque_societe_id = :brand_id
               AND rma_supprime_le IS NULL
               AND rma_archive_le IS NULL
               AND (rma_termine_le IS NULL OR rma_termine_le >= CURDATE())",
            ['company_id' => $companyId, 'brand_id' => $brandId]
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }

    public function updateActiveCompany(int $userId, ?int $companyId): bool
    {
        return $this->db()->execute(
            "UPDATE sav_utilisateurs
             SET uti_societe_active_id = :company_id,
                 uti_marque_active_id = NULL,
                 uti_modifie_le = NOW()
             WHERE uti_id = :user_id",
            ['company_id' => $companyId, 'user_id' => $userId]
        );
    }

    public function updateActiveBrand(int $userId, ?int $brandId): bool
    {
        return $this->db()->execute(
            "UPDATE sav_utilisateurs
             SET uti_marque_active_id = :brand_id,
                 uti_modifie_le = NOW()
             WHERE uti_id = :user_id",
            ['brand_id' => $brandId, 'user_id' => $userId]
        );
    }

    public function updatePersistentContext(int $sessionId, ?int $companyId, ?int $concessionId, ?int $brandId): bool
    {
        return $this->db()->execute(
            "UPDATE sav_sessions_utilisateurs
             SET seu_societe_active_id = :company_id,
                 seu_concession_active_id = :concession_id,
                 seu_marque_active_id = :brand_id,
                 seu_derniere_activite_le = NOW(),
                 seu_modifie_le = NOW()
             WHERE seu_id = :session_id
               AND seu_revoquee_le IS NULL
               AND seu_supprime_le IS NULL",
            [
                'session_id' => $sessionId,
                'company_id' => $companyId,
                'concession_id' => $concessionId,
                'brand_id' => $brandId,
            ]
        );
    }
}
