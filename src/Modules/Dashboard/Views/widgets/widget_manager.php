<?php
declare(strict_types=1);

$scopeLabel = !empty($widget_context['manager_scope']) ? 'Service rattache' : 'Perimetre personnel';
?>
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users mr-2"></i>Vue manager</h3>
    </div>
    <div class="card-body">
        <p class="mb-2 text-muted"><?= e($scopeLabel) ?></p>
        <div class="d-flex justify-content-between">
            <span>Entreprise active</span>
            <strong><?= e((string) ($widget_context['active_company_id'] ?? '-')) ?></strong>
        </div>
        <div class="d-flex justify-content-between">
            <span>Marque active</span>
            <strong><?= e((string) ($widget_context['active_brand_id'] ?? '-')) ?></strong>
        </div>
    </div>
</div>
