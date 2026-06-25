<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Core\Services\CatalogScopeService;
use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Qualifications\Models\QualificationModel;
use Nenad\Autosav\Modules\Qualifications\Models\UserQualificationModel;
use Nenad\Autosav\Modules\Qualifications\Services\QualificationService;

class QualificationsAjaxController extends AjaxController
{
    private QualificationService $service;
    private CatalogScopeService $catalogScope;

    public function __construct()
    {
        parent::__construct();
        $this->service = new QualificationService(new QualificationModel(), new UserQualificationModel());
        $this->catalogScope = new CatalogScopeService();
    }

    /** GET /ajax/qualifications/list?company_id=X */
    public function list(): void
    {
        $companyId = $this->requestedCompanyId();
        $rows = $this->service->getForContext($companyId, false, $this->networkOwnerCompanyIds($companyId));
        AjaxResponseService::success('OK', ['qualifications' => array_map([$this, 'mapQualification'], $rows)]);
    }

    /** GET /ajax/qualifications/search?q=X&company_id=X */
    public function search(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $companyId = $this->requestedCompanyId();
        $rows = $q === '' ? [] : $this->service->search($q, $companyId, $this->networkOwnerCompanyIds($companyId));
        $results = array_map([$this, 'mapQualification'], $rows);
        AjaxResponseService::success('OK', ['results' => $results, 'qualifications' => $results]);
    }

    private function requestedCompanyId(): ?int
    {
        $companyId = (int) ($_GET['company_id'] ?? ($_SESSION['active_company_id'] ?? 0));
        return $companyId > 0 ? $companyId : null;
    }

    private function networkOwnerCompanyIds(?int $companyId): array
    {
        return $companyId ? $this->catalogScope->networkOwnerCompanyIds($companyId) : [];
    }

    private function mapQualification(array $row): array
    {
        return [
            'id' => (int) ($row['qua_id'] ?? $row['cer_id'] ?? 0),
            'label' => (string) ($row['qua_name'] ?? $row['cer_nom'] ?? ''),
            'text' => (string) ($row['qua_name'] ?? $row['cer_nom'] ?? ''),
            'code' => (string) ($row['qua_code'] ?? $row['cer_code'] ?? ''),
            'description' => (string) ($row['qua_description'] ?? $row['cer_description'] ?? ''),
            'scope' => (string) ($row['qua_scope_code'] ?? $row['cer_portee_code'] ?? 'interne'),
            'is_global' => (bool) ($row['qua_is_global'] ?? false),
        ];
    }
}
