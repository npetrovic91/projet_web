<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Notifications\Models\NotificationModel;

class NotificationsAjaxController extends AjaxController
{
    private NotificationModel $notifications;

    public function __construct()
    {
        parent::__construct();
        $this->notifications = new NotificationModel();
    }

    public function unreadCount(): void
    {
        $count = $this->notifications->unreadCount(
            (int) $this->user['id'],
            !empty($this->user['active_company_id']) ? (int) $this->user['active_company_id'] : null
        );
        AjaxResponseService::success('Compteur chargé.', ['count' => $count]);
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
        $this->notifications->markRead($notificationId, (int) $this->user['id']);
        AjaxResponseService::success('Notification marquée comme lue.');
    }

    public function markAllRead(): void
    {
        $this->notifications->markAllRead(
            (int) $this->user['id'],
            !empty($this->user['active_company_id']) ? (int) $this->user['active_company_id'] : null
        );
        AjaxResponseService::success('Notifications marquées comme lues.');
    }
}
