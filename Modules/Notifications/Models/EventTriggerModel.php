<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class EventTriggerModel extends BaseModel
{
    /**
     * Table réelle du dump SQL : catalogue/exécution des événements applicatifs.
     * Les anciens noms evt_* sont conservés uniquement comme alias de sortie.
     */
    protected string $table = 'sav_evenements_application';
    protected string $colPrefix = 'eva_';

    public function allActive(): array
    {
        $rows = [];

        $subscriptions = $this->db->fetchAll(
            "SELECT abe_id, abe_code_evenement, abe_nom, abe_module_id, abe_est_actif
             FROM sav_abonnements_evenements
             WHERE abe_supprime_le IS NULL
             ORDER BY abe_code_evenement ASC, abe_nom ASC"
        );
        foreach ($subscriptions as $row) {
            $code = strtoupper((string) $row['abe_code_evenement']);
            $rows[$code] = [
                'evt_id' => (int) $row['abe_id'],
                'evt_code' => $code,
                'evt_label' => (string) $row['abe_nom'],
                'evt_module' => $row['abe_module_id'] ? 'module_' . (int) $row['abe_module_id'] : 'noyau',
                'evt_is_active' => (int) $row['abe_est_actif'],
                'source_table' => 'sav_abonnements_evenements',
            ];
        }

        $events = $this->db->fetchAll(
            "SELECT eva_code, MAX(eva_nom) AS eva_nom, MAX(eva_module_id) AS eva_module_id
             FROM sav_evenements_application
             WHERE eva_supprime_le IS NULL
             GROUP BY eva_code
             ORDER BY eva_code ASC"
        );
        foreach ($events as $row) {
            $code = strtoupper((string) $row['eva_code']);
            $rows[$code] ??= [
                'evt_id' => 0,
                'evt_code' => $code,
                'evt_label' => (string) ($row['eva_nom'] ?: $code),
                'evt_module' => $row['eva_module_id'] ? 'module_' . (int) $row['eva_module_id'] : 'noyau',
                'evt_is_active' => 1,
                'source_table' => 'sav_evenements_application',
            ];
        }

        if ($rows === []) {
            foreach ($this->fallbackEvents() as $event) {
                $rows[$event['evt_code']] = $event;
            }
        }

        ksort($rows);
        return array_values($rows);
    }

    public function findByCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        foreach ($this->allActive() as $event) {
            if (($event['evt_code'] ?? '') === $code && (int) ($event['evt_is_active'] ?? 1) === 1) {
                return $event;
            }
        }
        return null;
    }

    public function createApplicationEvent(
        string $code,
        string $name,
        ?int $companyId,
        ?int $moduleId,
        ?int $emitterUserId,
        ?string $targetType,
        ?int $targetId,
        array $payload = []
    ): int {
        $this->db->execute(
            "INSERT INTO sav_evenements_application
                (eva_uuid, eva_code, eva_nom, eva_module_id, eva_societe_id,
                 eva_emetteur_utilisateur_id, eva_cible_type, eva_cible_id,
                 eva_donnees_json, eva_cree_le)
             VALUES
                (:uuid, :code, :name, :module_id, :company_id,
                 :emitter_user_id, :target_type, :target_id,
                 :payload, NOW())",
            [
                'uuid' => $this->uuid(),
                'code' => strtoupper(trim($code)),
                'name' => trim($name) ?: strtoupper(trim($code)),
                'module_id' => $moduleId,
                'company_id' => $companyId,
                'emitter_user_id' => $emitterUserId,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    private function fallbackEvents(): array
    {
        return [
            ['evt_id' => 0, 'evt_code' => 'UTILISATEUR_CREE', 'evt_label' => 'Utilisateur créé', 'evt_module' => 'users', 'evt_is_active' => 1, 'source_table' => 'fallback'],
            ['evt_id' => 0, 'evt_code' => 'SOCIETE_CREEE', 'evt_label' => 'Société créée', 'evt_module' => 'society', 'evt_is_active' => 1, 'source_table' => 'fallback'],
            ['evt_id' => 0, 'evt_code' => 'SECURITE_ALERTE', 'evt_label' => 'Alerte sécurité', 'evt_module' => 'security', 'evt_is_active' => 1, 'source_table' => 'fallback'],
            ['evt_id' => 0, 'evt_code' => 'VALIDATION_DEMANDEE', 'evt_label' => 'Validation demandée', 'evt_module' => 'workflow', 'evt_is_active' => 1, 'source_table' => 'fallback'],
        ];
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
