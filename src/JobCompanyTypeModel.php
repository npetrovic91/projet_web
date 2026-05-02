<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationContactModel extends BaseModel
{
    protected string $table = 'sav_notification_contacts';

    public function forCompany(int $companyId): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, u.use_firstname AS internal_firstname, u.use_lastname AS internal_lastname
             FROM sav_notification_contacts c
             LEFT JOIN sav_users u ON u.use_id = c.nco_user_id
             WHERE c.nco_company_id = :company_id
             ORDER BY c.nco_is_active DESC, c.nco_lastname ASC, c.nco_email ASC",
            ['company_id' => $companyId]
        );
    }

    public function create(array $data, int $createdBy): int
    {
        $userId = $data['user_id'] ?? '';
        $this->db->execute(
            "INSERT INTO sav_notification_contacts
                (nco_uuid, nco_company_id, nco_user_id, nco_contact_type, nco_firstname,
                 nco_lastname, nco_email, nco_phone, nco_company_name, nco_role_label,
                 nco_preferred_channel, nco_is_active, nco_created_by, nco_updated_by,
                 nco_created_at, nco_updated_at)
             VALUES
                (:uuid, :company_id, :user_id, :contact_type, :firstname,
                 :lastname, :email, :phone, :company_name, :role_label,
                 :preferred_channel, 1, :created_by, :created_by, NOW(), NOW())",
            [
                'uuid' => $this->uuid(),
                'company_id' => (int) $data['company_id'],
                'user_id' => $userId !== '' ? (int) $userId : null,
                'contact_type' => $userId !== '' ? 'internal' : 'external',
                'firstname' => trim((string) ($data['firstname'] ?? '')) ?: null,
                'lastname' => trim((string) ($data['lastname'] ?? '')) ?: null,
                'email' => strtolower(trim((string) $data['email'])),
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'company_name' => trim((string) ($data['company_name'] ?? '')) ?: null,
                'role_label' => trim((string) ($data['role_label'] ?? '')) ?: null,
                'preferred_channel' => $data['preferred_channel'] ?? 'email',
                'created_by' => $createdBy,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function setActive(int $contactId, bool $active, int $updatedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_notification_contacts
             SET nco_is_active = :active,
                 nco_updated_by = :updated_by,
                 nco_updated_at = NOW()
             WHERE nco_id = :id",
            ['active' => $active, 'updated_by' => $updatedBy, 'id' => $contactId]
        );
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
