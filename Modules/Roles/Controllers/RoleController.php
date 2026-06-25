<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Roles\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Services\CatalogScopeService;
use Nenad\Autosav\Modules\Roles\Models\RolePermissionModel;
use Nenad\Autosav\Modules\Roles\Services\RolePermissionService;

class RoleController extends BaseController
{
    private RolePermissionService $service;
    private RolePermissionModel $model;
    private CatalogScopeService $catalogScope;

    public function __construct()
    {
        parent::__construct();
        $this->model = new RolePermissionModel();
        $this->service = new RolePermissionService($this->model);
        $this->catalogScope = new CatalogScopeService();
    }

    public function index(): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('role.gerer');

        $filters = [
            'q' => $this->request->get('q'),
            'module_id' => $this->request->get('module_id'),
            'societe_id' => $this->request->get('societe_id'),
        ];
        if (!$this->estSuperAdmin()) {
            $filters['societe_ids_autorisees'] = $this->societesScope();
            $filters['network_societe_ids_autorisees'] = $this->networkOwnerCompanyIds();
            if (!empty($filters['societe_id'])) {
                $requestedSocieteId = (int) $filters['societe_id'];
                $allowedSocietes = array_merge($this->societesScope(), $this->networkOwnerCompanyIds());
                if (!in_array($requestedSocieteId, array_map('intval', $allowedSocietes), true)) {
                    $filters['societe_id'] = null;
                }
            }
        }

        $this->render('Roles/index', [
            'page_title' => 'Rôles & permissions',
            'roles' => $this->model->listerRoles($filters),
            'modules' => $this->model->listerModules(),
            'societes' => $this->model->listerSocietes($this->estSuperAdmin() ? null : $this->societesScope()),
            'filters' => $filters,
            'isSuperAdmin' => $this->estSuperAdmin(),
            'activeCompanyId' => $this->societeActiveId(),
            'publicationScopes' => $this->publicationScopes(),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function create(): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('role.gerer');
        $this->renderForm(null, [], '/roles/store', 'Nouveau rôle');
    }

    public function store(): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('role.gerer');
        $this->validateCsrf();

        $data = $this->forcerSocieteRole($this->request->all());
        $errors = $this->validerContexteMetier($data);
        if ($errors !== []) {
            $this->renderForm($data, $errors, '/roles/store', 'Nouveau rôle', 422);
            return;
        }

        $result = $this->service->creerRole($data, $this->userId());
        if ($result['success']) {
            $this->flash('success', 'Rôle créé. Vous pouvez maintenant définir ses permissions.');
            $this->redirect('/roles/' . (int) $result['id'] . '/edit');
        }

        $this->renderForm($data, $result['errors'], '/roles/store', 'Nouveau rôle', 422);
    }

    public function show(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('role.gerer');
        $role = $this->model->trouverRole((int) $id);
        if (!$role || !$this->peutAdministrerRole((int) $id)) {
            $this->flash('error', 'Rôle introuvable.');
            $this->redirect('/roles');
        }

        $this->render('Roles/show', [
            'page_title' => 'Détail rôle',
            'role' => $role,
            'permissions' => $this->model->permissionsDuRole((int) $id),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('role.gerer');
        $role = $this->model->trouverRole((int) $id);
        if (!$role || !$this->peutAdministrerRole((int) $id)) {
            $this->flash('error', 'Rôle introuvable ou hors périmètre de gestion.');
            $this->redirect('/roles');
        }
        $this->renderForm($role, [], '/roles/' . (int) $id . '/update', 'Modifier rôle', 200, (int) $id);
    }

    public function update(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('role.gerer');
        $this->validateCsrf();
        $roleId = (int) $id;
        if (!$this->peutAdministrerRole($roleId)) {
            $this->flash('error', 'Rôle hors périmètre société.');
            $this->redirect('/roles');
        }

        $data = $this->forcerSocieteRole($this->request->all());
        $errors = $this->validerContexteMetier($data);
        if ($errors !== []) {
            $role = array_merge($this->model->trouverRole($roleId) ?? [], $data);
            $this->renderForm($role, $errors, '/roles/' . $roleId . '/update', 'Modifier rôle', 422, $roleId);
            return;
        }

        $result = $this->service->modifierRole($roleId, $data, $this->userId());
        if ($result['success']) {
            $this->flash('success', 'Rôle mis à jour.');
            $this->redirect('/roles/' . $roleId . '/edit');
        }
        $role = array_merge($this->model->trouverRole($roleId) ?? [], $this->request->all());
        $this->renderForm($role, $result['errors'], '/roles/' . $roleId . '/update', 'Modifier rôle', 422, $roleId);
    }

    public function delete(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('role.gerer');
        $this->validateCsrf();
        if (!$this->peutAdministrerRole((int) $id)) {
            $this->flash('error', 'Rôle hors périmètre société.');
            $this->redirect('/roles');
        }
        $this->model->supprimerRole((int) $id, $this->userId());
        $this->flash('success', 'Rôle supprimé logiquement.');
        $this->redirect('/roles');
    }

    public function syncPermissions(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('role.gerer');
        $this->validateCsrf();
        $roleId = (int) $id;
        $role = $this->model->trouverRole($roleId);
        if (!$role || !$this->peutAdministrerRole($roleId)) {
            $this->flash('error', 'Rôle introuvable.');
            $this->redirect('/roles');
        }
        $effets = $this->service->extraireEffetsPermissions($this->request->all());
        $this->model->synchroniserPermissionsRole($roleId, $effets, $this->userId());
        $this->flash('success', 'Permissions du rôle synchronisées. Règle appliquée à l’exécution : refuser > autoriser.');
        $this->redirect('/roles/' . $roleId . '/edit');
    }

    private function renderForm(?array $role, array $errors, string $action, string $title, int $httpCode = 200, ?int $roleId = null): void
    {
        $isSuperAdmin = $this->estSuperAdmin();
        $societeId = $this->societeActiveId();

        if ($isSuperAdmin) {
            $typesSocietes = $this->model->listerTypesSocietes();
        } elseif ($societeId > 0) {
            $typesSocietes = $this->model->listerTypesSocietesParSociete($societeId);
        } else {
            $typesSocietes = [];
        }

        $this->render('Roles/form', [
            'page_title' => $title,
            'role' => $role,
            'action' => $action,
            'errors' => $errors,
            'modules' => $this->model->listerModules(),
            'societes' => $this->model->listerSocietes($isSuperAdmin ? null : $this->societesScope()),
            'typesSocietes' => $typesSocietes,
            'statuts' => $this->model->listerStatutsGeneraux(),
            'permissions' => $roleId ? $this->model->permissionsDuRole($roleId) : [],
            'peutCreerGlobal' => $isSuperAdmin,
            'isSuperAdmin' => $isSuperAdmin,
            'activeCompanyId' => $societeId,
            'publicationScopes' => $this->publicationScopes(),
            'csrf_token' => $this->csrfToken(),
        ], 'main', $httpCode);
    }

    private function forcerSocieteRole(array $data): array
    {
        $isSuperAdmin = $this->estSuperAdmin();
        $societeId = $this->societeActiveId();
        $data['rol_portee_code'] = $this->catalogScope->normalizeRequestedScope($data['rol_portee_code'] ?? null, $societeId, $isSuperAdmin);

        if ($isSuperAdmin) {
            if ($data['rol_portee_code'] === CatalogScopeService::SCOPE_PLATFORM) {
                $data['rol_societe_proprietaire_id'] = null;
            } elseif (empty($data['rol_societe_proprietaire_id']) && $societeId > 0) {
                $data['rol_societe_proprietaire_id'] = $societeId;
            }
            return $data;
        }

        if ($societeId <= 0 || !in_array($societeId, $this->societesScope(), true)) {
            $this->flash('error', 'Aucune société active administrable.');
            $this->redirect('/roles');
        }

        $data['rol_societe_proprietaire_id'] = $societeId;
        return $data;
    }

    private function peutAdministrerRole(int $roleId): bool
    {
        return $this->estSuperAdmin()
            || $this->model->roleDansSocietesAutorisees($roleId, $this->societesScope());
    }

    private function societesScope(): array
    {
        $active = $this->societeActiveId();
        if ($active > 0) {
            return [$active];
        }

        $companies = is_array($_SESSION['available_companies'] ?? null) ? $_SESSION['available_companies'] : [];
        $ids = [];
        foreach ($companies as $company) {
            $id = (int) ($company['soc_id'] ?? $company['com_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function societeActiveId(): int
    {
        return (int) ($this->activeCompanyId() ?? 0);
    }

    private function estSuperAdmin(): bool
    {
        return CatalogScopeService::currentUserIsSuperAdmin();
    }

    private function requireCatalogManager(): void
    {
        $this->requireAuth();
        if (!CatalogScopeService::canManageCurrentUser()) {
            if ($this->isAjax()) {
                $this->jsonError('Accès réservé aux profils PDG et chef de service.', 403);
            }
            http_response_code(403);
            $errorView = SRC_PATH . '/Core/Theme/Views/errors/403.php';
            file_exists($errorView) ? include $errorView : print('<h1>403 &mdash; Accès refusé</h1>');
            exit;
        }
    }

    private function publicationScopes(): array
    {
        return $this->catalogScope->allowedPublicationScopes($this->societeActiveId(), $this->estSuperAdmin());
    }

    private function networkOwnerCompanyIds(): array
    {
        return $this->catalogScope->networkOwnerCompanyIds($this->societeActiveId());
    }

    /**
     * Valide les règles métier de portée :
     *  – la portée plateforme est réservée au super-admin ;
     *  – les types société non-super-admin doivent appartenir à la société active.
     *
     * @return array<int,string>
     */
    private function validerContexteMetier(array $data): array
    {
        $isSuperAdmin = $this->estSuperAdmin();
        $societeId = $this->societeActiveId();
        $errors = [];

        if (($data['rol_portee_code'] ?? 'interne') === CatalogScopeService::SCOPE_PLATFORM && !$isSuperAdmin) {
            $errors[] = 'La portée plateforme est réservée au super-administrateur.';
        }

        $typesAutorises = [];
        if (!$isSuperAdmin && $societeId > 0) {
            $rows = $this->model->listerTypesSocietesParSociete($societeId);
            $typesAutorises = array_map('intval', array_column($rows, 'tso_id'));
        }

        return array_merge($errors, $this->service->validerContexteRole($data, $isSuperAdmin, $typesAutorises));
    }
}
