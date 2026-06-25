<?php
declare(strict_types=1);
/** Widget : Alertes sécurité */
$stats = is_array($widget_context['dashboard_stats'] ?? null) ? $widget_context['dashboard_stats'] : [];
$blocked = (int)($stats['security_blocks_active'] ?? 0);
$attempts = (int)($stats['failed_logins_24h'] ?? 0);
?>
<div class="card bg-danger">
    <div class="card-body d-flex justify-content-between align-items-center py-3">
        <div>
            <div class="text-white" style="font-size:2rem;font-weight:700"><?= $blocked ?></div>
            <div class="text-white-50">Blocages actifs</div>
            <small class="text-white-50"><?= $attempts ?> tentatives / 24h</small>
        </div>
        <i class="fas fa-shield-alt fa-2x text-white-50"></i>
    </div>
</div>
