<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\EventTriggers\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Notifications\Services\NotificationRuleService;

/**
 * CORRECTIF (section 3 du roadmap, encapsulation EventTriggers ->
 * Notifications) : ce contrôleur reconstruisait auparavant lui-même
 * l'intégralité du graphe de dépendances de NotificationRuleService (8
 * modèles d'un AUTRE module), ce qui le rendait silencieusement fragile à
 * tout changement de signature côté Notifications. Le Service est
 * maintenant auto-suffisant (tous ses paramètres ont une valeur par
 * défaut) — ce contrôleur n'a plus besoin de connaître ses dépendances
 * internes.
 */
class EventTriggerController extends BaseController
{
    private NotificationRuleService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new NotificationRuleService();
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
