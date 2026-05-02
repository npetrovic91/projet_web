<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Models\ContextModel;
use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;

class ContextController extends AjaxController
{
    private ContextModel $contextModel;

    public function __construct()
    {
        parent::__construct();
        $this->contextModel = new ContextModel();
    }

    public function setCompany(): void
    {
        $companyId = (int) ($_POST['company_id'] ?? 0);
        if ($companyId <= 0 || !$this->contextModel->userOwnsCompany($this->user['id'], $companyId)) {
            AjaxResponseService::forbidden('Entreprise non autorisee.');
        }

        $this->contextModel->updateActiveCompany($this->user['id'], $companyId);
        $_SESSION['active_company_id'] = $companyId;
        unset($_SESSION['active_brand_id']);

        AjaxResponseService::success('Contexte entreprise mis a jour.', [
            'company_id' => $companyId,
            'brands' => $this->contextModel->getBrandsForCompany($companyId),
        ]);
    }

    public function setBrand(): void
    {
        $brandId = (int) ($_POST['brand_id'] ?? 0);
        $companyId = (int) ($_SESSION['active_company_id'] ?? 0);
        if ($brandId <= 0 || $companyId <= 0 || !$this->contextModel->companyHasBrand($companyId, $brandId)) {
            AjaxResponseService::forbidden('Marque non autorisee pour cette entreprise.');
        }

        $this->contextModel->updateActiveBrand($this->user['id'], $brandId);
        $_SESSION['active_brand_id'] = $brandId;
        AjaxResponseService::success('Contexte marque mis a jour.', ['brand_id' => $brandId]);
    }

    public function brandsForCompany(): void
    {
        $companyId = (int) ($_GET['company_id'] ?? ($_SESSION['active_company_id'] ?? 0));
        if ($companyId <= 0 || !$this->contextModel->userOwnsCompany($this->user['id'], $companyId)) {
            AjaxResponseService::forbidden('Entreprise non autorisee.');
        }

        AjaxResponseService::success('Marques chargees.', [
            'brands' => $this->contextModel->getBrandsForCompany($companyId),
        ]);
    }
}
