<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Functions\Models\FunctionModel;
use Nenad\Autosav\Modules\Functions\Models\UserFunctionModel;
use Nenad\Autosav\Modules\Functions\Services\FunctionService;

class FunctionsAjaxController extends AjaxController
{
    private FunctionService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new FunctionService(new FunctionModel(), new UserFunctionModel());
    }

    public function list(): void
    {
        $companyId = $this->request->get('company_id') ? (int) $this->request->get('company_id') : ($this->user['active_company_id'] ?? null);
        AjaxResponseService::success('Fonctions chargees.', [
            'functions' => $this->service->getForContext($companyId ? (int) $companyId : null)
        ]);
    }

    public function search(): void
    {
        $q = trim((string) $this->request->get('q', ''));
        $companyId = $this->request->get('company_id') ? (int) $this->request->get('company_id') : ($this->user['active_company_id'] ?? null);
        $items = $q === '' ? [] : $this->service->search($q, $companyId ? (int) $companyId : null);
        AjaxResponseService::success('Recherche effectuee.', [
            'results' => array_map(static fn(array $row): array => [
                'id' => (int) $row['fnc_id'],
                'text' => $row['fnc_label'],
                'code' => $row['fnc_code'],
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

    private function sendResult(array $result): void
    {
        if (!($result['success'] ?? false)) {
            AjaxResponseService::validationError($result['errors'] ?? [], $result['errors']['general'] ?? 'Action impossible.');
        }
        AjaxResponseService::success('Action realisee.');
    }
}
