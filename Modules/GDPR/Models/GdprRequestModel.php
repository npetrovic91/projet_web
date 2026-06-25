<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\GDPR\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class GdprRequestModel extends BaseModel
{
    protected string $table = 'sav_demandes_rgpd';
    protected string $colPrefix = 'drg_';

    public function listRequests(array $filters = []): array
    {
        $conditions = ['r.drg_supprime_le IS NULL'];
        $params = [];

        if (($filters['status'] ?? '') !== '') {
            if ($filters['status'] === 'submitted') {
                $conditions[] = 'r.drg_traitee_le IS NULL';
            } elseif ($filters['status'] === 'processed') {
                $conditions[] = 'r.drg_traitee_le IS NOT NULL';
            }
        }
        if (($filters['type'] ?? '') !== '') {
            $conditions[] = 'r.drg_type_demande = :type';
            $params['type'] = $filters['type'];
        }

        return $this->db->fetchAll(
            "SELECT r.drg_id AS grq_id,
                    r.drg_uuid AS grq_uuid,
                    r.drg_utilisateur_id AS grq_user_id,
                    r.drg_societe_id AS grq_company_id,
                    r.drg_type_demande AS grq_type,
                    CASE WHEN r.drg_traitee_le IS NULL THEN 'submitted' ELSE 'processed' END AS grq_status,
                    r.drg_description AS grq_message,
                    r.drg_reponse AS grq_response,
                    NULL AS grq_rejection_reason,
                    r.drg_cree_le AS grq_created_at,
                    r.drg_modifie_le AS grq_updated_at,
                    r.drg_traitee_le AS grq_handled_at,
                    r.drg_traitee_par_utilisateur_id AS grq_handled_by,
                    COALESCE(u.uti_email, r.drg_email_contact, '') AS use_email,
                    p.pui_prenom AS use_firstname,
                    p.pui_nom AS use_lastname
               FROM sav_demandes_rgpd r
          LEFT JOIN sav_utilisateurs u ON u.uti_id = r.drg_utilisateur_id
          LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
              WHERE " . implode(' AND ', $conditions) . "
           ORDER BY r.drg_cree_le DESC",
            $params
        );
    }

    public function findRequest(int $requestId): ?array
    {
        return $this->db->fetch(
            "SELECT r.drg_id AS grq_id,
                    r.drg_uuid AS grq_uuid,
                    r.drg_utilisateur_id AS grq_user_id,
                    r.drg_societe_id AS grq_company_id,
                    r.drg_type_demande AS grq_type,
                    CASE WHEN r.drg_traitee_le IS NULL THEN 'submitted' ELSE 'processed' END AS grq_status,
                    r.drg_description AS grq_message,
                    r.drg_reponse AS grq_response,
                    NULL AS grq_rejection_reason,
                    r.drg_cree_le AS grq_created_at,
                    r.drg_modifie_le AS grq_updated_at,
                    r.drg_traitee_le AS grq_handled_at,
                    r.drg_traitee_par_utilisateur_id AS grq_handled_by,
                    COALESCE(u.uti_email, r.drg_email_contact, '') AS use_email,
                    p.pui_prenom AS use_firstname,
                    p.pui_nom AS use_lastname
               FROM sav_demandes_rgpd r
          LEFT JOIN sav_utilisateurs u ON u.uti_id = r.drg_utilisateur_id
          LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
              WHERE r.drg_id = :id
                AND r.drg_supprime_le IS NULL",
            ['id' => $requestId]
        );
    }

    public function updateStatus(int $requestId, string $status, int $adminId, ?string $response = null, ?string $rejectionReason = null): bool
    {
        $finalResponse = $status === 'rejected'
            ? ('Rejet : ' . (string) $rejectionReason)
            : $response;

        return $this->db->execute(
            "UPDATE sav_demandes_rgpd
                SET drg_reponse = :response,
                    drg_traitee_par_utilisateur_id = :admin_id,
                    drg_traitee_le = NOW(),
                    drg_modifie_le = NOW()
              WHERE drg_id = :id
                AND drg_supprime_le IS NULL",
            [
                'response' => $finalResponse,
                'admin_id' => $adminId,
                'id' => $requestId,
            ]
        );
    }
}
