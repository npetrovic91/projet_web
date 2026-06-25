<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Maintenance\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Modules\Maintenance\Models\MaintenanceEventModel;
use Nenad\Autosav\Modules\Maintenance\Models\MaintenanceModel;

class MaintenanceService implements ServiceInterface{
    public function __construct(
        private MaintenanceModel $maintenance,
        private MaintenanceEventModel $events
    ) {}

    public function dashboard(): array
    {
        return [
            'state' => $this->normalizeState($this->maintenance->getState()),
            'indicators' => $this->indicators(),
            'events' => $this->events->latest(),
            'policies' => $this->maintenance->getPolicies(),
            'executions' => $this->maintenance->getLatestExecutions(),
        ];
    }

    public function update(array $data, int $adminId, string $ip): array
    {
        $active = !empty($data['mtn_is_active']);
        $message = trim((string) ($data['mtn_message'] ?? ''));
        if ($message === '') {
            $message = 'Application temporairement indisponible pour maintenance.';
        }

        $roles = $this->splitLines((string) ($data['mtn_allowed_roles'] ?? 'super_administrateur'));
        $ips = $this->splitLines((string) ($data['mtn_allowed_ips'] ?? "127.0.0.1\n::1"));

        $previous = $this->normalizeState($this->maintenance->getState());
        $this->maintenance->updateState($active, $message, $roles, $ips, $adminId);

        $type = $active ? 'maintenance_enabled' : 'maintenance_disabled';
        if ((bool) $previous['is_active'] === $active) {
            $type = 'maintenance_updated';
        }

        $this->events->record($type, 'info', $active ? 'Mode maintenance actif.' : 'Mode maintenance inactif.', $adminId, $ip, [
            'message' => $message,
            'allowed_roles' => $roles,
            'allowed_ips' => $ips,
        ]);

        logger('audit')->info($type, ['admin_id' => $adminId, 'active' => $active]);
        return ['success' => true, 'message' => 'Configuration maintenance enregistrée.'];
    }

    private function normalizeState(array $row): array
    {
        return [
            'is_active' => (bool) ($row['mtn_is_active'] ?? false),
            'message' => (string) ($row['mtn_message'] ?? ''),
            'allowed_roles' => json_decode((string) ($row['mtn_allowed_roles'] ?? '[]'), true) ?: [],
            'allowed_ips' => json_decode((string) ($row['mtn_allowed_ips'] ?? '[]'), true) ?: [],
            'started_at' => $row['mtn_started_at'] ?? null,
            'ended_at' => $row['mtn_ended_at'] ?? null,
            'updated_at' => $row['mtn_updated_at'] ?? null,
        ];
    }

    private function indicators(): array
    {
        $db = Database::getInstance();
        return [
            'tentatives_connexion_24h' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_tentatives_connexion WHERE tcn_cree_le >= DATE_SUB(NOW(), INTERVAL 1 DAY)"),
            'echecs_connexion_24h' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_tentatives_connexion WHERE tcn_succes = 0 AND tcn_cree_le >= DATE_SUB(NOW(), INTERVAL 1 DAY)"),
            'blocages_ip_actifs' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_blocages_securite WHERE bse_adresse_ip IS NOT NULL AND bse_supprime_le IS NULL AND (bse_termine_le IS NULL OR bse_termine_le > NOW())"),
            'comptes_verrouilles' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_utilisateurs WHERE uti_est_verrouille = 1 AND uti_supprime_le IS NULL AND (uti_verrouille_jusqua IS NULL OR uti_verrouille_jusqua > NOW())"),
            'demandes_rgpd_ouvertes' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_demandes_rgpd WHERE drg_supprime_le IS NULL AND drg_traitee_le IS NULL"),
            'utilisateurs_actifs' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_utilisateurs WHERE uti_supprime_le IS NULL AND uti_anonymise_le IS NULL"),
            'politiques_maintenance_actives' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_politiques_maintenance WHERE pmt_est_active = 1 AND pmt_supprime_le IS NULL"),
            'executions_maintenance_echouees_7j' => $this->countSafe($db, "SELECT COUNT(*) FROM sav_executions_maintenance WHERE exm_statut = 'echouee' AND exm_debut_le >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        ];
    }

    private function countSafe(Database $db, string $sql): int
    {
        try {
            return (int) $db->fetchColumn($sql);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function splitLines(string $value): array
    {
        $items = preg_split('/[\r\n,]+/', $value) ?: [];
        return array_values(array_filter(array_map('trim', $items), static fn(string $item): bool => $item !== ''));
    }
}
