<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationAuditModel extends BaseModel
{
    protected string $table = 'sav_notification_audit';

    public function record(?int $ruleId, ?int $notificationId, string $action, ?int $createdBy, string $ip, array $details = []): int
    {
        $this->db->execute(
            "INSERT INTO sav_notification_audit
                (nau_uuid, nau_rule_id, nau_notification_id, nau_action, nau_details, nau_created_by, nau_created_ip, nau_created_at)
             VALUES
                (:uuid, :rule_id, :notification_id, :action, :details, :created_by, :ip, NOW())",
            [
                'uuid' => $this->uuid(),
                'rule_id' => $ruleId,
                'notification_id' => $notificationId,
                'action' => $action,
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_by' => $createdBy,
                'ip' => $ip,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
