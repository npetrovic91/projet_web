<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class IpBlacklistModel extends BaseModel
{
    private const TABLE = 'sav_ip_blacklist';

    public function findActiveByIp(string $ip): ?array
    {
        return $this->db()->fetch("SELECT * FROM " . self::TABLE . " WHERE ibl_ip = :ip AND ibl_is_active = 1 AND (ibl_expires_at IS NULL OR ibl_expires_at > NOW()) LIMIT 1", [':ip' => $ip]);
    }

    public function blockIp(string $ip, string $reason, string $type = 'auto', int $durationMinutes = 0, int $attemptCount = 0, ?int $createdBy = null): int
    {
        $expiresAt = $durationMinutes > 0 ? date('Y-m-d H:i:s', time() + $durationMinutes * 60) : null;
        $existing = $this->db()->fetch("SELECT ibl_id FROM " . self::TABLE . " WHERE ibl_ip = :ip LIMIT 1", [':ip' => $ip]);
        if ($existing) {
            $this->db()->execute(
                "UPDATE " . self::TABLE . "
                 SET ibl_is_active = 1, ibl_reason = :reason, ibl_type = :type,
                     ibl_expires_at = :expires_at, ibl_attempt_count = ibl_attempt_count + :attempt_count,
                     ibl_update_count = ibl_update_count + 1, ibl_unblocked_by = NULL,
                     ibl_unblocked_at = NULL, ibl_updated_at = NOW()
                 WHERE ibl_ip = :ip",
                [':reason' => $reason, ':type' => $type, ':expires_at' => $expiresAt, ':attempt_count' => $attemptCount, ':ip' => $ip]
            );
            return (int) $existing['ibl_id'];
        }
        $this->db()->execute(
            "INSERT INTO " . self::TABLE . "
             (ibl_uuid, ibl_ip, ibl_reason, ibl_type, ibl_is_active, ibl_expires_at, ibl_attempt_count, ibl_created_by, ibl_created_at, ibl_updated_at)
             VALUES (:uuid, :ip, :reason, :type, 1, :expires_at, :attempt_count, :created_by, NOW(), NOW())",
            [':uuid' => generate_uuid(), ':ip' => $ip, ':reason' => $reason, ':type' => $type, ':expires_at' => $expiresAt, ':attempt_count' => $attemptCount, ':created_by' => $createdBy]
        );
        return (int) $this->db()->lastInsertId();
    }

    public function unblockById(int $id, int $adminId): bool
    {
        return $this->db()->execute("UPDATE " . self::TABLE . " SET ibl_is_active = 0, ibl_unblocked_by = :admin_id, ibl_unblocked_at = NOW(), ibl_updated_at = NOW() WHERE ibl_id = :id", [':admin_id' => $adminId, ':id' => $id]);
    }

    public function findById(int $id): ?array
    {
        return $this->db()->fetch("SELECT * FROM " . self::TABLE . " WHERE ibl_id = :id LIMIT 1", [':id' => $id]);
    }

    public function getActiveBlocks(int $limit = 50, int $offset = 0): array
    {
        return $this->db()->fetchAll("SELECT * FROM " . self::TABLE . " WHERE ibl_is_active = 1 AND (ibl_expires_at IS NULL OR ibl_expires_at > NOW()) ORDER BY ibl_created_at DESC LIMIT :limit OFFSET :offset", [':limit' => $limit, ':offset' => $offset]);
    }

    public function countActive(): int
    {
        $row = $this->db()->fetch("SELECT COUNT(*) AS cnt FROM " . self::TABLE . " WHERE ibl_is_active = 1 AND (ibl_expires_at IS NULL OR ibl_expires_at > NOW())");
        return (int) ($row['cnt'] ?? 0);
    }
}
