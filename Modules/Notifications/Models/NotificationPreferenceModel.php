<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationPreferenceModel extends BaseModel
{
    protected string $table = 'sav_preferences_notifications';
    protected string $colPrefix = 'pno_';

    public function forUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, c.cno_code, c.cno_nom
             FROM sav_preferences_notifications p
             INNER JOIN sav_canaux_notifications c ON c.cno_id = p.pno_canal_notification_id
             WHERE p.pno_utilisateur_id = :user_id
               AND p.pno_supprime_le IS NULL
             ORDER BY p.pno_type_notification ASC, c.cno_nom ASC",
            ['user_id' => $userId]
        );
    }

    public function isAllowed(int $userId, string $eventType, int $channelId): bool
    {
        $row = $this->db->fetch(
            "SELECT pno_est_active
             FROM sav_preferences_notifications
             WHERE pno_utilisateur_id = :user_id
               AND pno_canal_notification_id = :channel_id
               AND pno_type_notification = :event_type
               AND pno_supprime_le IS NULL
             LIMIT 1",
            ['user_id' => $userId, 'channel_id' => $channelId, 'event_type' => $eventType]
        );
        return $row === null || (int) $row['pno_est_active'] === 1;
    }
}
