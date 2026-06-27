<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Administration\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Administration\Services\SecurityMonitoringService;

/**
 * Administration securite alignee sur la base SQL actuelle.
 *
 * Les anciennes listes noires absentes du dump sont remplacees par :
 * - sav_blocages_securite pour les blocages IP ;
 * - sav_utilisateurs pour les comptes verrouilles ;
 * - sav_tentatives_connexion pour le journal des connexions ;
 * - sav_journaux_audit pour l'historique des deblocages.
 */
class SecurityController extends BaseController
{
    private SecurityMonitoringService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SecurityMonitoringService();
    }

    public function index(): void
    {
        $this->requirePermission('security.read');

        $filterIp = $this->getRequest()->get('ip', null);
        $filterEmail = $this->getRequest()->get('email', null);
        $page = max(1, (int) $this->getRequest()->get('page', 1));
        $perPage = 30;

        $data = $this->service->tableauDeBord($filterIp, $filterEmail, $page, $perPage);

        $this->render('Administration/Views/security', array_merge($data, [
            'pageTitle' => 'Supervision sécurité — Autosav',
            'currentPage' => $page,
            'perPage' => $perPage,
            'filterIp' => $filterIp,
            'filterEmail' => $filterEmail,
            'csrfToken' => $this->csrfToken(),
            'flash' => $this->flash()->all(),
        ]));
    }

    public function attempts(): void
    {
        $this->index();
    }

    public function unblockIp(int $id): void
    {
        $this->requirePermission('security.manage');
        $this->handleUnblock($id, 'ip');
    }

    public function unblockEmail(int $id): void
    {
        $this->requirePermission('security.manage');
        $this->handleUnblock($id, 'user');
    }

    private function handleUnblock(int $id, string $type): void
    {
        if (!$this->getRequest()->isPost()) {
            $this->redirect('/admin/security');
        }

        if (!$this->verifyCsrf($this->getRequest()->post(CSRF_FORM_FIELD))) {
            $this->flash()->error('Erreur de sécurité.');
            $this->redirect('/admin/security');
        }

        $reason = trim((string) $this->getRequest()->post('reason', ''));
        $result = $this->service->debloquer($type, $id, $this->userId() ?? 0, client_ip(), $reason);

        $result['success'] ? $this->flash()->success($result['message']) : $this->flash()->error($result['message']);

        $this->redirect('/admin/security');
    }
}
