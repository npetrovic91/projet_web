<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Dashboard\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class DashboardWidgetModel extends BaseModel
{
    public function getWidgetsForRoles(array $roles): array
    {
        if (in_array(ROLE_SUPERADMIN, $roles, true)) {
            return $this->db()->fetchAll(
                "SELECT * FROM sav_dashboard_widgets
                 WHERE dwi_is_active = 1
                 ORDER BY dwi_default_order ASC, dwi_label ASC"
            );
        }

        $rows = $this->db()->fetchAll(
            "SELECT * FROM sav_dashboard_widgets
             WHERE dwi_is_active = 1
             ORDER BY dwi_default_order ASC, dwi_label ASC"
        );

        return array_values(array_filter($rows, function (array $row) use ($roles): bool {
            $allowed = json_decode((string) ($row['dwi_roles_json'] ?? '[]'), true) ?: [];
            foreach ($roles as $role) {
                if (in_array($role, $allowed, true)) {
                    return true;
                }
            }
            return false;
        }));
    }

    public function getWidgetByCode(string $code): ?array
    {
        return $this->db()->fetch(
            "SELECT * FROM sav_dashboard_widgets
             WHERE dwi_code = :code AND dwi_is_active = 1
             LIMIT 1",
            [':code' => $code]
        );
    }

    public function getUserWidgetConfig(int $userId): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT * FROM sav_user_dashboard_widgets WHERE udw_user_id = :user_id",
            [':user_id' => $userId]
        );

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['udw_widget_id']] = $row;
        }
        return $indexed;
    }
}
