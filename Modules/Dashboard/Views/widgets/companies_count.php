<?php
declare(strict_types=1);
/** Widget : Nombre de sociétés */
$stats = is_array($widget_context['dashboard_stats'] ?? null) ? $widget_context['dashboard_stats'] : [];
$count = (int)($stats['companies_total'] ?? 0);
?>
<div class="card bg-success">
    <div class="card-body d-flex justify-content-between align-items-center py-3">
        <div><div class="text-white" style="font-size:2rem;font-weight:700"><?= $count ?></div><div class="text-white-50">Sociétés</div></div>
        <i class="fas fa-building fa-2x text-white-50"></i>
    </div>
</div>
