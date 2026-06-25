<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Settings\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Settings\Services\SettingsService;

class SettingsController extends BaseController
{
    private SettingsService $settings;

    public function __construct()
    {
        parent::__construct();
        $this->settings = new SettingsService();
    }

    public function index(): void
    {
        $this->requirePermission('settings.read');
        $data = $this->settings->tableauDeBord([
            'domain' => (string) $this->get('domain', ''),
            'q' => (string) $this->get('q', ''),
        ]);

        $this->render('Settings/Views/index', [
            'settings' => $data['settings'],
            'domains' => $data['domains'],
            'stats' => $data['stats'],
            'maintenance' => $data['maintenance'],
            'filters' => ['domain' => (string) $this->get('domain', ''), 'q' => (string) $this->get('q', '')],
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Paramètres application',
        ]);
    }

    public function system(): void
    {
        $this->requirePermission('settings.read');
        $data = $this->settings->tableauDeBord([]);
        $this->render('Settings/Views/system', [
            'domains' => $data['domains'],
            'stats' => $data['stats'],
            'maintenance' => $data['maintenance'],
            'settings' => $data['settings'],
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Configuration système',
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('settings.manage');
        $this->render('Settings/Views/form', [
            'setting' => null,
            'statuts' => $this->settings->statuts(),
            'pageTitle' => 'Nouveau paramètre',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('settings.manage');
        $this->validateCsrf();
        try {
            $id = $this->settings->creer($_POST, (int) $this->userId(), client_ip());
            $this->flash()->success('Paramètre créé.');
            $this->redirect('/settings/' . $id . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/settings/create');
        }
    }

    public function edit(string $id): void
    {
        $this->requirePermission('settings.manage');
        $setting = $this->settings->trouver((int) $id);
        if (!$setting) {
            $this->flash()->error('Paramètre introuvable.');
            $this->redirect('/settings');
        }

        $this->render('Settings/Views/form', [
            'setting' => $setting,
            'statuts' => $this->settings->statuts(),
            'pageTitle' => 'Modifier paramètre',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('settings.manage');
        $this->validateCsrf();
        try {
            $this->settings->modifier((int) $id, $_POST, (int) $this->userId(), client_ip());
            $this->flash()->success('Paramètre mis à jour.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/settings/' . (int) $id . '/edit');
    }

    public function delete(string $id): void
    {
        $this->requirePermission('settings.manage');
        $this->validateCsrf();
        $this->settings->supprimer((int) $id, (int) $this->userId(), client_ip());
        $this->flash()->success('Paramètre supprimé logiquement.');
        $this->redirect('/settings');
    }

    public function updateMaintenance(): void
    {
        $this->requirePermission('settings.manage');
        $this->validateCsrf();
        $this->settings->sauvegarderMaintenance($_POST, (int) $this->userId(), client_ip());
        $this->flash()->success('Configuration maintenance mise à jour.');
        $this->redirect('/settings/system');
    }

    public function exportJson(): never
    {
        $this->requirePermission('settings.read');
        $this->json(true, $this->settings->export(), 'Export des paramètres applicatifs.');
    }
}
