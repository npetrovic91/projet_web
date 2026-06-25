<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Maintenance\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Mode maintenance aligne sur sav_parametres_application.
 *
 * Le dump SQL ne contient pas de table dediee au mode maintenance : le mode est donc
 * porte par des parametres applicatifs versionnables et auditables.
 */
class MaintenanceModel extends BaseModel
{
    protected string $table = 'sav_parametres_application';
    protected string $colPrefix = 'pap_';

    public function getState(): array
    {
        return [
            'mtn_id' => 1,
            'mtn_is_active' => (int) $this->getValue('application', 'mode_maintenance', false),
            'mtn_message' => (string) $this->getValue('maintenance', 'message_public', 'Application temporairement indisponible pour maintenance.'),
            'mtn_allowed_roles' => json_encode((array) $this->getValue('maintenance', 'roles_autorises', ['super_administrateur']), JSON_UNESCAPED_UNICODE),
            'mtn_allowed_ips' => json_encode((array) $this->getValue('maintenance', 'ips_autorisees', ['127.0.0.1', '::1']), JSON_UNESCAPED_UNICODE),
            'mtn_started_at' => $this->getValue('maintenance', 'dernier_debut_le', null),
            'mtn_ended_at' => $this->getValue('maintenance', 'derniere_fin_le', null),
            'mtn_updated_at' => $this->getValue('maintenance', 'modifie_le', null),
        ];
    }

    public function updateState(bool $active, string $message, array $allowedRoles, array $allowedIps, int $updatedBy): bool
    {
        $previous = $this->getState();
        $now = date('Y-m-d H:i:s');

        $this->setValue('application', 'mode_maintenance', $active, 'Activation du mode maintenance', $updatedBy);
        $this->setValue('maintenance', 'message_public', $message, 'Message public affiche pendant la maintenance', $updatedBy);
        $this->setValue('maintenance', 'roles_autorises', array_values($allowedRoles), 'Roles autorises pendant la maintenance', $updatedBy);
        $this->setValue('maintenance', 'ips_autorisees', array_values($allowedIps), 'IP autorisees pendant la maintenance', $updatedBy);
        $this->setValue('maintenance', 'modifie_le', $now, 'Derniere modification maintenance', $updatedBy);

        if ($active && empty($previous['mtn_is_active'])) {
            $this->setValue('maintenance', 'dernier_debut_le', $now, 'Dernier debut du mode maintenance', $updatedBy);
        }
        if (!$active && !empty($previous['mtn_is_active'])) {
            $this->setValue('maintenance', 'derniere_fin_le', $now, 'Derniere fin du mode maintenance', $updatedBy);
        }

        return true;
    }

    public function getPolicies(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return $this->db->fetchAll(
            "SELECT *
               FROM sav_politiques_maintenance
              WHERE pmt_supprime_le IS NULL
           ORDER BY pmt_est_active DESC, pmt_prochaine_execution_le ASC, pmt_code ASC
              LIMIT {$limit}"
        );
    }

    public function getLatestExecutions(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        return $this->db->fetchAll(
            "SELECT *
               FROM sav_executions_maintenance
           ORDER BY exm_debut_le DESC
              LIMIT {$limit}"
        );
    }

    private function getValue(string $domain, string $key, mixed $default = null): mixed
    {
        $row = $this->db->fetch(
            "SELECT pap_valeur_json
               FROM sav_parametres_application
              WHERE pap_domaine = :domain
                AND pap_cle = :key
                AND pap_supprime_le IS NULL
           ORDER BY pap_id DESC
              LIMIT 1",
            ['domain' => $domain, 'key' => $key]
        );
        if (!$row) {
            return $default;
        }

        $decoded = json_decode((string) ($row['pap_valeur_json'] ?? ''), true);
        if (!is_array($decoded) || !array_key_exists('valeur', $decoded)) {
            return $default;
        }
        return $decoded['valeur'];
    }

    private function setValue(string $domain, string $key, mixed $value, string $description, int $updatedBy): void
    {
        $payload = json_encode(['valeur' => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row = $this->db->fetch(
            "SELECT pap_id
               FROM sav_parametres_application
              WHERE pap_domaine = :domain
                AND pap_cle = :key
                AND pap_supprime_le IS NULL
           ORDER BY pap_id DESC
              LIMIT 1",
            ['domain' => $domain, 'key' => $key]
        );

        if ($row) {
            $this->db->execute(
                "UPDATE sav_parametres_application
                    SET pap_valeur_json = :payload,
                        pap_description = :description,
                        pap_modifie_le = NOW()
                  WHERE pap_id = :id",
                ['payload' => $payload, 'description' => $description, 'id' => (int) $row['pap_id']]
            );
            return;
        }

        $this->db->execute(
            "INSERT INTO sav_parametres_application
                (pap_domaine, pap_cle, pap_valeur_json, pap_description, pap_est_secret, pap_est_systeme, pap_cree_le, pap_modifie_le)
             VALUES
                (:domain, :key, :payload, :description, 0, 1, NOW(), NOW())",
            ['domain' => $domain, 'key' => $key, 'payload' => $payload, 'description' => $description]
        );
    }
}
