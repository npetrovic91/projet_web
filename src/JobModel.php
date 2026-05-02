<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationModel extends BaseModel
{
    protected string $table = 'sav_notifications';

    public function queue(?int $userId, ?int $contactId, ?int $eventId, string $channel, string $title, string $message, array $payload = []): int
    {
        $this->db->execute(
            "INSERT INTO sav_notifications
                (ntf_uuid, ntf_user_id, ntf_contact_id, ntf_event_trigger_id, ntf_channel,
                 ntf_title, ntf_message, ntf_body, ntf_type, ntf_payload, ntf_status, ntf_created_at)
             VALUES
                (:uuid, :user_id, :contact_id, :event_id, :channel,
                 :title, :message, :message, 'info', :payload, 'queued', NOW())",
            [
                'uuid' => $this->uuid(),
                'user_id' => $userId,
                'contact_id' => $contactId,
                'event_id' => $eventId,
                'channel' => $channel,
                'title' => $title,
                'message' => $message,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
