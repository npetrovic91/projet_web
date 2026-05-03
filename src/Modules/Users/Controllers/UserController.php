<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Users\Models\UserCompanyHistoryModel;
use Nenad\Autosav\Modules\Users\Models\UserCompanyModel;
use Nenad\Autosav\Modules\Users\Models\UserHierarchyModel;
use Nenad\Autosav\Modules\Users\Models\UserModel;
use Nenad\Autosav\Modules\Users\Models\UserRoleModel;
use Nenad\Autosav\Modules\Users\Services\UserCompanyService;
use Nenad\Autosav\Modules\Users\Services\UserHierarchyService;
use Nenad\Autosav\Modules\Users\Services\UserService;

class UserController extends BaseController
{
    private UserService $users;
    private UserCompanyService $companies;
    private UserHierarchyService $hierarchy;

    public function __construct()
    {
        parent::__construct();
        $userModel = new UserModel();
        $roleModel = new UserRoleModel();
        $companyModel = new UserCompanyModel();
        $historyModel = new UserCompanyHistoryModel();

        $this->users = new UserService($userModel, $roleModel, $companyModel, $historyModel);
        $this->companies = new UserCompanyService($companyModel, $historyModel, $userModel);
        $this->hierarchy = new UserHierarchyService(new UserHierarchyModel());
    }

    public function index(): void
    {
        $this->requirePermission('users.read');
        $currentUser = $this->getCurrentUser();
        $page = max(1, (int) $this->request->get('page', 1));
        $filters = array_filter([
            'search' => trim((string) $this->request->get('search', '')),
            'role_code' => trim((string) $this->request->get('role_code', '')),
            'company_id' => trim((string) $this->request->get('company_id', '')),
            'is_active' => trim((string) $this->request->get('is_active', '')),
        ], static fn($value): bool => $value !== '');

        $result = $this->users->getFilteredUsers($filters, (int) $currentUser['use_id'], $page, 20);

        $this->render('Users/index', [
            'users' => $result['users'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $result['page'],
            'filters' => $filters,
            'roles' => $this->users->getCreatableRoles((int) $currentUser['use_id']),
            'companies' => $this->users->getManageableCompanies((int) $currentUser['use_id']),
            'csrf_token' => $this->csrfToken(),
            'page_title' => 'Gestion des utilisateurs',
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('users.create');
        $currentUser = $this->getCurrentUser();

        $this->render('Users/create', [
            'roles' => $this->users->getCreatableRoles((int) $currentUser['use_id']),
            'companies' => $this->users->getManageableCompanies((int) $currentUser['use_id']),
            'csrf_token' => $this->csrfToken(),
            'old' => [],
            'errors' => [],
            'mode' => 'create',
            'page_title' => 'Creer un utilisateur',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('users.create');
        $this->validateCsrf();

        $currentUser = $this->getCurrentUser();
        $data = $this->request->all();
        $result = $this->users->createUser($data, (int) $currentUser['use_id']);

        if ($result['success']) {
            $this->flash('success', $result['message']);
            $this->redirect('/users/' . $result['user_id']);
        }

        $this->render('Users/create', [
            'roles' => $this->users->getCreatableRoles((int) $currentUser['use_id']),
            'companies' => $this->users->getManageableCompanies((int) $currentUser['use_id']),
            'csrf_token' => $this->csrfToken(),
            'old' => $data,
            'errors' => $result['errors'] ?? [],
            'mode' => 'create',
            'page_title' => 'Creer un utilisateur',
        ], 'main', 422);
    }

    public function show(string $id): void
    {
        $userId = (int) $id;
        $this->requirePermission('users.read');
        $currentUser = $this->getCurrentUser();

        if (!$this->users->canManageUser((int) $currentUser['use_id'], $userId)) {
            $this->flash('error', 'Acces refuse.');
            $this->redirect('/users');
        }

        $user = $this->users->getFullProfile($userId);
        if (!$user) {
            $this->flash('error', 'Utilisateur introuvable.');
            $this->redirect('/users');
        }

        $this->render('Users/show', [
            'user' => $user,
            'managers' => $this->hierarchy->getManagers($userId),
            'subordinates' => $this->hierarchy->getSubordinates($userId),
            'history' => $this->companies->getUserHistory($userId),
            'csrf_token' => $this->csrfToken(),
            'page_title' => $user['use_firstname'] . ' ' . $user['use_lastname'],
        ]);
    }

    public function edit(string $id): void
    {
        $userId = (int) $id;
        $this->requirePermission('users.update');
        $currentUser = $this->getCurrentUser();

        if (!$this->users->canManageUser((int) $currentUser['use_id'], $userId)) {
            $this->flash('error', 'Acces refuse.');
            $this->redirect('/users');
        }

        $user = $this->users->getFullProfile($userId);
        if (!$user) {
            $this->flash('error', 'Utilisateur introuvable.');
            $this->redirect('/users');
        }

        $this->render('Users/edit', [
            'user' => $user,
            'csrf_token' => $this->csrfToken(),
            'old' => [],
            'errors' => [],
            'page_title' => 'Modifier utilisateur',
        ]);
    }

    public function update(string $id): void
    {
        $userId = (int) $id;
        $this->requirePermission('users.update');
        $this->validateCsrf();
        $currentUser = $this->getCurrentUser();

        $posted = $this->request->all();
        $result = $this->users->updateUser($userId, $posted, (int) $currentUser['use_id']);
        if ($result['success']) {
            $this->flash('success', $result['message']);
            $this->redirect('/users/' . $userId);
        }

        $this->render('Users/edit', [
            'user' => $this->users->getFullProfile($userId),
            'csrf_token' => $this->csrfToken(),
            'old' => $posted,
            'errors' => $result['errors'] ?? [],
            'page_title' => 'Modifier utilisateur',
        ], 'main', 422);
    }

    public function deactivate(string $id): void
    {
        $this->requirePermission('users.update');
        $this->validateCsrf();
        $result = $this->users->deactivateUser((int) $id, (int) $this->getCurrentUser()['use_id']);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/users/' . (int) $id);
    }

    public function reactivate(string $id): void
    {
        $this->requirePermission('users.update');
        $this->validateCsrf();
        $result = $this->users->reactivateUser((int) $id, (int) $this->getCurrentUser()['use_id']);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/users/' . (int) $id);
    }

    public function history(string $id): void
    {
        $userId = (int) $id;
        $this->requirePermission('users.read');
        $currentUser = $this->getCurrentUser();
        if (!$this->users->canManageUser((int) $currentUser['use_id'], $userId)) {
            $this->flash('error', 'Acces refuse.');
            $this->redirect('/users');
        }
        $this->render('Users/history', [
            'user' => $this->users->getFullProfile($userId),
            'history' => $this->companies->getUserHistory($userId),
            'page_title' => 'Historique professionnel',
        ]);
    }
}
