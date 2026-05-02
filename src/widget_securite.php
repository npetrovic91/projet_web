<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Users\Models\UserCompanyHistoryModel;
use Nenad\Autosav\Modules\Users\Models\UserCompanyModel;
use Nenad\Autosav\Modules\Users\Models\UserHierarchyModel;
use Nenad\Autosav\Modules\Users\Models\UserModel;
use Nenad\Autosav\Modules\Users\Models\UserRoleModel;
use Nenad\Autosav\Modules\Users\Services\UserCompanyService;
use Nenad\Autosav\Modules\Users\Services\UserHierarchyService;
use Nenad\Autosav\Modules\Users\Services\UserService;

class UsersAjaxController extends AjaxController
{
    private UserService $users;
    private UserCompanyService $companies;
    private UserHierarchyService $hierarchy;
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();

        $this->userModel = new UserModel();
        $roleModel = new UserRoleModel();
        $companyModel = new UserCompanyModel();
        $historyModel = new UserCompanyHistoryModel();

        $this->users = new UserService($this->userModel, $roleModel, $companyModel, $historyModel);
        $this->companies = new UserCompanyService($companyModel, $historyModel, $this->userModel);
        $this->hierarchy = new UserHierarchyService(new UserHierarchyModel());
    }

    public function search(): void
    {
        $query = trim((string) $this->request->get('q', ''));
        if (strlen($query) < 2) {
            AjaxResponseService::success('OK', ['users' => []]);
        }

        $result = $this->users->getFilteredUsers(
            ['search' => $query, 'is_active' => 1],
            (int) $this->user['id'],
            1,
            (int) $this->request->get('limit', 10)
        );

        $users = array_map(static fn(array $user): array => [
            'id' => (int) $user['use_id'],
            'text' => trim($user['use_firstname'] . ' ' . $user['use_lastname']) . ' - ' . $user['use_email'],
            'email' => $user['use_email'],
            'role' => $user['primary_role_label'] ?? null,
        ], $result['users']);

        AjaxResponseService::success('OK', ['users' => $users]);
    }

    public function getUserCompanies(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        AjaxResponseService::success('OK', ['companies' => $this->companies->getUserCompanies($targetId)]);
    }

    public function getUserManagers(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        AjaxResponseService::success('OK', ['managers' => $this->hierarchy->getManagers($targetId)]);
    }

    public function getSubordinates(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $companyId = $this->request->get('company_id') ? (int) $this->request->get('company_id') : null;
        AjaxResponseService::success('OK', ['subordinates' => $this->hierarchy->getSubordinates($targetId, $companyId)]);
    }

    public function getCreatableRoles(): void
    {
        AjaxResponseService::success('OK', ['roles' => $this->users->getCreatableRoles((int) $this->user['id'])]);
    }

    public function getManageableCompanies(): void
    {
        AjaxResponseService::success('OK', ['companies' => $this->users->getManageableCompanies((int) $this->user['id'])]);
    }

    public function attachCompany(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $data = $this->request->json();

        $result = $this->companies->attachUserToCompany(
            $targetId,
            (int) ($data['company_id'] ?? 0),
            (bool) ($data['is_primary'] ?? false),
            (string) ($data['joined_at'] ?? date('Y-m-d')),
            (string) ($data['job_title'] ?? ''),
            (int) $this->user['id']
        );

        $this->sendMutationResult($result, ['companies' => $this->companies->getUserCompanies($targetId)]);
    }

    public function detachCompany(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $data = $this->request->json();

        $result = $this->companies->detachUserFromCompany(
            $targetId,
            (int) ($data['company_id'] ?? 0),
            (string) ($data['reason'] ?? 'Retrait manuel'),
            (int) $this->user['id']
        );

        $this->sendMutationResult($result, ['companies' => $this->companies->getUserCompanies($targetId)]);
    }

    public function addManager(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $data = $this->request->json();

        $result = $this->hierarchy->addManager(
            $targetId,
            (int) ($data['manager_id'] ?? 0),
            isset($data['company_id']) && $data['company_id'] !== '' ? (int) $data['company_id'] : null,
            (bool) ($data['is_primary'] ?? false),
            (int) $this->user['id']
        );

        $this->sendMutationResult($result, ['managers' => $this->hierarchy->getManagers($targetId)]);
    }

    public function removeManager(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $data = $this->request->json();

        $result = $this->hierarchy->removeManager(
            $targetId,
            (int) ($data['manager_id'] ?? 0),
            (int) $this->user['id']
        );

        $this->sendMutationResult($result, ['managers' => $this->hierarchy->getManagers($targetId)]);
    }

    public function setPrimaryCompany(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $data = $this->request->json();

        $result = $this->companies->setPrimaryCompany(
            $targetId,
            (int) ($data['company_id'] ?? 0),
            (int) $this->user['id']
        );

        $this->sendMutationResult($result);
    }

    private function assertCanManage(int $targetId): void
    {
        if (!$this->users->canManageUser((int) $this->user['id'], $targetId)) {
            AjaxResponseService::forbidden('Acces refuse.');
        }
    }

    private function sendMutationResult(array $result, array $data = []): void
    {
        if (!($result['success'] ?? false)) {
            AjaxResponseService::validationError([], (string) ($result['message'] ?? 'Action impossible.'));
        }

        AjaxResponseService::success((string) ($result['message'] ?? 'OK'), $data);
    }
}
