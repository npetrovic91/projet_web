<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\GDPR\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class GdprActionModel extends BaseModel
{
    protected string $table = 'sav_gdpr_actions';

    public function record(?int $requestId, ?int $userId, string $action, int $adminId, string $ip, array $details = []): int
    {
        $this->db->execute(
            "INSERT INTO sav_gdpr_actions
                (gac_uuid, gac_request_id, gac_user_id, gac_action, gac_status, gac_details,
                 gac_performed_by, gac_performed_ip, gac_created_at)
             VALUES
                (:uuid, :request_id, :user_id, :action, 'done', :details,
                 :admin_id, :ip, NOW())",
            [
                'uuid' => $this->uuid(),
                'request_id' => $requestId,
                'user_id' => $userId,
                'action' => $action,
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'admin_id' => $adminId,
                'ip' => $ip,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function latest(int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, u.use_email AS target_email, admin.use_email AS admin_email
             FROM sav_gdpr_actions a
             LEFT JOIN sav_users u ON u.use_id = a.gac_user_id
             LEFT JOIN sav_users admin ON admin.use_id = a.gac_performed_by
             ORDER BY a.gac_created_at DESC
             LIMIT " . max(1, min(200, $limit))
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
