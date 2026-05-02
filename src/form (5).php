<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Profile\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class GdprRequestModel extends BaseModel
{
    protected string $table = 'sav_gdpr_requests';

    public function createRequest(int $userId, string $type, string $message, string $ip): int
    {
        $this->db->execute(
            "INSERT INTO sav_gdpr_requests
                (grq_uuid, grq_user_id, grq_type, grq_status, grq_message, grq_requested_ip, grq_created_at, grq_updated_at)
             VALUES
                (:uuid, :user_id, :type, 'submitted', :message, :ip, NOW(), NOW())",
            [
                'uuid' => $this->uuid(),
                'user_id' => $userId,
                'type' => $type,
                'message' => $message ?: null,
                'ip' => $ip,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function getForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM sav_gdpr_requests
             WHERE grq_user_id = :user_id
             ORDER BY grq_created_at DESC",
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
