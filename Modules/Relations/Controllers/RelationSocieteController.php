<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Relations\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Relations\Services\RelationSocieteService;

class RelationSocieteController extends BaseController
{
    private RelationSocieteService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new RelationSocieteService();
    }

    public function index(): void
    {
        $this->requirePermission('relations_societes.read');
        $filters = [
            'q' => trim((string)$this->get('q', '')),
            'societe_id' => (int)$this->get('societe_id', $this->activeCompanyId() ?? 0) ?: null,
            'source_id' => (int)$this->get('source_id', 0) ?: null,
            'target_id' => (int)$this->get('target_id', 0) ?: null,
            'type_id' => (int)$this->get('type_id', 0) ?: null,
            'statut_id' => (int)$this->get('statut_id', 0) ?: null,
            'periode' => trim((string)$this->get('periode', '')),
        ];
        $this->render('Relations/Views/index', [
            'pageTitle' => 'Relations sociétés',
            'stats' => $this->service->tableauDeBord($filters['societe_id'])['stats'],
            'relations' => $this->service->relations($filters),
            'filters' => $filters,
            'refs' => $this->service->references(),
        ]);
    }

    public function exportJson(): void
    {
        $this->requirePermission('relations_societes.read');
        $societeId = (int)$this->get('societe_id', $this->activeCompanyId() ?? 0) ?: null;
        $this->json(true, $this->service->export($societeId), 'Export des relations sociétés.');
    }

    public function create(): void
    {
        $this->requirePermission('relations_societes.manage');
        $this->render('Relations/Views/form', [
            'pageTitle' => 'Créer une relation société',
            'relation' => null,
            'refs' => $this->service->references(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('relations_societes.manage');
        $relation = $this->service->relation((int)$id);
        if (!$relation) {
            $this->flash()->error('Relation société introuvable.');
            $this->redirect('/relations-societes');
        }
        $this->render('Relations/Views/form', [
            'pageTitle' => 'Modifier une relation société',
            'relation' => $relation,
            'refs' => $this->service->references(),
        ]);
    }

    public function store(): void { $this->save(null); }
    public function update(string $id): void { $this->save((int)$id); }

    public function delete(string $id): void
    {
        $this->requirePermission('relations_societes.manage');
        $this->validateCsrf();
        $this->service->supprimerRelation((int)$id, $this->userId(), client_ip());
        $this->flash()->success('Relation société supprimée.');
        $this->redirect('/relations-societes');
    }

    public function types(): void
    {
        $this->requirePermission('relations_societes.read');
        $filters = ['q' => trim((string)$this->get('q', ''))];
        $this->render('Relations/Views/types', [
            'pageTitle' => 'Types de relations sociétés',
            'types' => $this->service->types($filters),
            'filters' => $filters,
        ]);
    }

    public function createType(): void
    {
        $this->requirePermission('relations_societes.manage');
        $this->render('Relations/Views/type_form', [
            'pageTitle' => 'Créer un type de relation société',
            'type' => null,
            'statuts' => $this->service->references()['statuts'],
        ]);
    }

    public function editType(string $id): void
    {
        $this->requirePermission('relations_societes.manage');
        $type = $this->service->type((int)$id);
        if (!$type) {
            $this->flash()->error('Type de relation introuvable.');
            $this->redirect('/relations-societes/types');
        }
        $this->render('Relations/Views/type_form', [
            'pageTitle' => 'Modifier un type de relation société',
            'type' => $type,
            'statuts' => $this->service->references()['statuts'],
        ]);
    }

    public function storeType(): void { $this->saveType(null); }
    public function updateType(string $id): void { $this->saveType((int)$id); }

    public function deleteType(string $id): void
    {
        $this->requirePermission('relations_societes.manage');
        $this->validateCsrf();
        $this->service->supprimerType((int)$id, $this->userId(), client_ip());
        $this->flash()->success('Type de relation supprimé.');
        $this->redirect('/relations-societes/types');
    }

    public function representations(): void
    {
        $this->requirePermission('relations_societes.read');
        $filters = [
            'societe_id' => (int)$this->get('societe_id', $this->activeCompanyId() ?? 0) ?: null,
            'concession_id' => (int)$this->get('concession_id', 0) ?: null,
            'marque_id' => (int)$this->get('marque_id', 0) ?: null,
            'importateur_id' => (int)$this->get('importateur_id', 0) ?: null,
            'constructeur_id' => (int)$this->get('constructeur_id', 0) ?: null,
        ];
        $this->render('Relations/Views/representations', [
            'pageTitle' => 'Représentations marques',
            'representations' => $this->service->representations($filters),
            'filters' => $filters,
            'refs' => $this->service->references(),
        ]);
    }

    public function createRepresentation(): void
    {
        $this->requirePermission('relations_societes.manage');
        $this->render('Relations/Views/representation_form', [
            'pageTitle' => 'Créer une représentation marque',
            'representation' => null,
            'refs' => $this->service->references(),
        ]);
    }

    public function editRepresentation(string $id): void
    {
        $this->requirePermission('relations_societes.manage');
        $representation = $this->service->representation((int)$id);
        if (!$representation) {
            $this->flash()->error('Représentation marque introuvable.');
            $this->redirect('/representations-marques');
        }
        $this->render('Relations/Views/representation_form', [
            'pageTitle' => 'Modifier une représentation marque',
            'representation' => $representation,
            'refs' => $this->service->references(),
        ]);
    }

    public function storeRepresentation(): void { $this->saveRepresentation(null); }
    public function updateRepresentation(string $id): void { $this->saveRepresentation((int)$id); }

    public function deleteRepresentation(string $id): void
    {
        $this->requirePermission('relations_societes.manage');
        $this->validateCsrf();
        $this->service->supprimerRepresentation((int)$id, $this->userId(), client_ip());
        $this->flash()->success('Représentation marque supprimée.');
        $this->redirect('/representations-marques');
    }

    private function save(?int $id): void
    {
        $this->requirePermission('relations_societes.manage');
        $this->validateCsrf();
        try {
            $savedId = $this->service->enregistrerRelation($_POST, $id, $this->userId(), client_ip());
            $this->flash()->success($id ? 'Relation société modifiée.' : 'Relation société créée.');
            $this->redirect('/relations-societes/' . $savedId . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect($id ? '/relations-societes/' . $id . '/edit' : '/relations-societes/create');
        }
    }

    private function saveType(?int $id): void
    {
        $this->requirePermission('relations_societes.manage');
        $this->validateCsrf();
        try {
            $this->service->enregistrerType($_POST, $id, $this->userId(), client_ip());
            $this->flash()->success($id ? 'Type de relation modifié.' : 'Type de relation créé.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/relations-societes/types');
    }

    private function saveRepresentation(?int $id): void
    {
        $this->requirePermission('relations_societes.manage');
        $this->validateCsrf();
        try {
            $savedId = $this->service->enregistrerRepresentation($_POST, $id, $this->userId(), client_ip());
            $this->flash()->success($id ? 'Représentation marque modifiée.' : 'Représentation marque créée.');
            $this->redirect('/representations-marques/' . $savedId . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect($id ? '/representations-marques/' . $id . '/edit' : '/representations-marques/create');
        }
    }
}
