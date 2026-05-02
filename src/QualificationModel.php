<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class LoginAttemptModel extends BaseModel
{
    private const TABLE = 'sav_login_attempts';

    public function record(string $ip, string $email, bool $success, ?string $failureReason = null, ?int $userId = null, ?string $userAgent = null, array $context = []): int
    {
        $this->db()->execute(
            "INSERT INTO " . self::TABLE . "
             (lat_ip, lat_email, lat_user_id, lat_success, lat_failure_reason, lat_user_agent, lat_context, lat_created_at)
             VALUES (:ip, :email, :user_id, :success, :reason, :ua, :context, NOW())",
            [
                ':ip' => $ip,
                ':email' => mb_strtolower(trim($email)),
                ':user_id' => $userId,
                ':success' => (int) $success,
                ':reason' => $failureReason,
                ':ua' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
                ':context' => $context === [] ? null : json_encode($context, JSON_UNESCAPED_UNICODE),
            ]
        );
        return (int) $this->db()->lastInsertId();
    }

    public function countRecentFailuresByIp(string $ip, int $minutes): int
    {
        $row = $this->db()->fetch(
            "SELECT COUNT(*) AS cnt FROM " . self::TABLE . "
             WHERE lat_ip = :ip AND lat_success = 0
               AND lat_created_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)",
            [':ip' => $ip, ':minutes' => $minutes]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function countRecentFailuresByEmail(string $email, int $minutes): int
    {
        $row = $this->db()->fetch(
            "SELECT COUNT(*) AS cnt FROM " . self::TABLE . "
             WHERE lat_email = :email AND lat_success = 0
               AND lat_created_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)",
            [':email' => mb_strtolower(trim($email)), ':minutes' => $minutes]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function getRecent(int $limit = 50, int $offset = 0): array
    {
        return $this->db()->fetchAll(
            "SELECT * FROM " . self::TABLE . " ORDER BY lat_created_at DESC LIMIT :limit OFFSET :offset",
            [':limit' => $limit, ':offset' => $offset]
        );
    }
}
