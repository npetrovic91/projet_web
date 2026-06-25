<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Verrous\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Verrous\Models\VerrousModel;

class VerrousService implements ServiceInterface{
    public function __construct(private ?VerrousModel $model = null)
    {
        $this->model ??= new VerrousModel();
    }

    public function dashboard(array $filters = []): array
    {
        return [
            'stats' => $this->model->stats(),
            'verrous' => $this->model->verrous($filters),
            'sessions' => $this->model->sessions($filters, 100),
            'contextes' => $this->model->contextes($filters, 100),
            'maintenance' => $this->model->verrousMaintenance(),
            'refs' => $this->model->refs(),
        ];
    }

    public function refs(): array { return $this->model->refs(); }
    public function verrous(array $filters = []): array { return $this->model->verrous($filters); }
    public function sessions(array $filters = []): array { return $this->model->sessions($filters); }
    public function contextes(array $filters = []): array { return $this->model->contextes($filters); }
    public function maintenance(): array { return $this->model->verrousMaintenance(); }
    public function export(array $filters = []): array { return $this->model->export($filters); }
    public function creerVerrou(array $data, int $userId): int { return $this->model->creerVerrou($data, $userId); }
    public function libererVerrou(int $id, int $userId, string $raison = ''): bool { return $this->model->libererVerrou($id, $userId, $raison); }
    public function revoquerSession(int $id, int $userId, string $motif = ''): bool { return $this->model->revoquerSession($id, $userId, $motif); }
}
