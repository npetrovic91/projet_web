<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class EmailBlacklistModel extends BaseModel
{
    private const TABLE = 'sav_email_blacklist';

    public function findActiveByEmail(string $email): ?array
    {
        return $this->db()->fetch("SELECT * FROM " . self::TABLE . " WHERE ebl_email = :email AND ebl_is_active = 1 AND (ebl_expires_at IS NULL OR ebl_expires_at > NOW()) LIMIT 1", [':email' => mb_strtolower(trim($email))]);
    }

    public function blockEmail(string $email, string $reason, string $type = 'auto', int $durationMinutes = 0, int $attemptCount = 0, ?int $createdBy = null): int
    {
        $email = mb_strtolower(trim($email));
        $expiresAt = $durationMinutes > 0 ? date('Y-m-d H:i:s', time() + $durationMinutes * 60) : null;
        $existing = $this->db()->fetch("SELECT ebl_id FROM " . self::TABLE . " WHERE ebl_email = :email LIMIT 1", [':email' => $email]);
        if ($existing) {
            $this->db()->execute(
                "UPDATE " . self::TABLE . "
                 SET ebl_is_active = 1, ebl_reason = :reason, ebl_type = :type,
                     ebl_expires_at = :expires_at, ebl_attempt_count = ebl_attempt_count + :attempt_count,
                     ebl_unblocked_by = NULL, ebl_unblocked_at = NULL, ebl_updated_at = NOW()
                 WHERE ebl_email = :email",
                [':reason' => $reason, ':type' => $type, ':expires_at' => $expiresAt, ':attempt_count' => $attemptCount, ':email' => $email]
            );
            return (int) $existing['ebl_id'];
        }
        $this->db()->execute(
            "INSERT INTO " . self::TABLE . "
             (ebl_uuid, ebl_email, ebl_reason, ebl_type, ebl_is_active, ebl_expires_at, ebl_attempt_count, ebl_created_by, ebl_created_at, ebl_updated_at)
             VALUES (:uuid, :email, :reason, :type, 1, :expires_at, :attempt_count, :created_by, NOW(), NOW())",
            [':uuid' => generate_uuid(), ':email' => $email, ':reason' => $reason, ':type' => $type, ':expires_at' => $expiresAt, ':attempt_count' => $attemptCount, ':created_by' => $createdBy]
        );
        return (int) $this->db()->lastInsertId();
    }

    public function unblockById(int $id, int $adminId): bool
    {
        return $this->db()->execute("UPDATE " . self::TABLE . " SET ebl_is_active = 0, ebl_unblocked_by = :admin_id, ebl_unblocked_at = NOW(), ebl_updated_at = NOW() WHERE ebl_id = :id", [':admin_id' => $adminId, ':id' => $id]);
    }

    public function findById(int $id): ?array
    {
        return $this->db()->fetch("SELECT * FROM " . self::TABLE . " WHERE ebl_id = :id LIMIT 1", [':id' => $id]);
    }

    public function getActiveBlocks(int $limit = 50, int $offset = 0): array
    {
        return $this->db()->fetchAll("SELECT * FROM " . self::TABLE . " WHERE ebl_is_active = 1 AND (ebl_expires_at IS NULL OR ebl_expires_at > NOW()) ORDER BY ebl_created_at DESC LIMIT :limit OFFSET :offset", [':limit' => $limit, ':offset' => $offset]);
    }

    public function countActive(): int
    {
        $row = $this->db()->fetch("SELECT COUNT(*) AS cnt FROM " . self::TABLE . " WHERE ebl_is_active = 1 AND (ebl_expires_at IS NULL OR ebl_expires_at > NOW())");
        return (int) ($row['cnt'] ?? 0);
    }
}
