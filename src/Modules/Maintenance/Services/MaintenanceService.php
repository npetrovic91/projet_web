<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Maintenance\Services;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Modules\Maintenance\Models\MaintenanceEventModel;
use Nenad\Autosav\Modules\Maintenance\Models\MaintenanceModel;

class MaintenanceService
{
    public function __construct(
        private MaintenanceModel $maintenance,
        private MaintenanceEventModel $events
    ) {}

    public function dashboard(): array
    {
        return [
            'state' => $this->normalizeState($this->maintenance->getState()),
            'indicators' => $this->indicators(),
            'events' => $this->events->latest(),
        ];
    }

    public function update(array $data, int $adminId, string $ip): array
    {
        $active = !empty($data['mtn_is_active']);
        $message = trim((string) ($data['mtn_message'] ?? ''));
        if ($message === '') {
            $message = 'Application temporairement indisponible pour maintenance.';
        }

        $roles = $this->splitLines((string) ($data['mtn_allowed_roles'] ?? 'SUPERADMIN'));
        $ips = $this->splitLines((string) ($data['mtn_allowed_ips'] ?? "127.0.0.1\n::1"));

        $previous = $this->normalizeState($this->maintenance->getState());
        $this->maintenance->updateState($active, $message, $roles, $ips, $adminId);

        $type = $active ? 'maintenance_enabled' : 'maintenance_disabled';
        if ((bool) $previous['is_active'] === $active) {
            $type = 'maintenance_updated';
        }

        $this->events->record($type, 'info', $active ? 'Mode maintenance actif.' : 'Mode maintenance inactif.', $adminId, $ip, [
            'message' => $message,
            'allowed_roles' => $roles,
            'allowed_ips' => $ips,
        ]);

        logger('audit')->info($type, ['admin_id' => $adminId, 'active' => $active]);
        return ['success' => true, 'message' => 'Configuration maintenance enregistree.'];
    }

    private function normalizeState(array $row): array
    {
        return [
            'is_active' => (bool) ($row['mtn_is_active'] ?? false),
            'message' => (string) ($row['mtn_message'] ?? ''),
            'allowed_roles' => json_decode((string) ($row['mtn_allowed_roles'] ?? '[]'), true) ?: [],
            'allowed_ips' => json_decode((string) ($row['mtn_allowed_ips'] ?? '[]'), true) ?: [],
            'started_at' => $row['mtn_started_at'] ?? null,
            'ended_at' => $row['mtn_ended_at'] ?? null,
            'updated_at' => $row['mtn_updated_at'] ?? null,
        ];
    }

    private function indicators(): array
    {
        $db = Database::getInstance();
        return [
            'login_attempts_24h' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_login_attempts WHERE lat_created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)"),
            'failed_logins_24h' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_login_attempts WHERE lat_success = 0 AND lat_created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)"),
            'active_ip_blocks' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_ip_blacklist WHERE ibl_is_active = 1 AND (ibl_expires_at IS NULL OR ibl_expires_at > NOW())"),
            'active_email_blocks' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_email_blacklist WHERE ebl_is_active = 1 AND (ebl_expires_at IS NULL OR ebl_expires_at > NOW())"),
            'open_gdpr_requests' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_gdpr_requests WHERE grq_status IN ('submitted','accepted')"),
            'active_users' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_users WHERE use_is_active = 1 AND use_deleted_at IS NULL"),
        ];
    }

    private function countSafe(Database $db, string $sql): int
    {
        try {
            return (int) $db->fetchColumn($sql);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function splitLines(string $value): array
    {
        $items = preg_split('/[\r\n,]+/', $value) ?: [];
        return array_values(array_filter(array_map('trim', $items), static fn(string $item): bool => $item !== ''));
    }
}
