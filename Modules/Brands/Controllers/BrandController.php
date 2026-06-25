<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Brands\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Brands\Services\BrandService;

class BrandController extends BaseController
{
    private BrandService $brandService;

    public function __construct()
    {
        parent::__construct();
        $this->brandService = new BrandService();
    }

    public function index(): void
    {
        $this->requireAuth();
        $data = $this->brandService->listBrands(['search' => $this->get('search', '')], max(1, (int) $this->get('page', 1)), 25);
        $this->render('Brands/Views/brands/index', [
            'page_title' => 'Marques',
            'brands' => $data['rows'],
            'pagination' => $data,
            'search' => $this->get('search', ''),
            'canCreateBrand' => has_role(['super_administrateur', 'super_admin', 'SUPERADMIN']),
            'canEditBrand' => has_role(['super_administrateur', 'super_admin', 'SUPERADMIN']) || has_permission(['brands.update', 'brands.manage', 'admin.brands']),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->requireRole(['super_administrateur', 'super_admin', 'SUPERADMIN']);
        $this->render('Brands/Views/brands/form', [
            'page_title' => 'Nouvelle marque',
            'brand' => [],
            'errors' => [],
            'csrf_token' => $this->csrfToken(),
            'mode' => 'create',
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->requireRole(['super_administrateur', 'super_admin', 'SUPERADMIN']);
        $this->validateCsrf();
        $result = $this->brandService->save(null, $_POST, (int) ($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0));
        if ($result['success']) {
            $this->redirect('/brands');
        }
        $this->render('Brands/Views/brands/form', [
            'page_title' => 'Nouvelle marque',
            'brand' => $_POST,
            'errors' => $result['errors'],
            'csrf_token' => $this->csrfToken(),
            'mode' => 'create',
        ], 'main', 422);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->requireBrandPermission('brands.update');
        $brand = $this->brandService->find((int) $id);
        if (!$brand) {
            http_response_code(404);
            echo 'Marque introuvable.';
            return;
        }
        $this->render('Brands/Views/brands/form', [
            'page_title' => 'Modifier marque',
            'brand' => $brand,
            'errors' => [],
            'csrf_token' => $this->csrfToken(),
            'mode' => 'edit',
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->requireBrandPermission('brands.update');
        $this->validateCsrf();

        $brandId = (int) $id;
        $result = $this->brandService->save($brandId, $_POST, (int) ($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0));
        if (!($result['success'] ?? false)) {
            $brand = array_merge($this->brandService->find($brandId) ?? [], $_POST);
            $this->render('Brands/Views/brands/form', [
                'page_title' => 'Modifier marque',
                'brand' => $brand,
                'errors' => $result['errors'] ?? [],
                'csrf_token' => $this->csrfToken(),
                'mode' => 'edit',
            ], 'main', 422);
            return;
        }

        $this->redirect('/brands');
    }

    public function deactivate(string $id): void
    {
        $this->requireAuth();
        $this->requireBrandPermission('brands.delete');
        $this->validateCsrf();
        $brand = $this->brandService->find((int) $id);
        if ($brand) {
            $this->brandService->save((int) $id, [
                'brd_code' => $brand['brd_code'] ?? $brand['soc_code'] ?? '',
                'brd_name' => $brand['brd_name'] ?? $brand['soc_nom'] ?? '',
                'brd_is_active' => 0,
            ], (int) ($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0));
        }
        $this->redirect('/brands');
    }

    private function requireBrandPermission(string $permission): void
    {
        if (has_role(['super_administrateur', 'super_admin', 'SUPERADMIN'])) {
            return;
        }

        if (has_permission([$permission, 'brands.manage', 'admin.brands'])) {
            return;
        }

        if ($this->isAjax()) {
            $this->jsonError('Acces refuse.', 403);
        }

        http_response_code(403);
        $errorView = SRC_PATH . '/Core/Theme/Views/errors/403.php';
        file_exists($errorView) ? include $errorView : print('<h1>403 &mdash; Acces refuse</h1>');
        exit;
    }
}
