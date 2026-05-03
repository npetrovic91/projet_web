<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Maintenance\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class MaintenanceModel extends BaseModel
{
    protected string $table = 'sav_maintenance';

    public function getState(): array
    {
        return $this->db->fetch('SELECT * FROM sav_maintenance WHERE mtn_id = 1') ?? [
            'mtn_id' => 1,
            'mtn_is_active' => 0,
            'mtn_message' => 'Application temporairement indisponible pour maintenance.',
            'mtn_allowed_roles' => json_encode(['SUPERADMIN']),
            'mtn_allowed_ips' => json_encode(['127.0.0.1', '::1']),
        ];
    }

    public function updateState(bool $active, string $message, array $allowedRoles, array $allowedIps, int $updatedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_maintenance
             SET mtn_is_active = :active,
                 mtn_message = :message,
                 mtn_allowed_roles = :roles,
                 mtn_allowed_ips = :ips,
                 mtn_started_at = CASE WHEN :active_started = 1 AND mtn_is_active = 0 THEN NOW() ELSE mtn_started_at END,
                 mtn_ended_at = CASE WHEN :active_ended = 0 THEN NOW() ELSE mtn_ended_at END,
                 mtn_updated_by = :updated_by,
                 mtn_updated_at = NOW()
             WHERE mtn_id = 1",
            [
                'active' => $active,
                'message' => $message,
                'roles' => json_encode(array_values($allowedRoles), JSON_UNESCAPED_UNICODE),
                'ips' => json_encode(array_values($allowedIps), JSON_UNESCAPED_UNICODE),
                'active_started' => $active ? 1 : 0,
                'active_ended' => $active ? 1 : 0,
                'updated_by' => $updatedBy,
            ]
        );
    }
}
