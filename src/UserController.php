<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class PasswordResetTokenModel extends BaseModel
{
    private const TABLE = 'sav_password_reset_tokens';

    public function invalidatePrevious(int $userId): bool
    {
        return $this->db()->execute("UPDATE " . self::TABLE . " SET prt_used_at = NOW() WHERE prt_user_id = :user_id AND prt_used_at IS NULL", [':user_id' => $userId]);
    }

    public function create(int $userId, string $tokenHash, int $expiryHours): int
    {
        $this->db()->execute(
            "INSERT INTO " . self::TABLE . " (prt_user_id, prt_token_hash, prt_expires_at, prt_created_at) VALUES (:user_id, :hash, :expires, NOW())",
            [':user_id' => $userId, ':hash' => $tokenHash, ':expires' => date('Y-m-d H:i:s', time() + $expiryHours * 3600)]
        );
        return (int) $this->db()->lastInsertId();
    }

    public function findValidByHash(string $tokenHash): ?array
    {
        return $this->db()->fetch(
            "SELECT prt.*, u.use_email, u.use_id
             FROM " . self::TABLE . " prt
             JOIN sav_users u ON u.use_id = prt.prt_user_id
             WHERE prt.prt_token_hash = :hash AND prt.prt_used_at IS NULL AND prt.prt_expires_at > NOW()
             LIMIT 1",
            [':hash' => $tokenHash]
        );
    }

    public function markUsed(int $id, string $ip): bool
    {
        return $this->db()->execute("UPDATE " . self::TABLE . " SET prt_used_at = NOW(), prt_used_ip = :ip WHERE prt_id = :id", [':ip' => $ip, ':id' => $id]);
    }
}
