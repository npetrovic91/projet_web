<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Functions\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Services\CatalogScopeService;
use Nenad\Autosav\Modules\Functions\Models\FunctionModel;
use Nenad\Autosav\Modules\Functions\Models\UserFunctionModel;
use Nenad\Autosav\Modules\Functions\Services\FunctionService;

class FunctionController extends BaseController
{
    private FunctionService $service;
    private CatalogScopeService $catalogScope;

    public function __construct()
    {
        parent::__construct();
        $this->service = new FunctionService(new FunctionModel(), new UserFunctionModel());
        $this->catalogScope = new CatalogScopeService();
    }

    public function index(): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('functions.read');
        $companyId = $this->activeCompanyId();
        try {
            $functions = $this->service->getForContext($companyId, true, $this->networkOwnerCompanyIds());
        } catch (\Throwable $e) {
            error_log('[AUTOSAV][Functions] index failed: ' . $e->getMessage());
            $functions = [];
            $this->flash('error', 'Erreur de chargement des fonctions. La page est affichee en mode securise.');
        }

        $this->render('Functions/index', [
            'functions' => $functions,
            'companyTypes' => $this->service->companyTypes(),
            'companyId' => $companyId,
            'isSuperAdmin' => $this->isSuperAdmin(),
            'publicationScopes' => $this->safePublicationScopes(),
            'page_title' => 'Fonctions métier',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function create(): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('functions.manage');
        $this->render('Functions/form', [
            'function' => null,
            'companyTypes' => $this->service->companyTypes(),
            'publicationScopes' => $this->safePublicationScopes(),
            'action' => '/functions/store',
            'errors' => [],
            'isSuperAdmin' => $this->isSuperAdmin(),
            'page_title' => 'Nouvelle fonction métier',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('functions.manage');
        $data = $this->request->all();
        $scopeCode = $this->requestedScope($data['fon_portee_code'] ?? $data['fnc_scope_code'] ?? null);
        $result = $this->service->create($data, $this->activeCompanyId(), $this->operatorId(), $scopeCode);

        if ($result['success']) {
            $this->flash('success', 'Fonction métier créée.');
            $this->redirect('/functions');
        }

        $data['fon_portee_code'] = $scopeCode;
        $this->render('Functions/form', [
            'function' => $data,
            'companyTypes' => $this->service->companyTypes(),
            'publicationScopes' => $this->safePublicationScopes(),
            'action' => '/functions/store',
            'errors' => $result['errors'],
            'isSuperAdmin' => $this->isSuperAdmin(),
            'page_title' => 'Nouvelle fonction métier',
            'csrf_token' => $this->csrfToken(),
        ], 'main', 422);
    }

    public function edit(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('functions.manage');
        $function = $this->service->getById((int) $id, $this->activeCompanyId(), $this->networkOwnerCompanyIds());
        if ($function && !$this->canManageFunction($function)) {
            $function = null;
        }
        if (!$function) {
            $this->flash('error', 'Fonction introuvable ou hors périmètre de gestion.');
            $this->redirect('/functions');
        }

        $this->render('Functions/form', [
            'function' => $function,
            'companyTypes' => $this->service->companyTypes(),
            'publicationScopes' => $this->safePublicationScopes(),
            'action' => '/functions/' . (int) $id . '/update',
            'errors' => [],
            'isSuperAdmin' => $this->isSuperAdmin(),
            'page_title' => 'Modifier fonction métier',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('functions.manage');
        $function = $this->service->getById((int) $id, $this->activeCompanyId(), $this->networkOwnerCompanyIds());
        if (!$function || !$this->canManageFunction($function)) {
            $this->flash('error', 'Fonction introuvable ou hors perimetre de gestion.');
            $this->redirect('/functions');
        }
        $data = $this->request->all();
        $data['fon_portee_code'] = $this->requestedScope($data['fon_portee_code'] ?? $function['fon_portee_code'] ?? $function['fnc_scope_code'] ?? null);
        $result = $this->service->update((int) $id, $data, $this->operatorId());
        if ($result['success']) {
            $this->flash('success', 'Fonction métier mise à jour.');
            $this->redirect('/functions');
        }
        $this->flash('error', implode(' ', $result['errors']));
        $this->redirect('/functions/' . (int) $id . '/edit');
    }

    public function toggle(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('functions.manage');
        $function = $this->service->getById((int) $id, $this->activeCompanyId(), $this->networkOwnerCompanyIds());
        if ($function && $this->canManageFunction($function)) {
            $this->service->setActive((int) $id, !((bool) $function['fnc_is_active']), $this->operatorId());
        }
        $this->redirect('/functions');
    }

    protected function operatorId(): int
    {
        return (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
    }

    private function canManageFunction(array $function): bool
    {
        return $this->isSuperAdmin()
            || ((int) ($function['fon_societe_proprietaire_id'] ?? $function['fnc_owner_company_id'] ?? 0) === (int) $this->activeCompanyId());
    }

    private function requireCatalogManager(): void
    {
        $this->requireAuth();
        if (!CatalogScopeService::canManageCurrentUser()) {
            if ($this->isAjax()) {
                $this->jsonError('Acces reserve aux profils PDG et chef de service.', 403);
            }
            http_response_code(403);
            $errorView = SRC_PATH . '/Core/Theme/Views/errors/403.php';
            file_exists($errorView) ? include $errorView : print('<h1>403 &mdash; Acces refuse</h1>');
            exit;
        }
    }

    private function requestedScope(?string $scope): string
    {
        return $this->catalogScope->normalizeRequestedScope($scope, (int) $this->activeCompanyId(), $this->isSuperAdmin());
    }

    private function safePublicationScopes(): array
    {
        try {
            return $this->catalogScope->allowedPublicationScopes((int) $this->activeCompanyId(), $this->isSuperAdmin());
        } catch (\Throwable $e) {
            error_log('[AUTOSAV][Functions] publication scopes failed: ' . $e->getMessage());
            return ['interne' => 'Interne societe'];
        }
    }

    private function networkOwnerCompanyIds(): array
    {
        try {
            return $this->catalogScope->networkOwnerCompanyIds((int) $this->activeCompanyId());
        } catch (\Throwable $e) {
            error_log('[AUTOSAV][Functions] network scope failed: ' . $e->getMessage());
            $companyId = (int) $this->activeCompanyId();
            return $companyId > 0 ? [$companyId] : [];
        }
    }

    private function isSuperAdmin(): bool
    {
        return CatalogScopeService::currentUserIsSuperAdmin();
    }
}
