<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Core\Services\CatalogScopeService;
use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Skills\Models\SkillModel;
use Nenad\Autosav\Modules\Skills\Models\UserSkillModel;
use Nenad\Autosav\Modules\Skills\Services\SkillService;

class SkillsAjaxController extends AjaxController
{
    private SkillService $service;
    private CatalogScopeService $catalogScope;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SkillService(new SkillModel(), new UserSkillModel());
        $this->catalogScope = new CatalogScopeService();
    }

    /** GET /ajax/skills/list?company_id=X */
    public function list(): void
    {
        $companyId = $this->requestedCompanyId();
        $rows = $this->service->getForContext($companyId, false, $this->networkOwnerCompanyIds($companyId));
        AjaxResponseService::success('OK', ['skills' => array_map([$this, 'mapSkill'], $rows)]);
    }

    /** GET /ajax/skills/search?q=X&company_id=X */
    public function search(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $companyId = $this->requestedCompanyId();
        $rows = $q === '' ? [] : $this->service->search($q, $companyId, $this->networkOwnerCompanyIds($companyId));
        $results = array_map([$this, 'mapSkill'], $rows);
        AjaxResponseService::success('OK', ['results' => $results, 'skills' => $results]);
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

    private function mapSkill(array $row): array
    {
        return [
            'id' => (int) ($row['skl_id'] ?? $row['cmp_id'] ?? 0),
            'label' => (string) ($row['skl_name'] ?? $row['cmp_nom'] ?? ''),
            'text' => (string) ($row['skl_name'] ?? $row['cmp_nom'] ?? ''),
            'code' => (string) ($row['skl_code'] ?? $row['cmp_code'] ?? ''),
            'description' => (string) ($row['skl_description'] ?? $row['cmp_description'] ?? ''),
            'scope' => (string) ($row['skl_scope_code'] ?? $row['cmp_portee_code'] ?? 'interne'),
            'is_global' => (bool) ($row['skl_is_global'] ?? false),
        ];
    }
}
