<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Relations\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Relations\Models\RelationSocieteModel;

class RelationSocieteService implements ServiceInterface{
    private RelationSocieteModel $model;

    public function __construct(?RelationSocieteModel $model = null)
    {
        $this->model = $model ?? new RelationSocieteModel();
    }

    public function tableauDeBord(?int $societeId = null): array
    {
        return [
            'stats' => $this->model->stats($societeId),
            'relations' => $this->model->listerRelations(['societe_id' => $societeId]),
            'representations' => $this->model->listerRepresentations(['societe_id' => $societeId]),
            'refs' => $this->references(),
        ];
    }

    public function relations(array $filters = []): array { return $this->model->listerRelations($filters); }
    public function relation(int $id): ?array { return $this->model->trouverRelation($id); }
    public function types(array $filters = []): array { return $this->model->listerTypes($filters); }
    public function type(int $id): ?array { return $this->model->trouverType($id); }
    public function representations(array $filters = []): array { return $this->model->listerRepresentations($filters); }
    public function representation(int $id): ?array { return $this->model->trouverRepresentation($id); }

    public function enregistrerRelation(array $data, ?int $id, ?int $userId, ?string $ip): int
    {
        $savedId = $this->model->enregistrerRelation($data, $id, $userId);
        $this->model->audit($id ? 'relation_societe.modifier' : 'relation_societe.creer', 'sav_relations_societes', $savedId, $userId, $ip, [
            'source_id' => (int)($data['rso_societe_source_id'] ?? 0),
            'cible_id' => (int)($data['rso_societe_cible_id'] ?? 0),
            'type_id' => (int)($data['rso_type_relation_societe_id'] ?? 0),
        ]);
        return $savedId;
    }

    public function supprimerRelation(int $id, ?int $userId, ?string $ip): void
    {
        $this->model->supprimerRelation($id, $userId);
        $this->model->audit('relation_societe.supprimer', 'sav_relations_societes', $id, $userId, $ip);
    }

    public function enregistrerType(array $data, ?int $id, ?int $userId, ?string $ip): int
    {
        $savedId = $this->model->enregistrerType($data, $id, $userId);
        $this->model->audit($id ? 'type_relation_societe.modifier' : 'type_relation_societe.creer', 'sav_types_relations_societes', $savedId, $userId, $ip, [
            'code' => (string)($data['tre_code'] ?? ''),
        ]);
        return $savedId;
    }

    public function supprimerType(int $id, ?int $userId, ?string $ip): void
    {
        $this->model->supprimerType($id, $userId);
        $this->model->audit('type_relation_societe.supprimer', 'sav_types_relations_societes', $id, $userId, $ip);
    }

    public function enregistrerRepresentation(array $data, ?int $id, ?int $userId, ?string $ip): int
    {
        $savedId = $this->model->enregistrerRepresentation($data, $id, $userId);
        $this->model->audit($id ? 'representation_marque.modifier' : 'representation_marque.creer', 'sav_representations_marques_societes', $savedId, $userId, $ip, [
            'concession_id' => (int)($data['rma_concession_societe_id'] ?? 0),
            'marque_id' => (int)($data['rma_marque_societe_id'] ?? 0),
        ]);
        return $savedId;
    }

    public function supprimerRepresentation(int $id, ?int $userId, ?string $ip): void
    {
        $this->model->supprimerRepresentation($id, $userId);
        $this->model->audit('representation_marque.supprimer', 'sav_representations_marques_societes', $id, $userId, $ip);
    }

    public function references(): array
    {
        return [
            'societes' => $this->model->societes(),
            'types' => $this->model->listerTypes(),
            'statuts' => $this->model->statuts(),
        ];
    }

    public function export(?int $societeId = null): array
    {
        return [
            'genere_le' => date('c'),
            'societe_filtre_id' => $societeId,
            'stats' => $this->model->stats($societeId),
            'relations' => $this->model->listerRelations(['societe_id' => $societeId]),
            'types' => $this->model->listerTypes(),
            'representations_marques' => $this->model->listerRepresentations(['societe_id' => $societeId]),
        ];
    }
}
