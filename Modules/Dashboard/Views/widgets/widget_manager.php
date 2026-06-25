<?php
declare(strict_types=1);
// B-05 : fallback défensif — e() est globalement définie par AuthHelper.php
// mais ce guard rend le widget autonome en contexte CLI ou test unitaire.
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** @var array $widget_context */
$scopeLabel = !empty($widget_context['manager_scope']) ? 'Service / équipe rattachée' : 'Périmètre personnel';
$rows = is_array($widget_context['user_company_levels'] ?? null) ? $widget_context['user_company_levels'] : [];
?>
<div class="card card-outline card-info">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-users mr-2"></i>Vue manager</h3></div>
    <div class="card-body">
        <p class="mb-2 text-muted"><?= e($scopeLabel) ?></p>
        <div class="d-flex justify-content-between"><span>Entreprise active</span><strong><?= e((string)($widget_context['active_company_id'] ?? '-')) ?></strong></div>
        <div class="d-flex justify-content-between"><span>Marque active</span><strong><?= e((string)($widget_context['active_brand_id'] ?? '-')) ?></strong></div>
        <div class="d-flex justify-content-between"><span>Affectations visibles</span><strong><?= count($rows) ?></strong></div>
        <div class="small text-muted mt-2">Les listes sont triées par utilisateur, société d’appartenance puis niveaux.</div>
    </div>
</div>
