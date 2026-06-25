<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Horaires\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Horaires\Services\HorairesService;

class HorairesController extends BaseController
{
    private HorairesService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new HorairesService();
    }

    public function index(): void
    {
        $this->requirePermission('horaire.consulter');
        $this->requireExportAllowed();
        $filters = $this->filters();
        $this->render('Horaires/Views/index', $this->service->dashboard($filters) + [
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Horaires de travail',
        ]);
    }

    public function calendrier(): void
    {
        $this->requirePermission('horaire.consulter');
        $this->requireExportAllowed();
        $filters = $this->filters();
        $this->render('Horaires/Views/calendrier', [
            'groupes' => $this->service->calendrier($filters),
            'refs' => $this->service->refs(),
            'filters' => $filters,
            'pageTitle' => 'Calendriers de travail',
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('horaire.gerer');
        $this->render('Horaires/Views/form_horaire', [
            'horaire' => null,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Nouvel horaire',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('horaire.gerer');
        $this->validateCsrf();
        try {
            $this->service->creerHoraire($_POST, (int)$this->userId());
            $this->flash()->success('Horaire créé.');
            $this->redirect('/horaires');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/horaires/create');
        }
    }

    public function edit(string $id): void
    {
        $this->requirePermission('horaire.gerer');
        $horaire = $this->service->horaire((int)$id);
        if (!$horaire) {
            $this->flash()->error('Horaire introuvable.');
            $this->redirect('/horaires');
        }
        $this->render('Horaires/Views/form_horaire', [
            'horaire' => $horaire,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Modifier horaire',
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('horaire.gerer');
        $this->validateCsrf();
        try {
            $this->service->modifierHoraire((int)$id, $_POST, (int)$this->userId());
            $this->flash()->success('Horaire mis à jour.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/horaires/' . (int)$id . '/edit');
    }

    public function delete(string $id): void
    {
        $this->requirePermission('horaire.gerer');
        $this->validateCsrf();
        $this->service->supprimerHoraire((int)$id, (int)$this->userId());
        $this->flash()->success('Horaire supprimé logiquement.');
        $this->redirect('/horaires');
    }

    public function exceptions(): void
    {
        $this->requirePermission('horaire.consulter');
        $this->requireExportAllowed();
        $filters = $this->filters();
        $this->render('Horaires/Views/exceptions', [
            'exceptions' => $this->service->exceptions($filters),
            'refs' => $this->service->refs(),
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Exceptions horaires',
        ]);
    }

    public function createException(): void
    {
        $this->requirePermission('horaire.gerer');
        $this->render('Horaires/Views/form_exception', [
            'exception' => null,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Nouvelle exception horaire',
        ]);
    }

    public function storeException(): void
    {
        $this->requirePermission('horaire.gerer');
        $this->validateCsrf();
        try {
            $this->service->creerException($_POST, (int)$this->userId());
            $this->flash()->success('Exception horaire créée.');
            $this->redirect('/horaires/exceptions');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/horaires/exceptions/create');
        }
    }

    public function editException(string $id): void
    {
        $this->requirePermission('horaire.gerer');
        $exception = $this->service->exception((int)$id);
        if (!$exception) {
            $this->flash()->error('Exception horaire introuvable.');
            $this->redirect('/horaires/exceptions');
        }
        $this->render('Horaires/Views/form_exception', [
            'exception' => $exception,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Modifier exception horaire',
        ]);
    }

    public function updateException(string $id): void
    {
        $this->requirePermission('horaire.gerer');
        $this->validateCsrf();
        try {
            $this->service->modifierException((int)$id, $_POST, (int)$this->userId());
            $this->flash()->success('Exception horaire mise à jour.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/horaires/exceptions/' . (int)$id . '/edit');
    }

    public function deleteException(string $id): void
    {
        $this->requirePermission('horaire.gerer');
        $this->validateCsrf();
        $this->service->supprimerException((int)$id, (int)$this->userId());
        $this->flash()->success('Exception horaire supprimée logiquement.');
        $this->redirect('/horaires/exceptions');
    }

    public function exportJson(): never
    {
        $this->requirePermission('horaire.consulter');
        $this->requireExportAllowed();
        $this->json(true, $this->service->export($this->filters()), 'Export horaires et exceptions');
    }

    private function filters(): array
    {
        return [
            'societe_id' => (int)$this->get('societe_id', 0),
            'portee_type' => (string)$this->get('portee_type', ''),
            'portee_id' => (int)$this->get('portee_id', 0),
            'jour_semaine' => (int)$this->get('jour_semaine', 0),
            'etat' => (string)$this->get('etat', ''),
            'date_debut' => (string)$this->get('date_debut', ''),
            'date_fin' => (string)$this->get('date_fin', ''),
            'periode' => (string)$this->get('periode', ''),
        ];
    }
}
