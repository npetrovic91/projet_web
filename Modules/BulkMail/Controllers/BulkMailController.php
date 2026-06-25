<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\BulkMail\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\BulkMail\Services\BulkMailService;
use Nenad\Autosav\Modules\Emails\Models\EmailModel;

class BulkMailController extends BaseController
{
    private BulkMailService $svc;
    private EmailModel $emails;

    public function __construct()
    {
        parent::__construct();
        $this->svc = new BulkMailService();
        $this->emails = new EmailModel();
    }

    public function index(): void
    {
        $this->requirePermission('notifications.manage');
        $this->render('BulkMail/Views/index', [
            'campaigns' => $this->svc->listerCampagnesDepuisJournaux(),
            'page_title' => 'Emails groupés',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('notifications.manage');
        $data = $this->svc->preparerFormulaire();
        $data['page_title'] = 'Nouvel email groupé';
        $data['csrf_token'] = $this->csrfToken();
        $this->render('BulkMail/Views/create', $data);
    }

    public function store(): void
    {
        $this->requirePermission('notifications.manage');
        $this->validateCsrf();
        $operatorId = (int)($this->getCurrentUser()['use_id'] ?? 0);
        $result = $this->svc->envoyerGroupe($_POST, $operatorId);

        if (!$result['success']) {
            $this->flash()->error($result['message'] ?? 'Erreur lors de l’envoi groupé.');
            $this->redirect('/bulk-mail/create');
        }

        $this->flash()->success($result['message'] ?? 'Envoi groupé terminé.');
        $this->redirect('/bulk-mail');
    }

    public function show(string $code): void
    {
        $this->requirePermission('notifications.manage');
        $journaux = $this->svc->listerJournauxCampagne($code, 500);
        $this->render('BulkMail/Views/show', [
            'code' => $code,
            'journaux' => $journaux,
            'page_title' => 'Détail email groupé',
        ]);
    }

    public function sendBatch(string $id): void
    {
        $this->requirePermission('notifications.manage');
        $this->json(false, [
            'code' => $id,
            'mode' => 'journalise',
        ], 'Aucune table de file/campagne email n’existe dans le SQL actuel. L’envoi groupé est réalisé directement puis journalisé dans sav_journaux_emails.', 409);
    }

    public function refresh(string $id): void
    {
        $this->requirePermission('notifications.manage');
        $this->validateCsrf();
        $this->flash()->info('Les destinataires sont résolus au moment de l’envoi avec la base SQL actuelle.');
        $this->redirect('/bulk-mail');
    }

    public function cancel(string $id): void
    {
        $this->requirePermission('notifications.manage');
        $this->validateCsrf();
        $this->flash()->info('Aucune campagne persistante à annuler dans le schéma SQL actuel.');
        $this->redirect('/bulk-mail');
    }

    public function unsubscribe(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        $ok = $token !== '' && $this->svc->desabonner($token);
        $this->render('BulkMail/Views/unsubscribed', [
            'page_title' => $ok ? 'Désabonnement confirmé' : 'Lien invalide',
            'success' => $ok,
        ], 'public');
    }
}
