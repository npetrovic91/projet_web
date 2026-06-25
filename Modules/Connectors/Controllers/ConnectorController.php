<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Connectors\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Connectors\Services\ConnectorService;

class ConnectorController extends BaseController
{
    private ConnectorService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ConnectorService();
    }

    public function index(): void
    {
        $this->requirePermission('connecteur.gerer');
        $filters = [
            'q' => (string) $this->get('q', ''),
            'type' => (string) $this->get('type', ''),
            'societe_id' => (int) $this->get('societe_id', 0),
        ];
        $data = $this->service->dashboard($filters);
        $this->render('Connectors/Views/index', $data + [
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Connecteurs / API keys / Webhooks',
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('connecteur.gerer');
        $this->render('Connectors/Views/form', [
            'connecteur' => null,
            'refs' => $this->service->referentiels(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Nouveau connecteur',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('connecteur.gerer');
        $this->validateCsrf();
        try {
            $id = $this->service->creerConnecteur($_POST, (int) $this->userId());
            $this->flash()->success('Connecteur créé.');
            $this->redirect('/connectors/' . $id . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/connectors/create');
        }
    }

    public function edit(string $id): void
    {
        $this->requirePermission('connecteur.gerer');
        $connecteur = $this->service->trouverConnecteur((int) $id);
        if (!$connecteur) {
            $this->flash()->error('Connecteur introuvable.');
            $this->redirect('/connectors');
        }
        $this->render('Connectors/Views/form', [
            'connecteur' => $connecteur,
            'refs' => $this->service->referentiels(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Modifier connecteur',
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('connecteur.gerer');
        $this->validateCsrf();
        try {
            $this->service->modifierConnecteur((int) $id, $_POST, (int) $this->userId());
            $this->flash()->success('Connecteur mis à jour.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/connectors/' . (int) $id . '/edit');
    }

    public function delete(string $id): void
    {
        $this->requirePermission('connecteur.gerer');
        $this->validateCsrf();
        $this->service->supprimerConnecteur((int) $id, (int) $this->userId());
        $this->flash()->success('Connecteur supprimé logiquement.');
        $this->redirect('/connectors');
    }

    public function apiKeys(): void
    {
        $this->requirePermission('connecteur.gerer');
        $this->render('Connectors/Views/api_keys', [
            'cles_api' => $this->service->clesApi(['inclure_revoquees' => (bool) $this->get('revoked', false)]),
            'refs' => $this->service->referentiels(),
            'csrf_token' => $this->csrfToken(),
            'last_secret' => $_SESSION['last_api_secret'] ?? null,
            'pageTitle' => 'Clés API',
        ]);
        unset($_SESSION['last_api_secret']);
    }

    public function storeApiKey(): void
    {
        $this->requirePermission('connecteur.gerer');
        $this->validateCsrf();
        try {
            $_SESSION['last_api_secret'] = $this->service->creerCleApi($_POST, (int) $this->userId());
            $this->flash()->success('Clé API créée. Copiez le secret maintenant : il ne sera plus affiché.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/connectors/api-keys');
    }

    public function revokeApiKey(string $id): void
    {
        $this->requirePermission('connecteur.gerer');
        $this->validateCsrf();
        $this->service->revoquerCleApi((int) $id, (int) $this->userId());
        $this->flash()->success('Clé API révoquée.');
        $this->redirect('/connectors/api-keys');
    }

    public function webhooks(): void
    {
        $this->requirePermission('connecteur.gerer');
        $this->render('Connectors/Views/webhooks', [
            'webhooks' => $this->service->webhooks(['success' => (string) $this->get('success', 'all')]),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Journaux Webhooks',
        ]);
    }

    public function events(): void
    {
        $this->requirePermission('evenement.gerer');
        $this->render('Connectors/Views/events', [
            'evenements' => $this->service->evenements(),
            'refs' => $this->service->referentiels(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Événements applicatifs',
        ]);
    }

    public function storeEvent(): void
    {
        $this->requirePermission('evenement.gerer');
        $this->validateCsrf();
        try {
            $this->service->creerEvenement($_POST, (int) $this->userId());
            $this->flash()->success('Événement applicatif créé.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/connectors/events');
    }

    public function exportJson(): never
    {
        $this->requirePermission('connecteur.gerer');
        $this->json(true, $this->service->dashboard([]), 'Export Connecteurs / API / Webhooks.');
    }
}
