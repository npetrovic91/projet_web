<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class EmailTokenModel extends BaseModel
{
    private const TABLE = 'sav_email_tokens';

    public function invalidatePrevious(int $userId): bool
    {
        return $this->db()->execute("UPDATE " . self::TABLE . " SET etk_used_at = NOW() WHERE etk_user_id = :user_id AND etk_used_at IS NULL", [':user_id' => $userId]);
    }

    public function create(int $userId, string $tokenHash, int $expiryHours): int
    {
        $this->db()->execute(
            "INSERT INTO " . self::TABLE . " (etk_user_id, etk_token_hash, etk_expires_at, etk_created_at) VALUES (:user_id, :hash, :expires, NOW())",
            [':user_id' => $userId, ':hash' => $tokenHash, ':expires' => date('Y-m-d H:i:s', time() + $expiryHours * 3600)]
        );
        return (int) $this->db()->lastInsertId();
    }

    public function findValidByHash(string $tokenHash): ?array
    {
        return $this->db()->fetch(
            "SELECT etk.*, u.use_email, u.use_email_verified_at
             FROM " . self::TABLE . " etk
             JOIN sav_users u ON u.use_id = etk.etk_user_id
             WHERE etk.etk_token_hash = :hash AND etk.etk_used_at IS NULL AND etk.etk_expires_at > NOW()
             LIMIT 1",
            [':hash' => $tokenHash]
        );
    }

    public function markUsed(int $id, string $ip): bool
    {
        return $this->db()->execute("UPDATE " . self::TABLE . " SET etk_used_at = NOW(), etk_used_ip = :ip WHERE etk_id = :id", [':ip' => $ip, ':id' => $id]);
    }
}
