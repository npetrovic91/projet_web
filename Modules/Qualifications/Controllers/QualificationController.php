<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Qualifications\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Services\CatalogScopeService;
use Nenad\Autosav\Modules\Qualifications\Models\QualificationModel;
use Nenad\Autosav\Modules\Qualifications\Models\UserQualificationModel;
use Nenad\Autosav\Modules\Qualifications\Services\QualificationService;

class QualificationController extends BaseController
{
    private QualificationService $service;
    private CatalogScopeService $catalogScope;

    public function __construct()
    {
        parent::__construct();
        $this->service = new QualificationService(new QualificationModel(), new UserQualificationModel());
        $this->catalogScope = new CatalogScopeService();
    }

    public function index(): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('qualifications.read');
        $companyId = $this->activeCompanyId();
        $items = $this->service->getForContext($companyId, true, $this->networkOwnerCompanyIds());
        $this->render('Qualifications/Views/index', [
            'items' => $items,
            'companyId' => $companyId,
            'isSuperAdmin' => $this->isSuperAdmin(),
            'publicationScopes' => $this->publicationScopes(),
            'page_title' => 'Certifications',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('qualifications.manage');
        $scopeCode = $this->requestedScope($_POST['cer_portee_code'] ?? $_POST['qua_scope_code'] ?? null);
        $result = $this->service->create($_POST, $this->activeCompanyId(), $this->operatorId(), $scopeCode);
        if ($result['success']) {
            $this->flash('success', 'Certification creee.');
        } else {
            $this->flash('error', implode(' ', $result['errors']));
        }
        $this->redirect('/admin/qualifications');
    }

    public function edit(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('qualifications.manage');
        $item = $this->service->getById((int) $id, $this->activeCompanyId(), $this->networkOwnerCompanyIds());
        if ($item && !$this->canManageQualification($item)) {
            $item = null;
        }
        if (!$item) {
            $this->flash('error', 'Certification introuvable ou hors perimetre de gestion.');
            $this->redirect('/admin/qualifications');
        }
        $this->render('Qualifications/Views/edit', [
            'item' => $item,
            'isSuperAdmin' => $this->isSuperAdmin(),
            'publicationScopes' => $this->publicationScopes(),
            'page_title' => 'Modifier certification',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('qualifications.manage');
        $item = $this->service->getById((int) $id, $this->activeCompanyId(), $this->networkOwnerCompanyIds());
        if (!$item || !$this->canManageQualification($item)) {
            $this->flash('error', 'Certification introuvable ou hors perimetre de gestion.');
            $this->redirect('/admin/qualifications');
        }
        $_POST['cer_portee_code'] = $this->requestedScope($_POST['cer_portee_code'] ?? $item['cer_portee_code'] ?? $item['qua_scope_code'] ?? null);
        $result = $this->service->update((int) $id, $_POST, $this->operatorId());
        if ($result['success']) {
            $this->flash('success', 'Certification mise a jour.');
            $this->redirect('/admin/qualifications');
        }
        $this->flash('error', implode(' ', $result['errors']));
        $this->redirect('/admin/qualifications/' . (int) $id . '/edit');
    }

    public function toggle(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('qualifications.manage');
        $item = $this->service->getById((int) $id, $this->activeCompanyId(), $this->networkOwnerCompanyIds());
        if ($item && $this->canManageQualification($item)) {
            $this->service->setActive((int) $id, !((bool) $item['qua_is_active']), $this->operatorId());
            $this->flash('success', 'Statut mis a jour.');
        }
        $this->redirect('/admin/qualifications');
    }

    protected function operatorId(): int
    {
        return (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
    }

    private function canManageQualification(array $item): bool
    {
        return $this->isSuperAdmin()
            || ((int) ($item['cer_societe_proprietaire_id'] ?? $item['qua_owner_company_id'] ?? 0) === (int) $this->activeCompanyId());
    }

    private function requireCatalogManager(): void
    {
        $this->requireAuth();
        if (!CatalogScopeService::canManageCurrentUser()) {
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

    private function publicationScopes(): array
    {
        return $this->catalogScope->allowedPublicationScopes((int) $this->activeCompanyId(), $this->isSuperAdmin());
    }

    private function networkOwnerCompanyIds(): array
    {
        return $this->catalogScope->networkOwnerCompanyIds((int) $this->activeCompanyId());
    }

    private function isSuperAdmin(): bool
    {
        return CatalogScopeService::currentUserIsSuperAdmin();
    }
}
