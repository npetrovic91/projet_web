<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationAuditModel extends BaseModel
{
    protected string $table = 'sav_journaux_audit';
    protected string $colPrefix = 'jau_';

    public function record(?int $ruleId, ?int $notificationId, string $action, ?int $createdBy, string $ip, array $details = []): int
    {
        $details = array_merge($details, [
            'abonnement_evenement_id' => $ruleId,
            'notification_id' => $notificationId,
        ]);

        $this->db->execute(
            "INSERT INTO sav_journaux_audit
                (jau_utilisateur_id, jau_societe_id, jau_action, jau_table_cible,
                 jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le)
             VALUES
                (:user_id, :company_id, :action, :target_table,
                 :target_id, INET6_ATON(:ip), :metadata, NOW())",
            [
                'user_id' => $createdBy,
                'company_id' => isset($details['company_id']) ? (int) $details['company_id'] : null,
                'action' => $action,
                'target_table' => $notificationId ? 'sav_notifications' : 'sav_abonnements_evenements',
                'target_id' => $notificationId ?: $ruleId,
                'ip' => $ip,
                'metadata' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
        return (int) $this->db->lastInsertId();
    }
}
