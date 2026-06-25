<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Verrous\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Verrous\Services\VerrousService;

class VerrousController extends BaseController
{
    private VerrousService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new VerrousService();
    }

    public function index(): void
    {
        $this->requirePermission('verrou.consulter');
        $this->requireExportAllowed();
        $filters = $this->filters();
        $this->render('Verrous/Views/index', $this->service->dashboard($filters) + [
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Verrous et concurrence',
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('verrou.gerer');
        $this->render('Verrous/Views/create', [
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Créer un verrou',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('verrou.gerer');
        $this->requireCsrf();
        $this->service->creerVerrou($_POST, (int)($this->userId() ?? 0));
        $this->flash('success', 'Verrou créé.');
        $this->redirect('/verrous');
    }

    public function release(int|string $id): void
    {
        $this->requirePermission('verrou.gerer');
        $this->requireCsrf();
        $this->service->libererVerrou((int)$id, (int)($this->userId() ?? 0), (string)($_POST['raison'] ?? ''));
        $this->flash('success', 'Verrou libéré.');
        $this->redirect('/verrous');
    }

    public function sessions(): void
    {
        $this->requirePermission('verrou.consulter');
        $this->requireExportAllowed();
        $filters = $this->filters();
        $this->render('Verrous/Views/sessions', [
            'sessions' => $this->service->sessions($filters),
            'refs' => $this->service->refs(),
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Sessions utilisateurs',
        ]);
    }

    public function revokeSession(int|string $id): void
    {
        $this->requirePermission('verrou.gerer');
        $this->requireCsrf();
        $this->service->revoquerSession((int)$id, (int)($this->userId() ?? 0), (string)($_POST['motif'] ?? ''));
        $this->flash('success', 'Session révoquée.');
        $this->redirect('/verrous/sessions');
    }

    public function contextes(): void
    {
        $this->requirePermission('verrou.consulter');
        $this->requireExportAllowed();
        $filters = $this->filters();
        $this->render('Verrous/Views/contextes', [
            'contextes' => $this->service->contextes($filters),
            'refs' => $this->service->refs(),
            'filters' => $filters,
            'pageTitle' => 'Historique des contextes utilisateur',
        ]);
    }

    public function maintenance(): void
    {
        $this->requirePermission('verrou.consulter');
        $this->requireExportAllowed();
        $this->render('Verrous/Views/maintenance', [
            'maintenance' => $this->service->maintenance(),
            'pageTitle' => 'Verrous de maintenance',
        ]);
    }

    public function exportJson(): never
    {
        $this->requirePermission('verrou.consulter');
        $this->requireExportAllowed();
        $this->json(true, $this->service->export($this->filters()), 'Export verrous / sessions / contextes');
    }

    private function filters(): array
    {
        return [
            'q' => trim((string)($_GET['q'] ?? '')),
            'cible_type' => trim((string)($_GET['cible_type'] ?? '')),
            'utilisateur_id' => (int)($_GET['utilisateur_id'] ?? 0),
            'societe_id' => (int)($_GET['societe_id'] ?? 0),
            'etat' => trim((string)($_GET['etat'] ?? '')),
            'etat_session' => trim((string)($_GET['etat_session'] ?? '')),
            'action' => trim((string)($_GET['action'] ?? '')),
            'date_debut' => trim((string)($_GET['date_debut'] ?? '')),
            'date_fin' => trim((string)($_GET['date_fin'] ?? '')),
        ];
    }
}
