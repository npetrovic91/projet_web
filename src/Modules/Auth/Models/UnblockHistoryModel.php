<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class UnblockHistoryModel extends BaseModel
{
    private const TABLE = 'sav_unblock_history';

    public function record(string $type, string $target, int $blockedTableId, int $adminId, string $adminIp, string $reason): int
    {
        $this->db()->execute(
            "INSERT INTO " . self::TABLE . "
             (ubh_type, ubh_target, ubh_blocked_table_id, ubh_unblocked_by, ubh_reason, ubh_admin_ip, ubh_created_at)
             VALUES (:type, :target, :blocked_table_id, :admin_id, :reason, :admin_ip, NOW())",
            [':type' => $type, ':target' => $target, ':blocked_table_id' => $blockedTableId, ':admin_id' => $adminId, ':reason' => $reason, ':admin_ip' => $adminIp]
        );
        return (int) $this->db()->lastInsertId();
    }
}
