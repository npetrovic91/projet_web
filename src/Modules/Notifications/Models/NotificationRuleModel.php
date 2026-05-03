<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationRuleModel extends BaseModel
{
    protected string $table = 'sav_notification_rules';

    public function forCompany(int $companyId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, e.evt_code, e.evt_label, c.nco_email, c.nco_firstname, c.nco_lastname
             FROM sav_notification_rules r
             INNER JOIN sav_event_triggers e ON e.evt_id = r.nru_event_trigger_id
             INNER JOIN sav_notification_contacts c ON c.nco_id = r.nru_contact_id
             WHERE r.nru_company_id = :company_id
             ORDER BY r.nru_is_active DESC, e.evt_label ASC, c.nco_email ASC",
            ['company_id' => $companyId]
        );
    }

    public function create(array $data, int $createdBy): int
    {
        $channels = array_values(array_filter((array) ($data['channels'] ?? ['email'])));
        if ($channels === []) {
            $channels = ['email'];
        }
        $this->db->execute(
            "INSERT INTO sav_notification_rules
                (nru_uuid, nru_company_id, nru_event_trigger_id, nru_contact_id, nru_channels,
                 nru_is_active, nru_created_by, nru_updated_by, nru_created_at, nru_updated_at)
             VALUES
                (:uuid, :company_id, :event_id, :contact_id, :channels,
                 1, :created_by, :created_by, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                nru_channels = VALUES(nru_channels),
                nru_is_active = 1,
                nru_updated_by = VALUES(nru_updated_by),
                nru_updated_at = NOW()",
            [
                'uuid' => $this->uuid(),
                'company_id' => (int) $data['company_id'],
                'event_id' => (int) $data['event_trigger_id'],
                'contact_id' => (int) $data['contact_id'],
                'channels' => json_encode($channels, JSON_UNESCAPED_UNICODE),
                'created_by' => $createdBy,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function setActive(int $ruleId, bool $active, int $updatedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_notification_rules
             SET nru_is_active = :active,
                 nru_updated_by = :updated_by,
                 nru_updated_at = NOW()
             WHERE nru_id = :id",
            ['active' => $active, 'updated_by' => $updatedBy, 'id' => $ruleId]
        );
    }

    public function activeRulesForEvent(string $eventCode, int $companyId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, c.*, e.evt_id, e.evt_code, e.evt_label
             FROM sav_notification_rules r
             INNER JOIN sav_event_triggers e ON e.evt_id = r.nru_event_trigger_id
             INNER JOIN sav_notification_contacts c ON c.nco_id = r.nru_contact_id
             WHERE e.evt_code = :event_code
               AND r.nru_company_id = :company_id
               AND r.nru_is_active = 1
               AND c.nco_is_active = 1
               AND e.evt_is_active = 1",
            ['event_code' => strtoupper($eventCode), 'company_id' => $companyId]
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
