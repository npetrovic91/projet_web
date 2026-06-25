<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Roles\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Roles\Models\RolePermissionModel;

class AccessPolicyController extends BaseController
{
    private RolePermissionModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new RolePermissionModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->requirePermission('permission.gerer');
        $filters = [
            'q' => $this->request->get('q'),
            'permission_id' => $this->request->get('permission_id'),
        ];
        $this->render('Roles/policies', [
            'page_title' => 'Politiques ABAC',
            'policies' => $this->model->listerPolitiquesAcces($filters),
            'permissions' => $this->model->listerPermissions(),
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $this->requirePermission('permission.gerer');
        $policy = $this->model->trouverPolitiqueAcces((int) $id);
        if (!$policy) {
            $this->flash('error', 'Politique ABAC introuvable.');
            $this->redirect('/access-policies');
        }
        $this->render('Roles/policy_show', [
            'page_title' => 'Détail politique ABAC',
            'policy' => $policy,
            'conditions' => $this->model->conditionsPolitique((int) $id),
        ]);
    }
}
