<?php
declare(strict_types=1);
// B-05 : fallback défensif — e() est globalement définie par AuthHelper.php
// mais ce guard rend le widget autonome en contexte CLI ou test unitaire.
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** @var array $widget_context */
$recentAttempts = is_array($widget_context['recent_security_attempts'] ?? null) ? $widget_context['recent_security_attempts'] : [];
$stats = is_array($widget_context['dashboard_stats'] ?? null) ? $widget_context['dashboard_stats'] : [];
?>
<div class="card card-outline card-danger">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Sécurité — Tentatives récentes</h3>
    </div>
    <div class="card-body p-0">
        <div class="row no-gutters text-center border-bottom">
            <div class="col-6 p-2"><strong><?= (int)($stats['failed_logins_24h'] ?? 0) ?></strong><br><small>Échecs / 24h</small></div>
            <div class="col-6 p-2"><strong><?= (int)($stats['security_blocks_active'] ?? 0) ?></strong><br><small>Blocages actifs</small></div>
        </div>
        <table class="table table-sm table-hover mb-0">
            <tbody>
            <?php foreach ($recentAttempts as $a): ?>
            <tr>
                <td class="py-1"><?= !empty($a['tcn_succes']) ? '<span class="badge badge-success"><i class="fas fa-check"></i></span>' : '<span class="badge badge-danger"><i class="fas fa-times"></i></span>' ?></td>
                <td class="py-1 small font-weight-bold"><?= e((string)($a['adresse_ip'] ?? '')) ?></td>
                <td class="py-1 small text-muted"><?= e((string)($a['tcn_email_normalise'] ?? $a['tcn_email_tente'] ?? '')) ?></td>
                <td class="py-1 small text-muted"><?= !empty($a['tcn_cree_le']) ? e(date('d/m H:i', strtotime((string)$a['tcn_cree_le']))) : '' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentAttempts)): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">Aucune tentative récente</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
