<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Maintenance\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class MaintenanceEventModel extends BaseModel
{
    protected string $table = 'sav_maintenance_events';

    public function record(string $type, string $severity, string $message, ?int $createdBy, string $ip, array $context = []): int
    {
        $this->db->execute(
            "INSERT INTO sav_maintenance_events
                (mev_uuid, mev_event_type, mev_severity, mev_message, mev_context, mev_created_by, mev_created_ip, mev_created_at)
             VALUES
                (:uuid, :type, :severity, :message, :context, :created_by, :ip, NOW())",
            [
                'uuid' => $this->uuid(),
                'type' => $type,
                'severity' => $severity,
                'message' => $message,
                'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_by' => $createdBy,
                'ip' => $ip,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function latest(int $limit = 30): array
    {
        return $this->db->fetchAll(
            "SELECT e.*, u.use_email AS created_by_email
             FROM sav_maintenance_events e
             LEFT JOIN sav_users u ON u.use_id = e.mev_created_by
             ORDER BY e.mev_created_at DESC
             LIMIT " . max(1, min(100, $limit))
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
