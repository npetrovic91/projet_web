<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\GDPR\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\GDPR\Models\GdprActionModel;
use Nenad\Autosav\Modules\GDPR\Models\GdprExportModel;
use Nenad\Autosav\Modules\GDPR\Models\GdprRequestModel;
use Nenad\Autosav\Modules\GDPR\Services\GdprService;
use Nenad\Autosav\Modules\Users\Models\UserModel;

class GdprController extends BaseController
{
    private GdprService $gdpr;

    public function __construct()
    {
        parent::__construct();
        $this->gdpr = new GdprService(new GdprRequestModel(), new GdprActionModel(), new GdprExportModel(), new UserModel());
    }

    public function index(): void
    {
        $this->requirePermission('gdpr.read');
        $filters = [
            'status' => trim((string) $this->request->get('status', '')),
            'type' => trim((string) $this->request->get('type', '')),
        ];
        $this->render('GDPR/index', [
            'requests' => $this->gdpr->listRequests($filters),
            'actions' => $this->gdpr->latestActions(),
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'page_title' => 'Tableau RGPD',
        ]);
    }

    public function show(string $id): void
    {
        $this->requirePermission('gdpr.read');
        $request = $this->gdpr->findRequest((int) $id);
        if (!$request) {
            $this->flash('error', 'Demande RGPD introuvable.');
            $this->redirect('/gdpr');
        }
        $this->render('GDPR/show', [
            'request' => $request,
            'csrf_token' => $this->csrfToken(),
            'page_title' => 'Demande RGPD',
        ]);
    }

    public function accept(string $id): void
    {
        $this->requirePermission('gdpr.process');
        $this->validateCsrf();
        $this->gdpr->acceptRequest((int) $id, (int) $this->getCurrentUser()['use_id'], client_ip(), (string) $this->request->post('response', 'Demande acceptee.'));
        $this->flash('success', 'Demande RGPD acceptee.');
        $this->redirect('/gdpr/' . (int) $id);
    }

    public function reject(string $id): void
    {
        $this->requirePermission('gdpr.process');
        $this->validateCsrf();
        $this->gdpr->rejectRequest((int) $id, (int) $this->getCurrentUser()['use_id'], client_ip(), (string) $this->request->post('reason', 'Motif non precise.'));
        $this->flash('success', 'Demande RGPD rejetee.');
        $this->redirect('/gdpr/' . (int) $id);
    }

    public function export(string $id): never
    {
        $this->requirePermission('gdpr.export');
        $request = $this->gdpr->findRequest((int) $id);
        if (!$request) {
            http_response_code(404);
            exit('Demande introuvable.');
        }
        $payload = $this->gdpr->exportUserData((int) $request['grq_user_id'], (int) $request['grq_id'], (int) $this->getCurrentUser()['use_id'], client_ip());
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="gdpr-request-' . (int) $id . '.json"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function anonymize(string $id): void
    {
        $this->requirePermission('gdpr.anonymize');
        $this->validateCsrf();
        $request = $this->gdpr->findRequest((int) $id);
        if (!$request) {
            $this->flash('error', 'Demande RGPD introuvable.');
            $this->redirect('/gdpr');
        }
        $this->gdpr->anonymizeUser((int) $request['grq_user_id'], (int) $this->getCurrentUser()['use_id'], client_ip(), (string) $this->request->post('reason', 'Anonymisation RGPD'));
        $this->flash('success', 'Utilisateur anonymise.');
        $this->redirect('/gdpr/' . (int) $id);
    }
}
