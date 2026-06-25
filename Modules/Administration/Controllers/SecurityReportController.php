<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Administration\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Pdf\TcpdfReportService;
use Nenad\Autosav\Modules\Administration\Models\SecurityMonitoringModel;

final class SecurityReportController extends BaseController
{
    public function pdf(): void
    {
        $this->requirePermission('security.read');

        $monitoring = new SecurityMonitoringModel();
        $pdf = (new TcpdfReportService())->renderSecurityReport(
            $monitoring->getSecurityStats(24),
            $monitoring->getTopFailedIps(10),
            $monitoring->getActiveIpBlocks(50),
            $monitoring->getActiveEmailBlocks(50)
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
