<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;

class AdminAjaxController extends AjaxController
{
    public function stats(): void
    {
        $this->requireAjaxRole([ROLE_SUPERADMIN, 'ADMIN_SECURITE']);
        AjaxResponseService::success('Statistiques chargees.', [
            'login_attempts_24h' => (int) (database()->fetch("SELECT COUNT(*) AS cnt FROM sav_login_attempts WHERE lat_created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['cnt'] ?? 0),
            'blocked_ips' => (int) (database()->fetch("SELECT COUNT(*) AS cnt FROM sav_ip_blacklist WHERE ibl_is_active = 1")['cnt'] ?? 0),
            'blocked_emails' => (int) (database()->fetch("SELECT COUNT(*) AS cnt FROM sav_email_blacklist WHERE ebl_is_active = 1")['cnt'] ?? 0),
        ]);
    }

    public function loginAttempts(): void
    {
        $this->requireAjaxRole([ROLE_SUPERADMIN, 'ADMIN_SECURITE']);
        AjaxResponseService::success('Tentatives chargees.', [
            'items' => database()->fetchAll("SELECT * FROM sav_login_attempts ORDER BY lat_created_at DESC LIMIT 50"),
        ]);
    }

    public function blockedIps(): void
    {
        $this->requireAjaxRole([ROLE_SUPERADMIN, 'ADMIN_SECURITE']);
        AjaxResponseService::success('IP bloquees chargees.', [
            'items' => database()->fetchAll("SELECT * FROM sav_ip_blacklist WHERE ibl_is_active = 1 ORDER BY ibl_created_at DESC LIMIT 50"),
        ]);
    }

    public function blockedEmails(): void
    {
        $this->requireAjaxRole([ROLE_SUPERADMIN, 'ADMIN_SECURITE']);
        AjaxResponseService::success('Emails bloques charges.', [
            'items' => database()->fetchAll("SELECT * FROM sav_email_blacklist WHERE ebl_is_active = 1 ORDER BY ebl_created_at DESC LIMIT 50"),
        ]);
    }
}
