<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;

class NotificationsAjaxController extends AjaxController
{
    public function unreadCount(): void
    {
        $row = database()->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_notifications
             WHERE ntf_user_id = :user_id AND ntf_is_read = 0
               AND (ntf_expires_at IS NULL OR ntf_expires_at > NOW())",
            [':user_id' => $this->user['id']]
        );
        AjaxResponseService::success('Compteur charge.', ['count' => (int) ($row['cnt'] ?? 0)]);
    }

    public function unread(): void
    {
        $this->unreadCount();
    }

    public function markRead(string $id = ''): void
    {
        $notificationId = (int) $id;
        if ($notificationId <= 0) {
            AjaxResponseService::badRequest('Notification invalide.');
        }
        database()->execute(
            "UPDATE sav_notifications
             SET ntf_is_read = 1, ntf_read_at = NOW()
             WHERE ntf_id = :id AND ntf_user_id = :user_id",
            [':id' => $notificationId, ':user_id' => $this->user['id']]
        );
        AjaxResponseService::success('Notification marquee comme lue.');
    }

    public function markAllRead(): void
    {
        database()->execute(
            "UPDATE sav_notifications
             SET ntf_is_read = 1, ntf_read_at = NOW()
             WHERE ntf_user_id = :user_id AND ntf_is_read = 0",
            [':user_id' => $this->user['id']]
        );
        AjaxResponseService::success('Notifications marquees comme lues.');
    }
}
