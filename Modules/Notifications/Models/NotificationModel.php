<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationModel extends BaseModel
{
    protected string $table = 'sav_notifications';
    protected string $colPrefix = 'not_';

    /**
     * Compatibilité ancienne signature + insertion conforme au dump SQL.
     */
    public function queue(
        ?int $userId,
        ?int $contactId,
        ?int $eventId,
        string $channel,
        string $title,
        string $message,
        array $payload = [],
        ?int $companyId = null,
        ?int $createdBy = null
    ): int {
        $channelCode = $this->normalizeChannel($channel);
        $channelId = $this->channelId($channelCode);
        $type = (string) ($payload['type'] ?? $payload['event_code'] ?? 'info');
        $priority = isset($payload['priority']) ? (int) $payload['priority'] : 3;
        $targetType = $payload['target_type'] ?? ($contactId ? 'contact_societe' : ($userId ? 'utilisateur' : 'systeme'));
        $targetId = $payload['target_id'] ?? ($contactId ?: $userId);

        $payload['channel'] = $channelCode;
        $payload['contact_id'] = $contactId;
        $payload['event_subscription_id'] = $eventId;

        $this->db->execute(
            "INSERT INTO sav_notifications
                (not_uuid, not_societe_id, not_module_id, not_modele_notification_id,
                 not_type, not_priorite, not_titre, not_message, not_lien_url,
                 not_cible_type, not_cible_id, not_donnees_json, not_expire_le,
                 not_cree_le, not_cree_par_utilisateur_id)
             VALUES
                (:uuid, :company_id, :module_id, :template_id,
                 :type, :priority, :title, :message, :link_url,
                 :target_type, :target_id, :payload, :expires_at,
                 NOW(), :created_by)",
            [
                'uuid' => $this->uuid(),
                'company_id' => $companyId,
                'module_id' => isset($payload['module_id']) ? (int) $payload['module_id'] : null,
                'template_id' => isset($payload['template_id']) ? (int) $payload['template_id'] : null,
                'type' => mb_substr($type, 0, 80),
                'priority' => max(1, min(9, $priority)),
                'title' => $title,
                'message' => $message,
                'link_url' => $payload['link_url'] ?? null,
                'target_type' => $targetType,
                'target_id' => $targetId ? (int) $targetId : null,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'expires_at' => $payload['expires_at'] ?? null,
                'created_by' => $createdBy,
            ]
        );
        $notificationId = (int) $this->db->lastInsertId();

        if ($userId !== null && $userId > 0) {
            $this->addRecipient($notificationId, $userId, $channelId, $channelCode === 'application');
        }

        if ($channelCode === 'email') {
            $this->logEmail($notificationId, $companyId, $userId, $contactId, $type, $title, $message, $payload);
        }

        return $notificationId;
    }

    public function addRecipient(int $notificationId, int $userId, ?int $channelId, bool $markSent = false): void
    {
        $this->db->execute(
            "INSERT INTO sav_destinataires_notifications
                (dno_notification_id, dno_utilisateur_id, dno_canal_notification_id,
                 dno_envoye_le, dno_cree_le)
             VALUES
                (:notification_id, :user_id, :channel_id,
                 :sent_at, NOW())",
            [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'channel_id' => $channelId,
                'sent_at' => $markSent ? date('Y-m-d H:i:s') : null,
            ]
        );
    }

    public function unreadCount(int $userId, ?int $companyId = null): int
    {
        $params = ['user_id' => $userId];
        $whereCompany = '';
        if ($companyId) {
            $whereCompany = ' AND (n.not_societe_id IS NULL OR n.not_societe_id = :company_id)';
            $params['company_id'] = $companyId;
        }
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt
             FROM sav_destinataires_notifications d
             INNER JOIN sav_notifications n ON n.not_id = d.dno_notification_id
             WHERE d.dno_utilisateur_id = :user_id
               AND d.dno_supprime_le IS NULL
               AND d.dno_lu_le IS NULL
               AND n.not_supprime_le IS NULL
               AND (n.not_expire_le IS NULL OR n.not_expire_le > NOW())
               {$whereCompany}",
            $params
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_destinataires_notifications
             SET dno_lu_le = COALESCE(dno_lu_le, NOW()),
                 dno_modifie_le = NOW()
             WHERE dno_notification_id = :id
               AND dno_utilisateur_id = :user_id
               AND dno_supprime_le IS NULL",
            ['id' => $notificationId, 'user_id' => $userId]
        );
    }

    public function markAllRead(int $userId, ?int $companyId = null): bool
    {
        $params = ['user_id' => $userId];
        $whereCompany = '';
        if ($companyId) {
            $whereCompany = ' AND (n.not_societe_id IS NULL OR n.not_societe_id = :company_id)';
            $params['company_id'] = $companyId;
        }
        return $this->db->execute(
            "UPDATE sav_destinataires_notifications d
             INNER JOIN sav_notifications n ON n.not_id = d.dno_notification_id
             SET d.dno_lu_le = COALESCE(d.dno_lu_le, NOW()),
                 d.dno_modifie_le = NOW()
             WHERE d.dno_utilisateur_id = :user_id
               AND d.dno_lu_le IS NULL
               AND d.dno_supprime_le IS NULL
               {$whereCompany}",
            $params
        );
    }

    public function recentForCompany(?int $companyId, int $limit = 20): array
    {
        $params = ['limit' => max(1, min(50, $limit))];
        $where = 'n.not_supprime_le IS NULL';
        if ($companyId) {
            $where .= ' AND (n.not_societe_id IS NULL OR n.not_societe_id = :company_id)';
            $params['company_id'] = $companyId;
        }
        return $this->db->fetchAll(
            "SELECT n.*, COUNT(d.dno_id) AS destinataires_total,
                    SUM(CASE WHEN d.dno_lu_le IS NULL THEN 1 ELSE 0 END) AS destinataires_non_lus
             FROM sav_notifications n
             LEFT JOIN sav_destinataires_notifications d ON d.dno_notification_id = n.not_id AND d.dno_supprime_le IS NULL
             WHERE {$where}
             GROUP BY n.not_id
             ORDER BY n.not_cree_le DESC
             LIMIT :limit",
            $params
        );
    }

    private function logEmail(int $notificationId, ?int $companyId, ?int $userId, ?int $contactId, string $type, string $title, string $message, array $payload): void
    {
        $email = $payload['email'] ?? null;
        if (!$email && $contactId) {
            $contact = $this->db->fetch(
                "SELECT COALESCE(u.uti_email, c.cts_email_externe) AS email
                 FROM sav_contacts_societes c
                 LEFT JOIN sav_utilisateurs u ON u.uti_id = c.cts_utilisateur_id
                 WHERE c.cts_id = :id",
                ['id' => $contactId]
            );
            $email = $contact['email'] ?? null;
        }

        $this->db->execute(
            "INSERT INTO sav_journaux_emails
                (jme_modele_email_id, jme_societe_expediteur_id, jme_societe_destinataire_id,
                 jme_utilisateur_destinataire_id, jme_email_destinataire, jme_type_evenement,
                 jme_sujet, jme_corps, jme_cree_le)
             VALUES
                (:template_id, :sender_company_id, :recipient_company_id,
                 :user_id, :email, :event_type,
                 :subject, :body, NOW())",
            [
                'template_id' => isset($payload['email_template_id']) ? (int) $payload['email_template_id'] : null,
                'sender_company_id' => $companyId,
                'recipient_company_id' => $companyId,
                'user_id' => $userId,
                'email' => $email,
                'event_type' => $type,
                'subject' => $title,
                'body' => $message . "\n\nNotification #" . $notificationId,
            ]
        );
    }

    private function channelId(string $code): ?int
    {
        $row = $this->db->fetch(
            "SELECT cno_id FROM sav_canaux_notifications
             WHERE cno_code = :code AND cno_supprime_le IS NULL
             LIMIT 1",
            ['code' => $code]
        );
        return $row ? (int) $row['cno_id'] : null;
    }

    private function normalizeChannel(string $channel): string
    {
        $channel = mb_strtolower(trim($channel));
        return match ($channel) {
            'app', 'application', 'interne' => 'application',
            'mail', 'email' => 'email',
            'webhook' => 'webhook',
            default => $channel ?: 'application',
        };
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
