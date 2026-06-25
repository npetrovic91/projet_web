<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationContactModel extends BaseModel
{
    protected string $table = 'sav_contacts_societes';
    protected string $colPrefix = 'cts_';

    public function forCompany(int $companyId): array
    {
        return $this->db->fetchAll(
            "SELECT c.cts_id AS nco_id,
                    c.cts_societe_id AS nco_company_id,
                    c.cts_utilisateur_id AS nco_user_id,
                    CASE WHEN c.cts_utilisateur_id IS NULL THEN 'external' ELSE 'internal' END AS nco_contact_type,
                    COALESCE(p.pui_prenom, c.cts_prenom) AS nco_firstname,
                    COALESCE(p.pui_nom, c.cts_nom) AS nco_lastname,
                    COALESCE(u.uti_email, c.cts_email_externe) AS nco_email,
                    NULL AS nco_phone,
                    s.soc_nom AS nco_company_name,
                    NULL AS nco_role_label,
                    'email' AS nco_preferred_channel,
                    CASE WHEN c.cts_supprime_le IS NULL THEN 1 ELSE 0 END AS nco_is_active,
                    c.cts_cree_le AS nco_created_at,
                    c.cts_modifie_le AS nco_updated_at,
                    u.uti_email AS internal_email,
                    p.pui_prenom AS internal_firstname,
                    p.pui_nom AS internal_lastname
             FROM sav_contacts_societes c
             LEFT JOIN sav_utilisateurs u ON u.uti_id = c.cts_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             LEFT JOIN sav_societes s ON s.soc_id = c.cts_societe_id
             WHERE c.cts_societe_id = :company_id
             ORDER BY nco_is_active DESC, nco_lastname ASC, nco_email ASC",
            ['company_id' => $companyId]
        );
    }

    public function find(int $contactId): ?array
    {
        return $this->db->fetch(
            "SELECT c.cts_id AS nco_id,
                    c.cts_societe_id AS nco_company_id,
                    c.cts_utilisateur_id AS nco_user_id,
                    COALESCE(u.uti_email, c.cts_email_externe) AS nco_email,
                    COALESCE(p.pui_prenom, c.cts_prenom) AS nco_firstname,
                    COALESCE(p.pui_nom, c.cts_nom) AS nco_lastname,
                    CASE WHEN c.cts_supprime_le IS NULL THEN 1 ELSE 0 END AS nco_is_active
             FROM sav_contacts_societes c
             LEFT JOIN sav_utilisateurs u ON u.uti_id = c.cts_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             WHERE c.cts_id = :id
             LIMIT 1",
            ['id' => $contactId]
        );
    }

    public function create(array $data, int $createdBy): int
    {
        $userId = trim((string) ($data['user_id'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        $this->db->execute(
            "INSERT INTO sav_contacts_societes
                (cts_societe_id, cts_utilisateur_id, cts_email_externe,
                 cts_prenom, cts_nom, cts_cree_le, cts_cree_par_utilisateur_id)
             VALUES
                (:company_id, :user_id, :email,
                 :firstname, :lastname, NOW(), :created_by)",
            [
                'company_id' => (int) $data['company_id'],
                'user_id' => $userId !== '' ? (int) $userId : null,
                'email' => $userId === '' ? $email : ($email ?: null),
                'firstname' => trim((string) ($data['firstname'] ?? '')) ?: null,
                'lastname' => trim((string) ($data['lastname'] ?? '')) ?: null,
                'created_by' => $createdBy,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function setActive(int $contactId, bool $active, int $updatedBy): bool
    {
        if ($active) {
            return $this->db->execute(
                "UPDATE sav_contacts_societes
                 SET cts_supprime_le = NULL,
                     cts_supprime_par_utilisateur_id = NULL,
                     cts_modifie_le = NOW(),
                     cts_modifie_par_utilisateur_id = :updated_by
                 WHERE cts_id = :id",
                ['updated_by' => $updatedBy, 'id' => $contactId]
            );
        }

        return $this->db->execute(
            "UPDATE sav_contacts_societes
             SET cts_supprime_le = NOW(),
                 cts_supprime_par_utilisateur_id = :updated_by,
                 cts_modifie_le = NOW(),
                 cts_modifie_par_utilisateur_id = :updated_by
             WHERE cts_id = :id",
            ['updated_by' => $updatedBy, 'id' => $contactId]
        );
    }
}
