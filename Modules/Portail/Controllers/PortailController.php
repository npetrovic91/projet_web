<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Portail\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Portail\Services\ContexteActifService;

class PortailController extends BaseController
{
    private ContexteActifService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ContexteActifService();
    }

    public function index(): void
    {
        $this->requirePermission('portail.read');
        $this->render('Portail/Views/index', [
            'pageTitle' => 'Portail société',
            'data' => $this->service->donneesPortail((int)$this->userId()),
        ]);
    }

    public function contexte(): void
    {
        $this->requirePermission('portail.read');
        $this->render('Portail/Views/contexte', [
            'pageTitle' => 'Contexte actif',
            'data' => $this->service->donneesPortail((int)$this->userId()),
        ]);
    }

    public function optionsJson(): void
    {
        $this->requirePermission('portail.read');
        $input = array_merge($_GET, $this->jsonInput());
        $this->json(true, $this->service->optionsDependantes((int)$this->userId(), $input), 'Options du contexte actif.');
    }

    public function updateContexte(): void
    {
        $this->requirePermission('portail.context.update');
        $this->validateCsrf();
        try {
            $contexte = $this->service->appliquerContexte((int)$this->userId(), $_POST);
            $this->flash()->success('Contexte actif mis à jour.');
            if ($this->isAjax()) {
                $this->json(true, $contexte, 'Contexte actif mis à jour.');
            }
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            if ($this->isAjax()) {
                $this->jsonError($e->getMessage(), 422);
            }
        }
        $this->redirect('/portail/contexte');
    }
}
