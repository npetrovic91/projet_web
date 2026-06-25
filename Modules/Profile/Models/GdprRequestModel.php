<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Profile\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Demandes RGPD issues du profil utilisateur.
 * Source SQL unique : sav_demandes_rgpd.
 */
class GdprRequestModel extends BaseModel
{
    protected string $table = 'sav_demandes_rgpd';
    protected string $colPrefix = 'drg_';

    public function createRequest(int $userId, string $type, string $message, string $ip): int
    {
        $this->db->execute(
            "INSERT INTO sav_demandes_rgpd
                (drg_uuid, drg_utilisateur_id, drg_type_demande, drg_email_contact, drg_description, drg_statut_id, drg_cree_le)
             SELECT
                :uuid, u.uti_id, :type, u.uti_email, :message, COALESCE(st.sta_id, 1), NOW()
             FROM sav_utilisateurs u
             LEFT JOIN sav_statuts st ON st.sta_domaine = 'general' AND st.sta_code = 'nouveau' AND st.sta_supprime_le IS NULL
             WHERE u.uti_id = :user_id
             LIMIT 1",
            [
                'uuid' => $this->uuid(),
                'user_id' => $userId,
                'type' => $type,
                'message' => trim($message) !== '' ? trim($message) . "\n\nIP demandeur : " . $ip : "IP demandeur : " . $ip,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function getForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT
                    d.*,
                    d.drg_id AS grq_id,
                    d.drg_uuid AS grq_uuid,
                    d.drg_utilisateur_id AS grq_user_id,
                    d.drg_type_demande AS grq_type,
                    d.drg_description AS grq_message,
                    d.drg_cree_le AS grq_created_at,
                    d.drg_modifie_le AS grq_updated_at,
                    st.sta_code AS grq_status,
                    st.sta_libelle AS statut_libelle
             FROM sav_demandes_rgpd d
             LEFT JOIN sav_statuts st ON st.sta_id = d.drg_statut_id
             WHERE d.drg_utilisateur_id = :user_id
               AND d.drg_supprime_le IS NULL
             ORDER BY d.drg_cree_le DESC",
            ['user_id' => $userId]
        );
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
