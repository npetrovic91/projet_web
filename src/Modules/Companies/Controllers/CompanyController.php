<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Brands\Services\BrandService;
use Nenad\Autosav\Modules\Companies\Services\CompanyService;

class CompanyController extends BaseController
{
    private CompanyService $companyService;
    private BrandService $brandService;

    public function __construct()
    {
        parent::__construct();
        $this->companyService = new CompanyService();
        $this->brandService = new BrandService();
    }

    public function index(): void
    {
        $this->requireAuth();
        $filters = [
            'type_code' => $this->get('type', ''),
            'search' => $this->get('search', ''),
            'is_active' => $this->get('is_active', ''),
        ];
        $data = $this->companyService->listCompanies($filters, max(1, (int) $this->get('page', 1)), 25);
        $this->render('Companies/Views/companies/index', [
            'page_title' => 'Entreprises',
            'companies' => $data['rows'],
            'types' => $this->companyService->getTypes(),
            'filters' => $filters,
            'pagination' => $data,
        ]);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $full = $this->companyService->getCompanyFull((int) $id);
        if (!$full) {
            http_response_code(404);
            echo 'Entreprise introuvable.';
            return;
        }
        $this->render('Companies/Views/companies/show', [
            'page_title' => $full['company']['com_name'],
            'company' => $full['company'],
            'relations' => $full['relations'],
            'brands' => $full['brands'],
            'available_brands' => $this->brandService->allActive(),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->render('Companies/Views/companies/form', [
            'page_title' => 'Nouvelle entreprise',
            'company' => [],
            'types' => $this->companyService->getTypes(),
            'holdings' => $this->companyService->getHoldings(),
            'csrf_token' => csrf_token(),
            'mode' => 'create',
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $result = $this->companyService->saveCompany(null, $_POST, (int) $_SESSION['user_id']);
        if ($result['success']) {
            $this->redirect('/companies/' . $result['id']);
        }
        $this->render('Companies/Views/companies/form', [
            'page_title' => 'Nouvelle entreprise',
            'company' => $_POST,
            'types' => $this->companyService->getTypes(),
            'holdings' => $this->companyService->getHoldings(),
            'csrf_token' => csrf_token(),
            'mode' => 'create',
            'errors' => $result['errors'],
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $full = $this->companyService->getCompanyFull((int) $id);
        if (!$full) {
            http_response_code(404);
            echo 'Entreprise introuvable.';
            return;
        }
        $this->render('Companies/Views/companies/form', [
            'page_title' => 'Modifier entreprise',
            'company' => $full['company'],
            'types' => $this->companyService->getTypes(),
            'holdings' => $this->companyService->getHoldings(),
            'csrf_token' => csrf_token(),
            'mode' => 'edit',
            'errors' => [],
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $result = $this->companyService->saveCompany((int) $id, $_POST, (int) $_SESSION['user_id']);
        if ($result['success']) {
            $this->redirect('/companies/' . $id);
        }
        $this->redirect('/companies/' . $id . '/edit');
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->companyService->deleteCompany((int) $id, (int) $_SESSION['user_id']);
        $this->redirect('/companies');
    }

    public function restore(string $id): void
    {
        $this->redirect('/companies/' . $id);
    }

    public function addRelation(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->companyService->addRelation((int) $_POST['parent_id'], (int) $_POST['child_id'], (string) $_POST['relation_type'], (int) $_SESSION['user_id']);
        $this->redirect('/companies/' . (int) $_POST['child_id']);
    }

    public function removeRelation(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->companyService->removeRelation((int) $id, (int) $_SESSION['user_id']);
        $this->redirect('/companies');
    }

    public function attachBrand(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $companyId = (int) $_POST['company_id'];
        $this->brandService->attachToCompany($companyId, (int) $_POST['brand_id'], (int) $_SESSION['user_id'], !empty($_POST['is_primary']));
        $this->redirect('/companies/' . $companyId);
    }

    public function detachBrand(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $companyId = (int) $_POST['company_id'];
        $this->brandService->detachFromCompany($companyId, (int) $_POST['brand_id'], (int) $_SESSION['user_id']);
        $this->redirect('/companies/' . $companyId);
    }
}
