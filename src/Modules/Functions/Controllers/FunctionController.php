<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Functions\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Functions\Models\FunctionModel;
use Nenad\Autosav\Modules\Functions\Models\UserFunctionModel;
use Nenad\Autosav\Modules\Functions\Services\FunctionService;

class FunctionController extends BaseController
{
    private FunctionService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new FunctionService(new FunctionModel(), new UserFunctionModel());
    }

    public function index(): void
    {
        $this->requireAuth();
        $companyId = $this->activeCompanyId();
        $this->render('Functions/index', [
            'functions' => $this->service->getForContext($companyId, true),
            'companyId' => $companyId,
            'isSuperAdmin' => has_role('SUPERADMIN'),
            'page_title' => 'Fonctions',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->render('Functions/form', [
            'function' => null,
            'action' => '/functions/store',
            'errors' => [],
            'isSuperAdmin' => has_role('SUPERADMIN'),
            'page_title' => 'Nouvelle fonction',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $data = $this->request->all();
        $isGlobal = has_role('SUPERADMIN') && !empty($data['fnc_is_global']);
        $result = $this->service->create($data, $this->activeCompanyId(), (int) $this->getCurrentUser()['use_id'], $isGlobal);

        if ($result['success']) {
            $this->flash('success', 'Fonction creee.');
            $this->redirect('/functions');
        }

        $this->render('Functions/form', [
            'function' => $data,
            'action' => '/functions/store',
            'errors' => $result['errors'],
            'isSuperAdmin' => has_role('SUPERADMIN'),
            'page_title' => 'Nouvelle fonction',
            'csrf_token' => $this->csrfToken(),
        ], 'main', 422);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $function = $this->service->getById((int) $id, $this->activeCompanyId());
        if (!$function) {
            $this->flash('error', 'Fonction introuvable ou hors perimetre.');
            $this->redirect('/functions');
        }

        $this->render('Functions/form', [
            'function' => $function,
            'action' => '/functions/' . (int) $id . '/update',
            'errors' => [],
            'isSuperAdmin' => has_role('SUPERADMIN'),
            'page_title' => 'Modifier fonction',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $result = $this->service->update((int) $id, $this->request->all(), (int) $this->getCurrentUser()['use_id']);
        if ($result['success']) {
            $this->flash('success', 'Fonction mise a jour.');
            $this->redirect('/functions');
        }
        $this->flash('error', implode(' ', $result['errors']));
        $this->redirect('/functions/' . (int) $id . '/edit');
    }

    public function toggle(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $function = $this->service->getById((int) $id, $this->activeCompanyId());
        if ($function) {
            $this->service->setActive((int) $id, !((bool) $function['fnc_is_active']), (int) $this->getCurrentUser()['use_id']);
        }
        $this->redirect('/functions');
    }
}
