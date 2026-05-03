<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Administration\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Modules\Administration\Models\SecurityMonitoringModel;
use Nenad\Autosav\Modules\Auth\Models\IpBlacklistModel;
use Nenad\Autosav\Modules\Auth\Models\EmailBlacklistModel;
use Nenad\Autosav\Modules\Auth\Models\LoginAttemptModel;
use Nenad\Autosav\Modules\Auth\Models\UnblockHistoryModel;
use Nenad\Autosav\Modules\Auth\Services\BlockingService;
use Nenad\Autosav\Modules\Auth\Services\AuthService;

/**
 * Contrôleur d'administration sécurité.
 * Accès réservé : SUPERADMIN et ADMIN_SECURITE uniquement.
 */
class SecurityController extends BaseController
{
    private SecurityMonitoringModel $monitoringModel;
    private BlockingService         $blockingService;
    private LogManager              $logger;

    public function __construct()
    {
        parent::__construct();

        $this->logger = LogManager::getInstance();

        $this->monitoringModel = new SecurityMonitoringModel();
        $this->blockingService = new BlockingService(
            new IpBlacklistModel(),
            new EmailBlacklistModel(),
            new LoginAttemptModel(),
            new UnblockHistoryModel(),
            $this->logger
        );
    }

    /**
     * Dashboard de supervision sécurité.
     *
     * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->requireRole(['SUPERADMIN', 'ADMIN_SECURITE']);

        $filterIp    = $this->getRequest()->get('ip', null);
        $filterEmail = $this->getRequest()->get('email', null);
        $page        = max(1, (int) $this->getRequest()->get('page', 1));
        $perPage     = 30;
        $offset      = ($page - 1) * $perPage;

        $stats         = $this->monitoringModel->getSecurityStats(24);
        $topFailedIps  = $this->monitoringModel->getTopFailedIps(10);
        $attempts      = $this->monitoringModel->getAttempts($perPage, $offset, $filterIp, $filterEmail);
        $totalAttempts = $this->monitoringModel->countAttempts($filterIp, $filterEmail);
        $activeIpBlocks    = $this->blockingService->getActiveIpBlocks(50);
        $activeEmailBlocks = $this->blockingService->getActiveEmailBlocks(50);
        $unblockHistory    = $this->monitoringModel->getUnblockHistory(20);

        $this->render('Administration/Views/security', [
            'pageTitle'        => 'Supervision Sécurité — Autosav',
            'stats'            => $stats,
            'topFailedIps'     => $topFailedIps,
            'attempts'         => $attempts,
            'totalAttempts'    => $totalAttempts,
            'activeIpBlocks'   => $activeIpBlocks,
            'activeEmailBlocks'=> $activeEmailBlocks,
            'unblockHistory'   => $unblockHistory,
            'currentPage'      => $page,
            'perPage'          => $perPage,
            'filterIp'         => $filterIp,
            'filterEmail'      => $filterEmail,
            'csrfToken'        => $this->csrfToken(),
            'flash'            => $this->flash()->all(),
        ]);
    }

    public function attempts(): void
    {
        $this->index();
    }

    /**
     * Débloque une IP.
     *
     * @param int $id ID dans sav_ip_blacklist
     * @return void
     */
    public function unblockIp(int $id): void
    {
        $this->requireAuth();
        $this->requireRole(['SUPERADMIN', 'ADMIN_SECURITE']);

        if (!$this->getRequest()->isPost()) {
            $this->redirect('/admin/security');
            return;
        }

        if (!$this->verifyCsrf($this->getRequest()->post(CSRF_FORM_FIELD))) {
            $this->flash()->error('Erreur de sécurité.');
            $this->redirect('/admin/security');
            return;
        }

        $reason  = trim($this->getRequest()->post('reason', ''));
        $adminId = (int) $_SESSION['user_id'];
        $adminIp = AuthService::detectClientIp();

        if (empty($reason)) {
            $this->flash()->error('La raison du déblocage est obligatoire.');
            $this->redirect('/admin/security');
            return;
        }

        try {
            $this->blockingService->adminUnblockIp($id, $adminId, $adminIp, $reason);
            $this->flash()->success("IP #{$id} débloquée avec succès.");
        } catch (\RuntimeException $e) {
            $this->flash()->error($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->channel('application')->error('admin_unblock_ip_failed', [
                'block_id' => $id,
                'error'    => $e->getMessage(),
            ]);
            $this->flash()->error('Une erreur est survenue lors du déblocage.');
        }

        $this->redirect('/admin/security');
    }

    /**
     * Débloque un email.
     *
     * @param int $id ID dans sav_email_blacklist
     * @return void
     */
    public function unblockEmail(int $id): void
    {
        $this->requireAuth();
        $this->requireRole(['SUPERADMIN', 'ADMIN_SECURITE']);

        if (!$this->getRequest()->isPost()) {
            $this->redirect('/admin/security');
            return;
        }

        if (!$this->verifyCsrf($this->getRequest()->post(CSRF_FORM_FIELD))) {
            $this->flash()->error('Erreur de sécurité.');
            $this->redirect('/admin/security');
            return;
        }

        $reason  = trim($this->getRequest()->post('reason', ''));
        $adminId = (int) $_SESSION['user_id'];
        $adminIp = AuthService::detectClientIp();

        if (empty($reason)) {
            $this->flash()->error('La raison du déblocage est obligatoire.');
            $this->redirect('/admin/security');
            return;
        }

        try {
            $this->blockingService->adminUnblockEmail($id, $adminId, $adminIp, $reason);
            $this->flash()->success("Email #{$id} débloqué avec succès.");
        } catch (\RuntimeException $e) {
            $this->flash()->error($e->getMessage());
        } catch (\Throwable $e) {
            $this->flash()->error('Une erreur est survenue lors du déblocage.');
        }

        $this->redirect('/admin/security');
    }
}

src/Modules/Administration/Views/security.php
