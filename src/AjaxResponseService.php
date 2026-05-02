<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class TermsAcceptanceModel extends BaseModel
{
    private const TABLE = 'sav_terms_acceptances';

    public function record(int $userId, int $versionId, string $action, string $ip, string $userAgent): int
    {
        $this->db()->execute(
            "INSERT INTO " . self::TABLE . "
             (tra_user_id, tra_terms_version_id, tra_action, tra_ip, tra_user_agent, tra_created_at)
             VALUES (:user_id, :version_id, :action, :ip, :ua, NOW())",
            [':user_id' => $userId, ':version_id' => $versionId, ':action' => $action, ':ip' => $ip, ':ua' => mb_substr($userAgent, 0, 500)]
        );
        return (int) $this->db()->lastInsertId();
    }

    public function findLastAcceptedByUser(int $userId): ?array
    {
        return $this->db()->fetch(
            "SELECT tra.*, trv.trv_version
             FROM " . self::TABLE . " tra
             JOIN sav_terms_versions trv ON trv.trv_id = tra.tra_terms_version_id
             WHERE tra.tra_user_id = :user_id AND tra.tra_action = 'accepted'
             ORDER BY tra.tra_created_at DESC
             LIMIT 1",
            [':user_id' => $userId]
        );
    }
}
