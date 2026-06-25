<?php
declare(strict_types=1);
/** Widget : Nombre de marques représentées */
$stats = is_array($widget_context['dashboard_stats'] ?? null) ? $widget_context['dashboard_stats'] : [];
$count = (int)($stats['brands_total'] ?? 0);
?>
<div class="card bg-warning">
    <div class="card-body d-flex justify-content-between align-items-center py-3">
        <div><div class="text-white" style="font-size:2rem;font-weight:700"><?= $count ?></div><div class="text-white-50">Marques représentées</div></div>
        <i class="fas fa-tag fa-2x text-white-50"></i>
    </div>
</div>
