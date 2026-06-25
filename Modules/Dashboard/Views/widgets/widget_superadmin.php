<?php
declare(strict_types=1);
// B-05 : fallback défensif — e() est globalement définie par AuthHelper.php
// mais ce guard rend le widget autonome en contexte CLI ou test unitaire.
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** @var array $widget_context */
$stats = is_array($widget_context['dashboard_stats'] ?? null) ? $widget_context['dashboard_stats'] : [];
$summary = is_array($widget_context['company_level_summary'] ?? null) ? $widget_context['company_level_summary'] : [];
?>
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Supervision globale</h3></div>
    <div class="card-body p-0">
        <div class="row no-gutters text-center">
            <div class="col-6 border-right border-bottom p-3"><div class="h3 mb-0 font-weight-bold text-primary"><?= (int)($stats['users_total'] ?? 0) ?></div><small class="text-muted">Utilisateurs</small></div>
            <div class="col-6 border-bottom p-3"><div class="h3 mb-0 font-weight-bold text-info"><?= (int)($stats['companies_total'] ?? 0) ?></div><small class="text-muted">Sociétés</small></div>
            <div class="col-6 border-right p-3"><div class="h3 mb-0 font-weight-bold <?= ((int)($stats['security_blocks_active'] ?? 0) > 0) ? 'text-danger' : 'text-success' ?>"><?= (int)($stats['security_blocks_active'] ?? 0) ?></div><small class="text-muted">Blocages actifs</small></div>
            <div class="col-6 p-3"><div class="h3 mb-0 font-weight-bold <?= ((int)($stats['gdpr_pending'] ?? 0) > 0) ? 'text-warning' : 'text-success' ?>"><?= (int)($stats['gdpr_pending'] ?? 0) ?></div><small class="text-muted">Demandes RGPD</small></div>
        </div>
        <?php if (!empty($summary)): ?>
        <div class="border-top p-2">
            <div class="small text-muted mb-1">Synthèse triée par société puis niveaux</div>
            <?php foreach (array_slice($summary, 0, 4) as $soc): ?>
                <div class="d-flex justify-content-between small">
                    <span><?= e((string)$soc['societe_nom']) ?></span>
                    <span><?= (int)$soc['utilisateurs_total'] ?> utilisateur(s), niveau <?= (int)$soc['niveau_applicatif_max'] ?>/<?= (int)$soc['niveau_competence_max'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($stats['maintenance_active'])): ?>
        <div class="alert alert-warning mb-0 rounded-0 py-2 text-center"><i class="fas fa-tools mr-2"></i><strong>Mode maintenance activé</strong></div>
        <?php endif; ?>
    </div>
</div>
