<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Validation\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Validation\Models\ValidationModel;

class ValidationService implements ServiceInterface{
    public function __construct(private ?ValidationModel $model = null)
    {
        $this->model ??= new ValidationModel();
    }

    public function dashboard(array $filters = []): array
    {
        return [
            'stats' => $this->model->stats(),
            'demandes' => $this->model->demandes($filters),
            'regles' => $this->model->regles($filters),
            'refs' => $this->model->refs(),
        ];
    }

    public function demande(int $id): ?array { return $this->model->findDemande($id); }
    public function regle(int $id): ?array { return $this->model->findRegle($id); }
    public function refs(): array { return $this->model->refs(); }
    public function demandes(array $filters = []): array { return $this->model->demandes($filters); }
    public function regles(array $filters = []): array { return $this->model->regles($filters); }
    public function creerDemande(array $data, int $userId): int { return $this->model->createDemande($data, $userId); }
    public function deciderDemande(int $id, string $decision, ?string $commentaire, int $userId): bool { return $this->model->decideDemande($id, $decision, $commentaire, $userId); }
    public function supprimerDemande(int $id, int $userId): bool { return $this->model->softDeleteDemande($id, $userId); }
    public function creerRegle(array $data, int $userId): int { return $this->model->createRegle($data, $userId); }
    public function modifierRegle(int $id, array $data, int $userId): bool { return $this->model->updateRegle($id, $data, $userId); }
    public function supprimerRegle(int $id, int $userId): bool { return $this->model->softDeleteRegle($id, $userId); }
    public function export(): array { return $this->model->export(); }
}
