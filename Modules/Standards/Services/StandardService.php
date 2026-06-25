<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Standards\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Standards\Models\StandardModel;

class StandardService implements ServiceInterface{
    public function __construct(private ?StandardModel $model = null)
    {
        $this->model ??= new StandardModel();
    }

    public function dashboard(array $filters = []): array
    {
        return [
            'stats' => $this->model->stats(),
            'standards' => $this->model->standards($filters),
            'versions' => $this->model->versions(),
            'exigences' => $this->model->exigences(),
            'refs' => $this->model->referentiels(),
        ];
    }

    public function refs(): array { return $this->model->referentiels(); }
    public function standard(int $id): ?array { return $this->model->findStandard($id); }
    public function standards(array $filters = []): array { return $this->model->standards($filters); }
    public function versions(?int $standardId = null): array { return $this->model->versions($standardId); }
    public function version(int $id): ?array { return $this->model->findVersion($id); }
    public function exigences(?int $versionId = null): array { return $this->model->exigences($versionId); }
    public function evaluation(int $versionId): array { return $this->model->evaluationUtilisateurs($versionId); }

    public function creerStandard(array $data, ?int $userId): int { return $this->model->createStandard($data, $userId); }
    public function modifierStandard(int $id, array $data, ?int $userId): bool { return $this->model->updateStandard($id, $data, $userId); }
    public function supprimerStandard(int $id, ?int $userId): bool { return $this->model->softDeleteStandard($id, $userId); }

    public function creerVersion(array $data, ?int $userId): int { return $this->model->createVersion($data, $userId); }
    public function modifierVersion(int $id, array $data, ?int $userId): bool { return $this->model->updateVersion($id, $data, $userId); }
    public function supprimerVersion(int $id, ?int $userId): bool { return $this->model->softDeleteVersion($id, $userId); }

    public function creerExigence(array $data, ?int $userId): int { return $this->model->createExigence($data, $userId); }
    public function supprimerExigence(int $id, ?int $userId): bool { return $this->model->softDeleteExigence($id, $userId); }
}
