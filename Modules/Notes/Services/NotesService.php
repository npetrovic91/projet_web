<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notes\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Notes\Models\NotesModel;

class NotesService implements ServiceInterface{
    private NotesModel $model;

    public function __construct(?NotesModel $model = null)
    {
        $this->model = $model ?? new NotesModel();
    }

    public function dashboard(array $filters = []): array
    {
        return [
            'stats' => $this->model->stats(),
            'notes' => $this->model->notes($filters),
            'etiquettes' => $this->model->etiquettes(),
            'refs' => $this->model->refs(),
        ];
    }

    public function note(int $id): ?array { return $this->model->findNote($id); }
    public function creerNote(array $data, int $userId): int { return $this->model->createNote($data, $userId); }
    public function modifierNote(int $id, array $data, int $userId): bool { return $this->model->updateNote($id, $data, $userId); }
    public function supprimerNote(int $id, int $userId): bool { return $this->model->softDeleteNote($id, $userId); }

    public function etiquettes(array $filters = []): array { return $this->model->etiquettes($filters); }
    public function etiquette(int $id): ?array { return $this->model->findEtiquette($id); }
    public function creerEtiquette(array $data, int $userId): int { return $this->model->createEtiquette($data, $userId); }
    public function modifierEtiquette(int $id, array $data, int $userId): bool { return $this->model->updateEtiquette($id, $data, $userId); }
    public function supprimerEtiquette(int $id, int $userId): bool { return $this->model->softDeleteEtiquette($id, $userId); }

    public function affectations(array $filters = []): array { return $this->model->affectations($filters); }
    public function affecterEtiquette(int $etiquetteId, string $cibleType, int $cibleId, int $userId): bool
    {
        return $this->model->attachEtiquette($etiquetteId, $cibleType, $cibleId, $userId);
    }
    public function retirerAffectation(int $id, int $userId): bool { return $this->model->detachAffectation($id, $userId); }
    public function etiquettesPourCible(string $cibleType, int $cibleId): array { return $this->model->etiquettesForTarget($cibleType, $cibleId); }
    public function refs(): array { return $this->model->refs(); }
    public function export(): array { return $this->model->export(); }
}
