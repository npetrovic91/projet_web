<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Notifications\Models\EventTriggerModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationAuditModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationContactModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationRuleModel;
use Nenad\Autosav\Modules\Notifications\Services\NotificationRuleService;

class NotificationRuleController extends BaseController
{
    private NotificationRuleService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new NotificationRuleService(
            new EventTriggerModel(),
            new NotificationContactModel(),
            new NotificationRuleModel(),
            new NotificationModel(),
            new NotificationAuditModel()
        );
    }

    public function index(): void
    {
        $this->requirePermission('notifications.read');
        $companyId = $this->activeCompanyId();
        if (!$companyId) {
            $this->flash('error', 'Selectionnez une entreprise active.');
            $this->redirect('/dashboard');
        }
        $data = $this->service->dashboard($companyId);
        $this->render('Notifications/index', [
            'events' => $data['events'],
            'contacts' => $data['contacts'],
            'rules' => $data['rules'],
            'company_id' => $companyId,
            'csrf_token' => $this->csrfToken(),
            'page_title' => 'Contacts et notifications',
        ]);
    }

    public function storeContact(): void
    {
        $this->requirePermission('notifications.manage');
        $this->validateCsrf();
        $data = $this->request->all();
        $data['company_id'] = $data['company_id'] ?? $this->activeCompanyId();
        $result = $this->service->createContact($data, (int) $this->getCurrentUser()['use_id'], client_ip());
        $this->flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Contact cree.' : implode(' ', $result['errors']));
        $this->redirect('/notifications/rules');
    }

    public function storeRule(): void
    {
        $this->requirePermission('notifications.manage');
        $this->validateCsrf();
        $data = $this->request->all();
        $data['company_id'] = $data['company_id'] ?? $this->activeCompanyId();
        $data['channels'] = $data['channels'] ?? ['email'];
        $result = $this->service->createRule($data, (int) $this->getCurrentUser()['use_id'], client_ip());
        $this->flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Regle creee.' : implode(' ', $result['errors']));
        $this->redirect('/notifications/rules');
    }

    public function toggleContact(string $id): void
    {
        $this->requirePermission('notifications.manage');
        $this->validateCsrf();
        $active = (bool) $this->request->post('active', 0);
        $this->service->toggleContact((int) $id, $active, (int) $this->getCurrentUser()['use_id'], client_ip());
        $this->redirect('/notifications/rules');
    }

    public function toggleRule(string $id): void
    {
        $this->requirePermission('notifications.manage');
        $this->validateCsrf();
        $active = (bool) $this->request->post('active', 0);
        $this->service->toggleRule((int) $id, $active, (int) $this->getCurrentUser()['use_id'], client_ip());
        $this->redirect('/notifications/rules');
    }
}
