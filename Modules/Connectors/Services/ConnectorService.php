<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Connectors\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Connectors\Models\ConnectorModel;

class ConnectorService implements ServiceInterface{
    private ConnectorModel $model;

    public function __construct(?ConnectorModel $model = null)
    {
        $this->model = $model ?? new ConnectorModel();
    }

    public function dashboard(array $filters = []): array
    {
        return [
            'stats' => $this->model->statistiques(),
            'connecteurs' => $this->model->listerConnecteurs($filters),
            'cles_api' => $this->model->listerClesApi(['inclure_revoquees' => false]),
            'webhooks' => $this->model->listerWebhooks([], 100),
            'connecteurs_modules' => $this->model->listerConnecteursModules(),
            'evenements' => $this->model->listerEvenements(100),
            'refs' => $this->model->referentiels(),
        ];
    }

    public function referentiels(): array
    {
        return $this->model->referentiels();
    }

    public function connecteurs(array $filters = []): array
    {
        return $this->model->listerConnecteurs($filters);
    }

    public function trouverConnecteur(int $id): ?array
    {
        return $this->model->trouverConnecteur($id);
    }

    public function creerConnecteur(array $input, int $userId = 0): int
    {
        return $this->model->creerConnecteur($input, $userId);
    }

    public function modifierConnecteur(int $id, array $input, int $userId = 0): bool
    {
        return $this->model->modifierConnecteur($id, $input, $userId);
    }

    public function supprimerConnecteur(int $id, int $userId = 0): bool
    {
        return $this->model->supprimerConnecteur($id, $userId);
    }

    public function clesApi(array $filters = []): array
    {
        return $this->model->listerClesApi($filters);
    }

    public function creerCleApi(array $input, int $userId = 0): array
    {
        return $this->model->creerCleApi($input, $userId);
    }

    public function revoquerCleApi(int $id, int $userId = 0): bool
    {
        return $this->model->revoquerCleApi($id, $userId);
    }

    public function verifierCleApi(string $secret): ?array
    {
        return $this->model->verifierCleApi($secret);
    }

    public function webhooks(array $filters = []): array
    {
        return $this->model->listerWebhooks($filters);
    }

    public function journaliserWebhook(array $data): int
    {
        return $this->model->journaliserWebhook($data);
    }

    public function evenements(): array
    {
        return $this->model->listerEvenements();
    }

    public function creerEvenement(array $input, int $userId = 0): int
    {
        return $this->model->creerEvenement($input, $userId);
    }
}
