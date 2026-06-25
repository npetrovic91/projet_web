<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Settings\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Settings\Models\ApplicationSettingModel;

class SettingsService implements ServiceInterface{
    public function __construct(private readonly ApplicationSettingModel $settings = new ApplicationSettingModel())
    {
    }

    public function tableauDeBord(array $filters = []): array
    {
        return [
            'settings' => $this->settings->lister($filters),
            'domains' => $this->settings->domaines(),
            'stats' => $this->settings->statistiques(),
            'maintenance' => $this->settings->etatMaintenance(),
        ];
    }

    public function trouver(int $id): ?array
    {
        return $this->settings->trouver($id);
    }

    public function creer(array $input, int $userId, string $ip): int
    {
        return $this->settings->creer($input, $userId, $ip);
    }

    public function modifier(int $id, array $input, int $userId, string $ip): bool
    {
        return $this->settings->modifier($id, $input, $userId, $ip);
    }

    public function supprimer(int $id, int $userId, string $ip): bool
    {
        return $this->settings->supprimer($id, $userId, $ip);
    }

    public function sauvegarderMaintenance(array $input, int $userId, string $ip): void
    {
        $this->settings->sauvegarderMaintenance($input, $userId, $ip);
    }

    public function statuts(): array
    {
        return $this->settings->statuts();
    }

    public function export(): array
    {
        return $this->settings->export();
    }
}
