<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Administration\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Modèle de supervision sécurité pour l'interface d'administration.
 * Regroupe les requêtes de consultation des données de sécurité.
 */
class SecurityMonitoringModel extends BaseModel
{
    /**
     * Statistiques globales de sécurité pour le widget admin.
     *
     * @param int $lastHours Fenêtre en heures
     * @return array
     */
    public function getSecurityStats(int $lastHours = 24): array
    {
        $db = $this->db();

        $totalAttempts = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_login_attempts
             WHERE lat_created_at >= DATE_SUB(NOW(), INTERVAL :h HOUR)",
            [':h' => $lastHours]
        );

        $failedAttempts = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_login_attempts
             WHERE lat_success = 0
               AND lat_created_at >= DATE_SUB(NOW(), INTERVAL :h HOUR)",
            [':h' => $lastHours]
        );

        $successAttempts = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_login_attempts
             WHERE lat_success = 1
               AND lat_created_at >= DATE_SUB(NOW(), INTERVAL :h HOUR)",
            [':h' => $lastHours]
        );

        $activeIpBlocks = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_ip_blacklist
             WHERE ibl_is_active = 1
               AND (ibl_expires_at IS NULL OR ibl_expires_at > NOW())"
        );

        $activeEmailBlocks = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_email_blacklist
             WHERE ebl_is_active = 1
               AND (ebl_expires_at IS NULL OR ebl_expires_at > NOW())"
        );

        return [
            'total_attempts'     => (int) ($totalAttempts['cnt'] ?? 0),
            'failed_attempts'    => (int) ($failedAttempts['cnt'] ?? 0),
            'success_attempts'   => (int) ($successAttempts['cnt'] ?? 0),
            'active_ip_blocks'   => (int) ($activeIpBlocks['cnt'] ?? 0),
            'active_email_blocks'=> (int) ($activeEmailBlocks['cnt'] ?? 0),
            'window_hours'       => $lastHours,
        ];
    }

    /**
     * Top des IPs avec le plus d'échecs récents.
     *
     * @param int $limit
     * @return array
     */
    public function getTopFailedIps(int $limit = 10): array
    {
        return $this->db()->fetchAll(
            "SELECT lat_ip,
                    COUNT(*) AS failure_count,
                    MAX(lat_created_at) AS last_attempt,
                    GROUP_CONCAT(DISTINCT lat_email ORDER BY lat_created_at DESC SEPARATOR ', ') AS emails_tried
             FROM sav_login_attempts
             WHERE lat_success = 0
               AND lat_created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY lat_ip
             ORDER BY failure_count DESC
             LIMIT :limit",
            [':limit' => $limit]
        );
    }

    /**
     * Tentatives de connexion récentes paginées.
     *
     * @param int $limit
     * @param int $offset
     * @param string|null $filterIp
     * @param string|null $filterEmail
     * @param int|null $onlyFailed 1 = échecs seulement, 0 = succès, null = tous
     * @return array
     */
    public function getAttempts(
        int     $limit       = 50,
        int     $offset      = 0,
        ?string $filterIp    = null,
        ?string $filterEmail = null,
        ?int    $onlyFailed  = null
    ): array {
        $conditions = ['1=1'];
        $params     = [':limit' => $limit, ':offset' => $offset];

        if ($filterIp !== null) {
            $conditions[] = 'lat_ip LIKE :ip';
            $params[':ip'] = '%' . $filterIp . '%';
        }

        if ($filterEmail !== null) {
            $conditions[] = 'lat_email LIKE :email';
            $params[':email'] = '%' . $filterEmail . '%';
        }

        if ($onlyFailed !== null) {
            $conditions[] = 'lat_success = :success';
            $params[':success'] = $onlyFailed === 0 ? 1 : 0;
        }

        $where = implode(' AND ', $conditions);

        return $this->db()->fetchAll(
            "SELECT lat_id, lat_ip, lat_email, lat_user_id, lat_success,
                    lat_failure_reason, lat_user_agent, lat_created_at
             FROM sav_login_attempts
             WHERE {$where}
             ORDER BY lat_created_at DESC
             LIMIT :limit OFFSET :offset",
            $params
        );
    }

    /**
     * Compte total des tentatives (pour pagination).
     *
     * @param string|null $filterIp
     * @param string|null $filterEmail
     * @return int
     */
    public function countAttempts(?string $filterIp = null, ?string $filterEmail = null): int
    {
        $conditions = ['1=1'];
        $params     = [];

        if ($filterIp !== null) {
            $conditions[] = 'lat_ip LIKE :ip';
            $params[':ip'] = '%' . $filterIp . '%';
        }

        if ($filterEmail !== null) {
            $conditions[] = 'lat_email LIKE :email';
            $params[':email'] = '%' . $filterEmail . '%';
        }

        $where = implode(' AND ', $conditions);
        $row   = $this->db()->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_login_attempts WHERE {$where}",
            $params
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Historique des déblocages.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUnblockHistory(int $limit = 30, int $offset = 0): array
    {
        return $this->db()->fetchAll(
            "SELECT ubh.*, u.use_firstname, u.use_lastname
             FROM sav_unblock_history ubh
             LEFT JOIN sav_users u ON u.use_id = ubh.ubh_unblocked_by
             ORDER BY ubh.ubh_created_at DESC
             LIMIT :limit OFFSET :offset",
            [':limit' => $limit, ':offset' => $offset]
        );
    }
}

src/Modules/Administration/Controllers/SecurityController.php
