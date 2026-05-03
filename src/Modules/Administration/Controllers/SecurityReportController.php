<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Administration\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Pdf\TcpdfReportService;
use Nenad\Autosav\Modules\Administration\Models\SecurityMonitoringModel;
use Nenad\Autosav\Modules\Auth\Models\EmailBlacklistModel;
use Nenad\Autosav\Modules\Auth\Models\IpBlacklistModel;
use Nenad\Autosav\Modules\Auth\Models\LoginAttemptModel;
use Nenad\Autosav\Modules\Auth\Models\UnblockHistoryModel;
use Nenad\Autosav\Modules\Auth\Services\BlockingService;
use Nenad\Autosav\Core\Logger\LogManager;

final class SecurityReportController extends BaseController
{
    public function pdf(): void
    {
        $this->requireAuth();
        $this->requireRole(['SUPERADMIN', 'ADMIN_SECURITE']);

        $monitoring = new SecurityMonitoringModel();
        $blocking = new BlockingService(
            new IpBlacklistModel(),
            new EmailBlacklistModel(),
            new LoginAttemptModel(),
            new UnblockHistoryModel(),
            LogManager::getInstance()
        );

        $pdf = (new TcpdfReportService())->renderSecurityReport(
            $monitoring->getSecurityStats(24),
            $monitoring->getTopFailedIps(10),
            $blocking->getActiveIpBlocks(50),
            $blocking->getActiveEmailBlocks(50)
        );

        logger('security')->info('security_pdf_report_generated', [
            'admin_id' => $this->userId(),
            'ip' => client_ip(),
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="autosav-rapport-securite.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}
