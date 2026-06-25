<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Maintenance\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class MaintenanceEventModel extends BaseModel
{
    protected string $table = 'sav_journaux_maintenance';
    protected string $colPrefix = 'jma_';

    public function record(string $type, string $severity, string $message, ?int $createdBy, string $ip, array $context = []): int
    {
        $details = [
            'type' => $type,
            'created_by' => $createdBy,
            'ip' => $ip,
            'context' => $context,
        ];

        $this->db->execute(
            "INSERT INTO sav_journaux_maintenance
                (jma_niveau, jma_message, jma_details_json, jma_cree_le)
             VALUES
                (:level, :message, :details, NOW())",
            [
                'level' => $severity,
                'message' => $message,
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function latest(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        return $this->db->fetchAll(
            "SELECT j.jma_id AS mev_id,
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(j.jma_details_json, '$.type')), 'maintenance') AS mev_event_type,
                    j.jma_niveau AS mev_severity,
                    j.jma_message AS mev_message,
                    j.jma_details_json AS mev_context,
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(j.jma_details_json, '$.created_by')) AS UNSIGNED) AS mev_created_by,
                    JSON_UNQUOTE(JSON_EXTRACT(j.jma_details_json, '$.ip')) AS mev_created_ip,
                    j.jma_cree_le AS mev_created_at,
                    u.uti_email AS created_by_email
               FROM sav_journaux_maintenance j
          LEFT JOIN sav_utilisateurs u
                 ON u.uti_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(j.jma_details_json, '$.created_by')) AS UNSIGNED)
           ORDER BY j.jma_cree_le DESC
              LIMIT {$limit}"
        );
    }
}
