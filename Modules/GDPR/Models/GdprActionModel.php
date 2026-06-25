<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\GDPR\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class GdprActionModel extends BaseModel
{
    protected string $table = 'sav_journaux_rgpd';
    protected string $colPrefix = 'jrg_';

    public function record(?int $requestId, ?int $userId, string $action, int $adminId, string $ip, array $details = []): int
    {
        $metadata = [
            'request_id' => $requestId,
            'performed_by' => $adminId,
            'ip' => $ip,
            'details' => $details,
        ];

        $this->db->execute(
            "INSERT INTO sav_journaux_rgpd
                (jrg_utilisateur_id, jrg_action, jrg_base_legale, jrg_table_cible, jrg_id_cible, jrg_metadata_json, jrg_cree_le)
             VALUES
                (:user_id, :action, 'RGPD', :table_cible, :target_id, :metadata, NOW())",
            [
                'user_id' => $userId,
                'action' => $action,
                'table_cible' => $requestId ? 'sav_demandes_rgpd' : 'sav_utilisateurs',
                'target_id' => $requestId ?: $userId,
                'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function latest(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return $this->db->fetchAll(
            "SELECT a.jrg_id AS gac_id,
                    a.jrg_action AS gac_action,
                    a.jrg_utilisateur_id AS gac_user_id,
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(a.jrg_metadata_json, '$.performed_by')) AS UNSIGNED) AS gac_performed_by,
                    a.jrg_metadata_json AS gac_details,
                    a.jrg_cree_le AS gac_created_at,
                    u.uti_email AS target_email,
                    admin.uti_email AS admin_email
               FROM sav_journaux_rgpd a
          LEFT JOIN sav_utilisateurs u ON u.uti_id = a.jrg_utilisateur_id
          LEFT JOIN sav_utilisateurs admin ON admin.uti_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(a.jrg_metadata_json, '$.performed_by')) AS UNSIGNED)
           ORDER BY a.jrg_cree_le DESC
              LIMIT {$limit}"
        );
    }
}
