<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Core\Services\CatalogScopeService;
use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Functions\Models\FunctionModel;
use Nenad\Autosav\Modules\Functions\Models\UserFunctionModel;
use Nenad\Autosav\Modules\Functions\Services\FunctionService;

class FunctionsAjaxController extends AjaxController
{
    private FunctionService $service;
    private CatalogScopeService $catalogScope;

    public function __construct()
    {
        parent::__construct();
        $this->service = new FunctionService(new FunctionModel(), new UserFunctionModel());
        $this->catalogScope = new CatalogScopeService();
    }

    public function list(): void
    {
        $companyId = $this->requestedCompanyId();
        AjaxResponseService::success('Fonctions chargees.', [
            'functions' => $this->service->getForContext($companyId, false, $this->networkOwnerCompanyIds($companyId))
        ]);
    }

    public function search(): void
    {
        $q = trim((string) $this->request->get('q', ''));
        $companyId = $this->requestedCompanyId();
        $items = $q === '' ? [] : $this->service->search($q, $companyId, $this->networkOwnerCompanyIds($companyId));
        AjaxResponseService::success('Recherche effectuee.', [
            'results' => array_map(static fn(array $row): array => [
                'id' => (int) $row['fnc_id'],
                'text' => $row['fnc_label'],
                'code' => $row['fnc_code'],
                'scope' => $row['fnc_scope_code'] ?? $row['fon_portee_code'] ?? 'interne',
                'is_global' => (bool) $row['fnc_is_global'],
            ], $items),
        ]);
    }

    public function forUser(string $id): void
    {
        AjaxResponseService::success('Fonctions utilisateur chargees.', [
            'functions' => $this->service->getUserFunctions((int) $id)
        ]);
    }

    public function assignToUser(string $id): void
    {
        $data = $this->request->json();
        $result = $this->service->assignToUser(
            (int) $id,
            (int) ($data['function_id'] ?? 0),
            isset($data['company_id']) && $data['company_id'] !== '' ? (int) $data['company_id'] : null,
            (bool) ($data['is_primary'] ?? false),
            (int) $this->user['id']
        );
        $this->sendResult($result);
    }

    public function unassignFromUser(string $id): void
    {
        $data = $this->request->json();
        $this->sendResult($this->service->unassignFromUser((int) $id, (int) ($data['function_id'] ?? 0)));
    }

    public function syncForUser(string $id): void
    {
        $data = $this->request->json();
        $this->sendResult($this->service->syncUserFunctions(
            (int) $id,
            (array) ($data['function_ids'] ?? []),
            (int) ($data['primary_function_id'] ?? 0),
            (int) $this->user['id']
        ));
    }

    /**
     * CORRECTIF (section 3 du roadmap, "company_id accepté en GET sur
     * certains contrôleurs Ajax... à nettoyer pour éviter toute
     * énumération") : un company_id arbitraire fourni par le client
     * permettait de filtrer sur N'IMPORTE QUELLE société, y compris une
     * dont l'utilisateur n'est pas membre — le middleware tenant bloque
     * l'accès aux données métier sensibles, mais ce catalogue de fonctions
     * inclut aussi les entrées spécifiques à une société (pas seulement
     * globales), ce qui pouvait révéler leur existence/nom à un tiers.
     * Plus aucun override possible depuis la requête : uniquement la
     * société active de la session de l'utilisateur courant.
     */
    private function requestedCompanyId(): ?int
    {
        $companyId = $this->user['active_company_id'] ?? null;
        return $companyId ? (int) $companyId : null;
    }

    private function networkOwnerCompanyIds(?int $companyId): array
    {
        return $companyId ? $this->catalogScope->networkOwnerCompanyIds($companyId) : [];
    }

    private function sendResult(array $result): void
    {
        if (!($result['success'] ?? false)) {
            AjaxResponseService::validationError($result['errors'] ?? [], $result['errors']['general'] ?? 'Action impossible.');
        }
        AjaxResponseService::success('Action realisee.');
    }
}
