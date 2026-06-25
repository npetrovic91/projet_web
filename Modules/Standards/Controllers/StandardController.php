<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Standards\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Standards\Services\StandardService;

class StandardController extends BaseController
{
    private StandardService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new StandardService();
    }

    public function index(): void
    {
        $this->requirePermission('standard.consulter');
        $filters = [
            'q' => (string) $this->get('q', ''),
            'type' => (string) $this->get('type', ''),
            'societe_id' => (int) $this->get('societe_id', 0),
        ];
        $this->render('Standards/Views/index', $this->service->dashboard($filters) + [
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Standards / versions / exigences',
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('standard.gerer');
        $this->render('Standards/Views/form', [
            'standard' => null,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Nouveau standard',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('standard.gerer');
        $this->validateCsrf();
        try {
            $id = $this->service->creerStandard($_POST, $this->userId());
            $this->flash()->success('Standard créé.');
            $this->redirect('/standards/' . $id . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/standards/create');
        }
    }

    public function edit(string $id): void
    {
        $this->requirePermission('standard.gerer');
        $standard = $this->service->standard((int) $id);
        if (!$standard) {
            $this->flash()->error('Standard introuvable.');
            $this->redirect('/standards');
        }
        $this->render('Standards/Views/form', [
            'standard' => $standard,
            'versions' => $this->service->versions((int) $id),
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Modifier standard',
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('standard.gerer');
        $this->validateCsrf();
        try {
            $this->service->modifierStandard((int) $id, $_POST, $this->userId());
            $this->flash()->success('Standard mis à jour.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/standards/' . (int) $id . '/edit');
    }

    public function delete(string $id): void
    {
        $this->requirePermission('standard.gerer');
        $this->validateCsrf();
        $this->service->supprimerStandard((int) $id, $this->userId());
        $this->flash()->success('Standard supprimé logiquement.');
        $this->redirect('/standards');
    }

    public function versions(): void
    {
        $this->requirePermission('standard.consulter');
        $this->render('Standards/Views/versions', [
            'versions' => $this->service->versions(),
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Versions de standards',
        ]);
    }

    public function storeVersion(): void
    {
        $this->requirePermission('standard.gerer');
        $this->validateCsrf();
        try {
            $this->service->creerVersion($_POST, $this->userId());
            $this->flash()->success('Version créée.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/standards/versions');
    }

    public function updateVersion(string $id): void
    {
        $this->requirePermission('standard.gerer');
        $this->validateCsrf();
        try {
            $this->service->modifierVersion((int) $id, $_POST, $this->userId());
            $this->flash()->success('Version mise à jour.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/standards/versions');
    }

    public function deleteVersion(string $id): void
    {
        $this->requirePermission('standard.gerer');
        $this->validateCsrf();
        $this->service->supprimerVersion((int) $id, $this->userId());
        $this->flash()->success('Version supprimée logiquement.');
        $this->redirect('/standards/versions');
    }

    public function exigences(): void
    {
        $this->requirePermission('standard.consulter');
        $versionId = (int) $this->get('version_id', 0);
        $this->render('Standards/Views/exigences', [
            'exigences' => $this->service->exigences($versionId > 0 ? $versionId : null),
            'refs' => $this->service->refs(),
            'version_id' => $versionId,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Exigences de standards',
        ]);
    }

    public function storeExigence(): void
    {
        $this->requirePermission('standard.gerer');
        $this->validateCsrf();
        try {
            $this->service->creerExigence($_POST, $this->userId());
            $this->flash()->success('Exigence créée.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $target = !empty($_POST['evs_version_standard_id']) ? ('?version_id=' . (int) $_POST['evs_version_standard_id']) : '';
        $this->redirect('/standards/exigences' . $target);
    }

    public function deleteExigence(string $id): void
    {
        $this->requirePermission('standard.gerer');
        $this->validateCsrf();
        $this->service->supprimerExigence((int) $id, $this->userId());
        $this->flash()->success('Exigence supprimée logiquement.');
        $this->redirect('/standards/exigences');
    }

    public function evaluation(string $versionId): void
    {
        $this->requirePermission('standard.consulter');
        $version = $this->service->version((int) $versionId);
        if (!$version) {
            $this->flash()->error('Version de standard introuvable.');
            $this->redirect('/standards/versions');
        }
        $this->render('Standards/Views/evaluation', [
            'version' => $version,
            'rows' => $this->service->evaluation((int) $versionId),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Évaluation standard / utilisateurs',
        ]);
    }

    public function exportJson(): never
    {
        $this->requirePermission('standard.consulter');
        $this->json(true, $this->service->dashboard([]), 'Export Standards / versions / exigences.');
    }
}
