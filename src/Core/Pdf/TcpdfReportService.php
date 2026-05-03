<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Pdf;

final class TcpdfReportService
{
    public function renderSecurityReport(array $stats, array $topFailedIps, array $activeIpBlocks, array $activeEmailBlocks): string
    {
        $this->loadTcpdf();

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('AutoSAV');
        $pdf->SetAuthor('AutoSAV');
        $pdf->SetTitle('Rapport de supervision securite');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();

        $html = $this->buildSecurityHtml($stats, $topFailedIps, $activeIpBlocks, $activeEmailBlocks);
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('autosav-rapport-securite.pdf', 'S');
    }

    private function loadTcpdf(): void
    {
        if (class_exists(\TCPDF::class)) {
            return;
        }

        $tcpdfPath = ROOT_PATH . '/vendor/tecnickcom/tcpdf/tcpdf.php';
        if (!is_file($tcpdfPath)) {
            throw new \RuntimeException('TCPDF local est introuvable dans vendor/tecnickcom/tcpdf.');
        }

        require_once $tcpdfPath;
    }

    private function buildSecurityHtml(array $stats, array $topFailedIps, array $activeIpBlocks, array $activeEmailBlocks): string
    {
        $generatedAt = date('d/m/Y H:i:s');
        $html = '<h1>AutoSAV - Rapport de supervision securite</h1>';
        $html .= '<p><strong>Generation :</strong> ' . $this->e($generatedAt) . '</p>';
        $html .= '<h2>Synthese 24h</h2>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><td>Tentatives totales</td><td>' . (int) ($stats['total_attempts'] ?? 0) . '</td></tr>';
        $html .= '<tr><td>Connexions reussies</td><td>' . (int) ($stats['success_attempts'] ?? 0) . '</td></tr>';
        $html .= '<tr><td>Echecs</td><td>' . (int) ($stats['failed_attempts'] ?? 0) . '</td></tr>';
        $html .= '<tr><td>IP bloquees actives</td><td>' . (int) ($stats['active_ip_blocks'] ?? 0) . '</td></tr>';
        $html .= '<tr><td>Emails bloques actifs</td><td>' . (int) ($stats['active_email_blocks'] ?? 0) . '</td></tr>';
        $html .= '</table>';

        $html .= '<h2>Top IP en echec</h2>';
        $html .= '<table border="1" cellpadding="4"><tr><th>IP</th><th>Echecs</th><th>Derniere tentative</th></tr>';
        foreach ($topFailedIps as $row) {
            $html .= '<tr><td>' . $this->e((string) ($row['lat_ip'] ?? '')) . '</td><td>' . (int) ($row['failure_count'] ?? 0) . '</td><td>' . $this->e((string) ($row['last_attempt'] ?? '')) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Blocages actifs</h2>';
        $html .= '<table border="1" cellpadding="4"><tr><th>Type</th><th>Cible</th><th>Raison</th><th>Expiration</th></tr>';
        foreach ($activeIpBlocks as $row) {
            $html .= '<tr><td>IP</td><td>' . $this->e((string) ($row['ibl_ip'] ?? '')) . '</td><td>' . $this->e((string) ($row['ibl_reason'] ?? '')) . '</td><td>' . $this->e((string) ($row['ibl_expires_at'] ?? '')) . '</td></tr>';
        }
        foreach ($activeEmailBlocks as $row) {
            $html .= '<tr><td>Email</td><td>' . $this->e((string) ($row['ebl_email'] ?? '')) . '</td><td>' . $this->e((string) ($row['ebl_reason'] ?? '')) . '</td><td>' . $this->e((string) ($row['ebl_expires_at'] ?? '')) . '</td></tr>';
        }
        $html .= '</table>';

        return $html;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
