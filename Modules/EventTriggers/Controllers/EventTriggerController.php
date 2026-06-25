<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\EventTriggers\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Notifications\Models\EventTriggerModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationAuditModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationChannelModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationContactModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationPreferenceModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationRuleModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationTemplateModel;
use Nenad\Autosav\Modules\Notifications\Services\NotificationRuleService;

class EventTriggerController extends BaseController
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
            new NotificationAuditModel(),
            new NotificationChannelModel(),
            new NotificationTemplateModel(),
            new NotificationPreferenceModel()
        );
    }

    public function index(): void
    {
        $this->requirePermission('eventtriggers.read');
        $companyId = $this->activeCompanyId();
        if (!$companyId) {
            $this->flash('error', 'Sélectionnez une société active.');
            $this->redirect('/dashboard');
        }

        $data = $this->service->dashboard($companyId);
        $this->render('EventTriggers/index', [
            'events' => $data['events'],
            'rules' => $data['rules'],
            'company_id' => $companyId,
            'page_title' => 'Événements applicatifs',
        ]);
    }
}
