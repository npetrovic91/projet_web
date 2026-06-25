<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Referentiels\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Referentiels\Models\ReferentielModel;

class ReferentielService implements ServiceInterface{
    public function __construct(private readonly ReferentielModel $model = new ReferentielModel())
    {
    }

    public function tableauDeBord(): array { return $this->model->tableauDeBord(); }
    public function pays(array $filters = []): array { return $this->model->pays($filters); }
    public function devises(array $filters = []): array { return $this->model->devises($filters); }
    public function fuseaux(array $filters = []): array { return $this->model->fuseaux($filters); }
    public function tauxTva(array $filters = []): array { return $this->model->tauxTva($filters); }
    public function statuts(array $filters = []): array { return $this->model->statuts($filters); }
    public function transitions(array $filters = []): array { return $this->model->transitions($filters); }
    public function domainesStatuts(): array { return $this->model->domainesStatuts(); }
    public function statutsPourSelect(?string $domaine = null): array { return $this->model->statutsPourSelect($domaine); }
    public function export(): array { return $this->model->export(); }

    public function trouver(string $type, int $id): ?array
    {
        return match ($type) {
            'pays' => $this->model->trouverPays($id),
            'devises' => $this->model->trouverDevise($id),
            'fuseaux' => $this->model->trouverFuseau($id),
            'tva' => $this->model->trouverTva($id),
            'statuts' => $this->model->trouverStatut($id),
            'transitions' => $this->model->trouverTransition($id),
            default => null,
        };
    }

    public function enregistrer(string $type, array $input, ?int $id, int $userId, string $ip): int
    {
        $result = match ($type) {
            'pays' => $this->model->enregistrerPays($input, $id),
            'devises' => $this->model->enregistrerDevise($input, $id),
            'fuseaux' => $this->model->enregistrerFuseau($input, $id),
            'tva' => $this->model->enregistrerTva($input, $id),
            'statuts' => $this->model->enregistrerStatut($input, $id),
            'transitions' => $this->model->enregistrerTransition($input, $id),
            default => throw new \InvalidArgumentException('Type de référentiel inconnu.'),
        };
        $this->model->auditer($id ? 'referentiel.modifier' : 'referentiel.creer', 'sav_' . $type, $result, $userId, $ip, ['type' => $type]);
        return $result;
    }

    public function supprimer(string $type, int $id, int $userId, string $ip): bool
    {
        return $this->model->supprimer($type, $id, $userId, $ip);
    }
}
