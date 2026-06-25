<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\GDPR\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class GdprExportModel extends BaseModel
{
    protected string $table = 'sav_journaux_rgpd';
    protected string $colPrefix = 'jrg_';

    public function record(int $userId, ?int $requestId, string $fileName, int $adminId, string $ip): int
    {
        $metadata = [
            'request_id' => $requestId,
            'file_name' => $fileName,
            'format' => 'json',
            'performed_by' => $adminId,
            'ip' => $ip,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
        ];

        $this->db->execute(
            "INSERT INTO sav_journaux_rgpd
                (jrg_utilisateur_id, jrg_action, jrg_base_legale, jrg_table_cible, jrg_id_cible, jrg_metadata_json, jrg_cree_le)
             VALUES
                (:user_id, 'user_data_exported', 'RGPD', :table_cible, :target_id, :metadata, NOW())",
            [
                'user_id' => $userId,
                'table_cible' => $requestId ? 'sav_demandes_rgpd' : 'sav_utilisateurs',
                'target_id' => $requestId ?: $userId,
                'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
        return (int) $this->db->lastInsertId();
    }
}
