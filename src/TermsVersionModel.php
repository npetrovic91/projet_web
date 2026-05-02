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
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->render('Brands/Views/brands/form', [
            'page_title' => 'Nouvelle marque',
            'brand' => [],
            'errors' => [],
            'csrf_token' => csrf_token(),
            'mode' => 'create',
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $result = $this->brandService->save(null, $_POST, (int) $_SESSION['user_id']);
        if ($result['success']) {
            $this->redirect('/brands');
        }
        $this->render('Brands/Views/brands/form', [
            'page_title' => 'Nouvelle marque',
            'brand' => $_POST,
            'errors' => $result['errors'],
            'csrf_token' => csrf_token(),
            'mode' => 'create',
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $brand = database()->fetch("SELECT * FROM sav_brands WHERE brd_id = :id LIMIT 1", [':id' => (int) $id]);
        $this->render('Brands/Views/brands/form', [
            'page_title' => 'Modifier marque',
            'brand' => $brand ?? [],
            'errors' => [],
            'csrf_token' => csrf_token(),
            'mode' => 'edit',
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->brandService->save((int) $id, $_POST, (int) $_SESSION['user_id']);
        $this->redirect('/brands');
    }

    public function deactivate(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        database()->execute(
            "UPDATE sav_brands SET brd_is_active = 0, brd_updated_by = :user_id, brd_updated_at = NOW() WHERE brd_id = :id",
            [':id' => (int) $id, ':user_id' => (int) $_SESSION['user_id']]
        );
        $this->redirect('/brands');
    }
}
