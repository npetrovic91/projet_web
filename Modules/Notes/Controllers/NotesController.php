<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notes\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Notes\Services\NotesService;

class NotesController extends BaseController
{
    private NotesService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new NotesService();
    }

    public function index(): void
    {
        $this->requireAuth();
        $filters = $this->filters();
        $this->render('Notes/Views/index', $this->service->dashboard($filters) + [
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Notes / étiquettes',
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->render('Notes/Views/form_note', [
            'note' => null,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Nouvelle note',
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        try {
            $id = $this->service->creerNote($_POST, (int) $this->userId());
            $this->flash()->success('Note créée.');
            $this->redirect('/notes/' . $id);
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/notes/create');
        }
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $note = $this->service->note((int) $id);
        if (!$note) {
            $this->flash()->error('Note introuvable.');
            $this->redirect('/notes');
        }
        $this->render('Notes/Views/show_note', [
            'note' => $note,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Note',
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $note = $this->service->note((int) $id);
        if (!$note) {
            $this->flash()->error('Note introuvable.');
            $this->redirect('/notes');
        }
        $this->render('Notes/Views/form_note', [
            'note' => $note,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Modifier note',
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        try {
            $this->service->modifierNote((int) $id, $_POST, (int) $this->userId());
            $this->flash()->success('Note mise à jour.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/notes/' . (int) $id . '/edit');
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->service->supprimerNote((int) $id, (int) $this->userId());
        $this->flash()->success('Note supprimée logiquement.');
        $this->redirect('/notes');
    }

    public function labels(): void
    {
        $this->requireAuth();
        $filters = ['q' => (string) $this->get('q', ''), 'societe_id' => (int) $this->get('societe_id', 0)];
        $this->render('Notes/Views/labels', [
            'etiquettes' => $this->service->etiquettes($filters),
            'refs' => $this->service->refs(),
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Étiquettes',
        ]);
    }

    public function createLabel(): void
    {
        $this->requireAuth();
        $this->render('Notes/Views/form_label', [
            'etiquette' => null,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Nouvelle étiquette',
        ]);
    }

    public function storeLabel(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        try {
            $id = $this->service->creerEtiquette($_POST, (int) $this->userId());
            $this->flash()->success('Étiquette créée.');
            $this->redirect('/labels/' . $id . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/labels/create');
        }
    }

    public function editLabel(string $id): void
    {
        $this->requireAuth();
        $etiquette = $this->service->etiquette((int) $id);
        if (!$etiquette) {
            $this->flash()->error('Étiquette introuvable.');
            $this->redirect('/labels');
        }
        $this->render('Notes/Views/form_label', [
            'etiquette' => $etiquette,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Modifier étiquette',
        ]);
    }

    public function updateLabel(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        try {
            $this->service->modifierEtiquette((int) $id, $_POST, (int) $this->userId());
            $this->flash()->success('Étiquette mise à jour.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/labels/' . (int) $id . '/edit');
    }

    public function deleteLabel(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->service->supprimerEtiquette((int) $id, (int) $this->userId());
        $this->flash()->success('Étiquette supprimée logiquement.');
        $this->redirect('/labels');
    }

    public function assignments(): void
    {
        $this->requireAuth();
        $filters = [
            'etiquette_id' => (int) $this->get('etiquette_id', 0),
            'cible_type' => (string) $this->get('cible_type', ''),
            'cible_id' => (int) $this->get('cible_id', 0),
        ];
        $this->render('Notes/Views/assignments', [
            'affectations' => $this->service->affectations($filters),
            'refs' => $this->service->refs(),
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Affectations d’étiquettes',
        ]);
    }

    public function attach(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        try {
            $this->service->affecterEtiquette(
                (int) $this->post('etiquette_id', 0),
                (string) $this->post('cible_type', 'general'),
                (int) $this->post('cible_id', 0),
                (int) $this->userId()
            );
            $this->flash()->success('Étiquette affectée.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/label-assignments');
    }

    public function detach(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->service->retirerAffectation((int) $id, (int) $this->userId());
        $this->flash()->success('Affectation retirée.');
        $this->redirect('/label-assignments');
    }

    public function targetLabels(): never
    {
        $this->requireAuth();
        $this->json(true, $this->service->etiquettesPourCible((string) $this->get('cible_type', 'general'), (int) $this->get('cible_id', 0)), 'Étiquettes de la cible');
    }

    public function exportJson(): never
    {
        $this->requireAuth();
        $this->json(true, $this->service->export(), 'Export notes et étiquettes');
    }

    private function filters(): array
    {
        return [
            'q' => (string) $this->get('q', ''),
            'societe_id' => (int) $this->get('societe_id', 0),
            'cible_type' => (string) $this->get('cible_type', ''),
            'cible_id' => (int) $this->get('cible_id', 0),
            'visibilite' => (string) $this->get('visibilite', ''),
            'etiquette_id' => (int) $this->get('etiquette_id', 0),
        ];
    }
}
