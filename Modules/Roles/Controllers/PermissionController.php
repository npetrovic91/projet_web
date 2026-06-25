<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Roles\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Roles\Models\RolePermissionModel;
use Nenad\Autosav\Modules\Roles\Services\RolePermissionService;

class PermissionController extends BaseController
{
    private RolePermissionModel $model;
    private RolePermissionService $service;

    public function __construct()
    {
        parent::__construct();
        $this->model = new RolePermissionModel();
        $this->service = new RolePermissionService($this->model);
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->requirePermission('permission.gerer');
        $filters = [
            'q' => $this->request->get('q'),
            'module_id' => $this->request->get('module_id'),
        ];
        $this->render('Roles/permissions', [
            'page_title' => 'Permissions',
            'permissions' => $this->model->listerPermissions($filters),
            'modules' => $this->model->listerModules(),
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->requirePermission('permission.gerer');
        $this->renderForm(null, [], '/permissions/store', 'Nouvelle permission');
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->requirePermission('permission.gerer');
        $this->validateCsrf();
        $data = $this->request->all();
        $result = $this->service->creerPermission($data, $this->userId());
        if ($result['success']) {
            $this->flash('success', 'Permission créée.');
            $this->redirect('/permissions');
        }
        $this->renderForm($data, $result['errors'], '/permissions/store', 'Nouvelle permission', 422);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->requirePermission('permission.gerer');
        $permission = $this->model->trouverPermission((int) $id);
        if (!$permission) {
            $this->flash('error', 'Permission introuvable.');
            $this->redirect('/permissions');
        }
        $this->renderForm($permission, [], '/permissions/' . (int) $id . '/update', 'Modifier permission');
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->requirePermission('permission.gerer');
        $this->validateCsrf();
        $permissionId = (int) $id;
        $result = $this->service->modifierPermission($permissionId, $this->request->all(), $this->userId());
        if ($result['success']) {
            $this->flash('success', 'Permission mise à jour.');
            $this->redirect('/permissions');
        }
        $permission = array_merge($this->model->trouverPermission($permissionId) ?? [], $this->request->all());
        $this->renderForm($permission, $result['errors'], '/permissions/' . $permissionId . '/update', 'Modifier permission', 422);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->requirePermission('permission.gerer');
        $this->validateCsrf();
        $this->model->supprimerPermission((int) $id, $this->userId());
        $this->flash('success', 'Permission supprimée logiquement.');
        $this->redirect('/permissions');
    }

    private function renderForm(?array $permission, array $errors, string $action, string $title, int $httpCode = 200): void
    {
        $this->render('Roles/permission_form', [
            'page_title' => $title,
            'permission' => $permission,
            'action' => $action,
            'errors' => $errors,
            'modules' => $this->model->listerModules(),
            'statuts' => $this->model->listerStatutsGeneraux(),
            'csrf_token' => $this->csrfToken(),
        ], 'main', $httpCode);
    }
}
