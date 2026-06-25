<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Brands\Models\BrandModel;
use Nenad\Autosav\Modules\Companies\Models\CompanyModel;

class CompaniesAjaxController extends AjaxController
{
    private CompanyModel $companyModel;
    private BrandModel $brandModel;

    public function __construct()
    {
        parent::__construct();
        $this->companyModel = new CompanyModel();
        $this->brandModel = new BrandModel();
    }

    public function list(): void
    {
        $filters = [
            'type_code' => $_GET['type_code'] ?? '',
            'search' => $_GET['q'] ?? '',
            'is_active' => $_GET['is_active'] ?? 1,
        ];
        $data = $this->companyModel->paginate($filters, 1, min(100, max(5, (int) ($_GET['limit'] ?? 50))));
        AjaxResponseService::success('Entreprises chargees.', ['items' => $data['rows']]);
    }

    public function search(): void
    {
        $_GET['q'] = $_GET['q'] ?? ($_GET['search'] ?? '');
        $this->list();
    }

    public function byType(): void
    {
        $type = strtoupper(trim((string) ($_GET['type'] ?? '')));
        if ($type === '') {
            AjaxResponseService::badRequest('Type obligatoire.');
        }
        AjaxResponseService::success('Entreprises chargees.', ['items' => $this->companyModel->byTypeCode($type)]);
    }

    public function brandsForCompany(string $id = ''): void
    {
        $companyId = (int) $id;
        AjaxResponseService::success('Marques chargees.', ['items' => $this->brandModel->getBrandsForCompany($companyId)]);
    }

    public function listBrands(): void
    {
        AjaxResponseService::success('Marques chargees.', ['items' => $this->brandModel->allActive()]);
    }

    public function searchBrands(): void
    {
        $data = $this->brandModel->paginate(['search' => $_GET['q'] ?? ''], 1, min(50, max(5, (int) ($_GET['limit'] ?? 20))));
        AjaxResponseService::success('Marques chargees.', ['items' => $data['rows']]);
    }
}
