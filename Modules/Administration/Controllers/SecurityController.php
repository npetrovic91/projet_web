<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Administration\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Administration\Models\SecurityMonitoringModel;

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
    private SecurityMonitoringModel $monitoringModel;

    public function __construct()
    {
        parent::__construct();
        $this->monitoringModel = new SecurityMonitoringModel();
    }

    public function index(): void
    {
        $this->requirePermission('security.read');

        $filterIp = $this->getRequest()->get('ip', null);
        $filterEmail = $this->getRequest()->get('email', null);
        $page = max(1, (int) $this->getRequest()->get('page', 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $this->render('Administration/Views/security', [
            'pageTitle' => 'Supervision sécurité — Autosav',
            'stats' => $this->monitoringModel->getSecurityStats(24),
            'topFailedIps' => $this->monitoringModel->getTopFailedIps(10),
            'attempts' => $this->monitoringModel->getAttempts($perPage, $offset, $filterIp, $filterEmail),
            'totalAttempts' => $this->monitoringModel->countAttempts($filterIp, $filterEmail),
            'activeIpBlocks' => $this->monitoringModel->getActiveIpBlocks(50),
            'activeEmailBlocks' => $this->monitoringModel->getActiveEmailBlocks(50),
            'unblockHistory' => $this->monitoringModel->getUnblockHistory(20),
            'currentPage' => $page,
            'perPage' => $perPage,
            'filterIp' => $filterIp,
            'filterEmail' => $filterEmail,
            'csrfToken' => $this->csrfToken(),
            'flash' => $this->flash()->all(),
        ]);
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
        if ($reason === '') {
            $this->flash()->error('La raison du déblocage est obligatoire.');
            $this->redirect('/admin/security');
        }

        $adminId = $this->userId() ?? 0;
        $adminIp = client_ip();

        try {
            if ($type === 'ip') {
                $this->monitoringModel->unblockIp($id, $adminId, $adminIp, $reason);
                $this->flash()->success("Blocage IP #{$id} levé avec succès.");
            } else {
                $this->monitoringModel->unblockUser($id, $adminId, $adminIp, $reason);
                $this->flash()->success("Compte utilisateur #{$id} déverrouillé avec succès.");
            }
        } catch (\Throwable $e) {
            logger('security')->error('security_unblock_failed', [
                'type' => $type,
                'id' => $id,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);
            $this->flash()->error('Une erreur est survenue lors du déblocage.');
        }

        $this->redirect('/admin/security');
    }
}
