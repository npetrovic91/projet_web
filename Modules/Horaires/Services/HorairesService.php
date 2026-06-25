<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Horaires\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Horaires\Models\HorairesModel;

class HorairesService implements ServiceInterface{
    private HorairesModel $model;

    public function __construct(?HorairesModel $model = null)
    {
        $this->model = $model ?? new HorairesModel();
    }

    public function dashboard(array $filters = []): array
    {
        return [
            'stats' => $this->model->stats(),
            'horaires' => $this->model->horaires($filters),
            'exceptions' => $this->model->exceptions($filters + ['periode' => 'futures'], 100),
            'refs' => $this->model->refs(),
        ];
    }

    public function refs(): array { return $this->model->refs(); }
    public function calendrier(array $filters = []): array { return $this->model->calendrier($filters); }
    public function export(array $filters = []): array { return $this->model->export($filters); }

    public function horaire(int $id): ?array { return $this->model->findHoraire($id); }
    public function creerHoraire(array $data, int $userId): int { return $this->model->createHoraire($data, $userId); }
    public function modifierHoraire(int $id, array $data, int $userId): bool { return $this->model->updateHoraire($id, $data, $userId); }
    public function supprimerHoraire(int $id, int $userId): bool { return $this->model->deleteHoraire($id, $userId); }

    public function exception(int $id): ?array { return $this->model->findException($id); }
    public function exceptions(array $filters = []): array { return $this->model->exceptions($filters); }
    public function creerException(array $data, int $userId): int { return $this->model->createException($data, $userId); }
    public function modifierException(int $id, array $data, int $userId): bool { return $this->model->updateException($id, $data, $userId); }
    public function supprimerException(int $id, int $userId): bool { return $this->model->deleteException($id, $userId); }
}
