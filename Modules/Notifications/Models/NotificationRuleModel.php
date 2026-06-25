<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationRuleModel extends BaseModel
{
    /**
     * Table réelle du dump : abonnements internes aux événements applicatifs.
     * Les anciens noms nru_* sont conservés comme alias de sortie.
     */
    protected string $table = 'sav_abonnements_evenements';
    protected string $colPrefix = 'abe_';

    public function forCompany(int $companyId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT a.*, m.mod_code, m.mod_nom
             FROM sav_abonnements_evenements a
             LEFT JOIN sav_modules m ON m.mod_id = a.abe_module_id
             WHERE a.abe_supprime_le IS NULL
               AND a.abe_action_type = 'notification'
             ORDER BY a.abe_est_actif DESC, a.abe_code_evenement ASC, a.abe_nom ASC"
        );

        $contacts = $this->contactsMap($companyId);
        $result = [];
        foreach ($rows as $row) {
            $config = $this->decodeConfig($row['abe_action_configuration_json'] ?? null);
            $configCompanyId = isset($config['company_id']) ? (int) $config['company_id'] : null;
            if ($configCompanyId !== null && $configCompanyId !== $companyId) {
                continue;
            }
            $contactId = isset($config['contact_id']) ? (int) $config['contact_id'] : null;
            $contact = $contactId && isset($contacts[$contactId]) ? $contacts[$contactId] : null;
            $channels = $config['channels'] ?? ['email'];
            $result[] = [
                'nru_id' => (int) $row['abe_id'],
                'nru_company_id' => $configCompanyId,
                'nru_event_code' => strtoupper((string) $row['abe_code_evenement']),
                'nru_contact_id' => $contactId,
                'nru_channels' => json_encode($channels, JSON_UNESCAPED_UNICODE),
                'nru_is_active' => (int) $row['abe_est_actif'],
                'evt_id' => (int) $row['abe_id'],
                'evt_code' => strtoupper((string) $row['abe_code_evenement']),
                'evt_label' => (string) $row['abe_nom'],
                'nco_id' => $contact['nco_id'] ?? null,
                'nco_email' => $contact['nco_email'] ?? ($config['email'] ?? '-'),
                'nco_firstname' => $contact['nco_firstname'] ?? null,
                'nco_lastname' => $contact['nco_lastname'] ?? null,
                'action_type' => $row['abe_action_type'],
                'config' => $config,
            ];
        }
        return $result;
    }

    public function create(array $data, int $createdBy): int
    {
        $channels = array_values(array_filter((array) ($data['channels'] ?? ['email'])));
        if ($channels === []) {
            $channels = ['email'];
        }

        $eventCode = strtoupper(trim((string) ($data['event_code'] ?? $data['event_trigger_code'] ?? '')));
        $eventName = trim((string) ($data['event_name'] ?? $eventCode));
        $config = [
            'company_id' => (int) $data['company_id'],
            'contact_id' => isset($data['contact_id']) ? (int) $data['contact_id'] : null,
            'channels' => $channels,
            'template_id' => !empty($data['template_id']) ? (int) $data['template_id'] : null,
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'message' => trim((string) ($data['message'] ?? '')) ?: null,
            'priority' => isset($data['priority']) ? (int) $data['priority'] : 3,
        ];

        $this->db->execute(
            "INSERT INTO sav_abonnements_evenements
                (abe_code_evenement, abe_module_id, abe_nom, abe_action_type,
                 abe_action_configuration_json, abe_est_actif, abe_cree_le,
                 abe_cree_par_utilisateur_id)
             VALUES
                (:event_code, :module_id, :name, 'notification',
                 :config, 1, NOW(), :created_by)",
            [
                'event_code' => $eventCode,
                'module_id' => !empty($data['module_id']) ? (int) $data['module_id'] : null,
                'name' => $eventName ?: $eventCode,
                'config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_by' => $createdBy,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function setActive(int $ruleId, bool $active, int $updatedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_abonnements_evenements
             SET abe_est_actif = :active,
                 abe_modifie_par_utilisateur_id = :updated_by,
                 abe_modifie_le = NOW()
             WHERE abe_id = :id",
            ['active' => $active ? 1 : 0, 'updated_by' => $updatedBy, 'id' => $ruleId]
        );
    }

    public function activeRulesForEvent(string $eventCode, int $companyId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT *
             FROM sav_abonnements_evenements
             WHERE abe_code_evenement = :event_code
               AND abe_action_type = 'notification'
               AND abe_est_actif = 1
               AND abe_supprime_le IS NULL",
            ['event_code' => strtoupper(trim($eventCode))]
        );

        $contacts = $this->contactsMap($companyId);
        $result = [];
        foreach ($rows as $row) {
            $config = $this->decodeConfig($row['abe_action_configuration_json'] ?? null);
            $configCompanyId = isset($config['company_id']) ? (int) $config['company_id'] : null;
            if ($configCompanyId !== null && $configCompanyId !== $companyId) {
                continue;
            }
            $contactId = isset($config['contact_id']) ? (int) $config['contact_id'] : null;
            $contact = $contactId && isset($contacts[$contactId]) ? $contacts[$contactId] : null;
            if (!$contact || (int) ($contact['nco_is_active'] ?? 0) !== 1) {
                continue;
            }
            $result[] = array_merge($contact, [
                'nru_id' => (int) $row['abe_id'],
                'nru_channels' => json_encode($config['channels'] ?? ['email'], JSON_UNESCAPED_UNICODE),
                'evt_id' => (int) $row['abe_id'],
                'evt_code' => strtoupper((string) $row['abe_code_evenement']),
                'evt_label' => (string) $row['abe_nom'],
                'config' => $config,
            ]);
        }
        return $result;
    }

    private function contactsMap(int $companyId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT c.cts_id AS nco_id,
                    c.cts_utilisateur_id AS nco_user_id,
                    COALESCE(u.uti_email, c.cts_email_externe) AS nco_email,
                    COALESCE(p.pui_prenom, c.cts_prenom) AS nco_firstname,
                    COALESCE(p.pui_nom, c.cts_nom) AS nco_lastname,
                    CASE WHEN c.cts_supprime_le IS NULL THEN 1 ELSE 0 END AS nco_is_active
             FROM sav_contacts_societes c
             LEFT JOIN sav_utilisateurs u ON u.uti_id = c.cts_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             WHERE c.cts_societe_id = :company_id",
            ['company_id' => $companyId]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['nco_id']] = $row;
        }
        return $map;
    }

    private function decodeConfig(mixed $json): array
    {
        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
