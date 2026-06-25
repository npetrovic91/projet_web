<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Organisation\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Organisation\Models\OrganisationModel;

class OrganisationService implements ServiceInterface{
    public function __construct(private readonly OrganisationModel $model = new OrganisationModel()) {}

    public function tableauDeBord(?int $societeId = null): array { return $this->model->tableauDeBord($societeId); }
    public function entites(string $type, array $filters = []): array { return $this->model->entites($type, $filters); }
    public function trouverEntite(string $type, int $id): ?array { return $this->model->trouverEntite($type, $id); }
    public function societes(): array { return $this->model->societes(); }
    public function statuts(): array { return $this->model->statutsOrganisation(); }
    public function utilisateurs(?int $societeId = null): array { return $this->model->utilisateursActifs($societeId); }
    public function affectations(string $type, int $id): array { return $this->model->affectationsEntite($type, $id); }
    public function typeLabels(): array { return $this->model->typeLabels(); }
    public function liaisonLabels(): array { return $this->model->liaisonLabels(); }
    public function optionsPourLiaison(string $type, ?int $societeId): array { return $this->model->optionsPourLiaison($type, $societeId); }
    public function liaisons(array $filters = [], ?int $societeId = null): array { return $this->model->liaisons($filters, 500, $societeId); }
    public function export(?int $societeId = null): array { return $this->model->export($societeId); }

    public function enregistrerEntite(string $type, array $input, ?int $id, ?int $userId, string $ip): int
    {
        $result = $this->model->enregistrerEntite($type, $input, $id, $userId);
        $societeId = (int)($input['societe_id'] ?? 0) ?: null;
        $this->model->auditer($id ? 'organisation.structure.modifier' : 'organisation.structure.creer', 'sav_' . $type, $result, $userId, $societeId, $ip, ['type' => $type]);
        return $result;
    }

    public function supprimerEntite(string $type, int $id, ?int $userId, string $ip): bool
    {
        $result = $this->model->supprimerEntite($type, $id, $userId);
        $this->model->auditer('organisation.structure.supprimer', 'sav_' . $type, $id, $userId, null, $ip, ['type' => $type]);
        return $result;
    }

    public function enregistrerLiaison(string $type, array $input, ?int $userId, string $ip): int
    {
        $result = $this->model->enregistrerLiaison($type, $input, $userId);
        $this->model->auditer('organisation.liaison.creer', 'liaison_' . $type, $result, $userId, (int)($input['societe_id'] ?? 0) ?: null, $ip, ['type' => $type]);
        return $result;
    }

    public function supprimerLiaison(string $type, int $id, ?int $userId, string $ip): bool
    {
        $result = $this->model->supprimerLiaison($type, $id, $userId);
        $this->model->auditer('organisation.liaison.supprimer', 'liaison_' . $type, $id, $userId, null, $ip, ['type' => $type]);
        return $result;
    }
}
