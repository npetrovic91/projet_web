<?php
/**
 * Widget Sécurité : Événements récents (SuperAdmin uniquement)
 * Async
 *
 * @var array $widget_context
 */
$recentAttempts = db()->fetchAll(
    "SELECT lat_ip, lat_email, lat_success, lat_failure_reason, lat_created_at
     FROM sav_login_attempts
     ORDER BY lat_created_at DESC LIMIT 8"
);
?>
<div class="card card-outline card-danger">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-shield-alt mr-2"></i>Sécurité — Tentatives récentes
        </h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <tbody>
            <?php foreach ($recentAttempts as $a): ?>
            <tr>
                <td class="py-1">
                    <?php if ($a['lat_success']): ?>
                    <span class="badge badge-success"><i class="fas fa-check"></i></span>
                    <?php else: ?>
                    <span class="badge badge-danger"><i class="fas fa-times"></i></span>
                    <?php endif; ?>
                </td>
                <td class="py-1 small font-weight-bold">
                    <?= htmlspecialchars($a['lat_ip'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td class="py-1 small text-muted">
                    <?= htmlspecialchars($a['lat_email'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td class="py-1 small text-muted">
                    <?= htmlspecialchars(
                        date('d/m H:i', strtotime($a['lat_created_at'])),
                        ENT_QUOTES, 'UTF-8'
                    ) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-right p-2">
        <a href="/admin/security" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-eye mr-1"></i>Supervision complète
        </a>
    </div>
</div>
