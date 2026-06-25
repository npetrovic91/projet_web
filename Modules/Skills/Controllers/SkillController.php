<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Skills\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Services\CatalogScopeService;
use Nenad\Autosav\Modules\Skills\Models\SkillModel;
use Nenad\Autosav\Modules\Skills\Models\UserSkillModel;
use Nenad\Autosav\Modules\Skills\Services\SkillService;

class SkillController extends BaseController
{
    private SkillService $service;
    private CatalogScopeService $catalogScope;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SkillService(new SkillModel(), new UserSkillModel());
        $this->catalogScope = new CatalogScopeService();
    }

    public function index(): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('skills.read');
        $companyId = $this->activeCompanyId();
        $items = $this->service->getForContext($companyId, true, $this->networkOwnerCompanyIds());
        $this->render('Skills/Views/index', [
            'items' => $items,
            'levels' => $this->service->levels(),
            'companyId' => $companyId,
            'isSuperAdmin' => $this->isSuperAdmin(),
            'publicationScopes' => $this->publicationScopes(),
            'page_title' => 'Competences metier',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('skills.manage');
        $scopeCode = $this->requestedScope($_POST['cmp_portee_code'] ?? $_POST['skl_scope_code'] ?? null);
        $result = $this->service->create($_POST, $this->activeCompanyId(), $this->operatorId(), $scopeCode);

        if ($result['success']) {
            $this->flash('success', 'Competence creee.');
        } else {
            $this->flash('error', implode(' ', $result['errors']));
        }
        $this->redirect('/admin/skills');
    }

    public function edit(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('skills.manage');
        $item = $this->service->getById((int) $id, $this->activeCompanyId(), $this->networkOwnerCompanyIds());
        if ($item && !$this->canManageSkill($item)) {
            $item = null;
        }
        if (!$item) {
            $this->flash('error', 'Competence introuvable ou hors perimetre de gestion.');
            $this->redirect('/admin/skills');
        }

        $this->render('Skills/Views/edit', [
            'item' => $item,
            'levels' => $this->service->levels(),
            'isSuperAdmin' => $this->isSuperAdmin(),
            'publicationScopes' => $this->publicationScopes(),
            'page_title' => 'Modifier competence',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('skills.manage');
        $item = $this->service->getById((int) $id, $this->activeCompanyId(), $this->networkOwnerCompanyIds());
        if (!$item || !$this->canManageSkill($item)) {
            $this->flash('error', 'Competence introuvable ou hors perimetre de gestion.');
            $this->redirect('/admin/skills');
        }
        $_POST['cmp_portee_code'] = $this->requestedScope($_POST['cmp_portee_code'] ?? $item['cmp_portee_code'] ?? $item['skl_scope_code'] ?? null);
        $result = $this->service->update((int) $id, $_POST, $this->operatorId());
        if ($result['success']) {
            $this->flash('success', 'Competence mise a jour.');
            $this->redirect('/admin/skills');
        }
        $this->flash('error', implode(' ', $result['errors']));
        $this->redirect('/admin/skills/' . (int) $id . '/edit');
    }

    public function toggle(string $id): void
    {
        $this->requireCatalogManager();
        $this->requirePermission('skills.manage');
        $item = $this->service->getById((int) $id, $this->activeCompanyId(), $this->networkOwnerCompanyIds());
        if ($item && $this->canManageSkill($item)) {
            $this->service->setActive((int) $id, !((bool) $item['skl_is_active']), $this->operatorId());
            $this->flash('success', 'Statut mis a jour.');
        }
        $this->redirect('/admin/skills');
    }

    protected function operatorId(): int
    {
        return (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
    }

    private function canManageSkill(array $item): bool
    {
        return $this->isSuperAdmin()
            || ((int) ($item['cmp_societe_proprietaire_id'] ?? $item['skl_owner_company_id'] ?? 0) === (int) $this->activeCompanyId());
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
