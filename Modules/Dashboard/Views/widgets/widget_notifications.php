<?php
declare(strict_types=1);
// B-05 : fallback défensif — e() est globalement définie par AuthHelper.php
// mais ce guard rend le widget autonome en contexte CLI ou test unitaire.
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** @var array $widget_context */
$notifs = is_array($widget_context['unread_notifications'] ?? null) ? $widget_context['unread_notifications'] : [];
$typeIcon = [
    'info' => 'info-circle text-info',
    'warning' => 'exclamation-triangle text-warning',
    'success' => 'check-circle text-success',
    'error' => 'times-circle text-danger',
    'gdpr' => 'user-shield text-primary',
    'security' => 'shield-alt text-danger',
];
?>
<div class="card card-outline card-warning">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title flex-grow-1"><i class="fas fa-bell mr-2"></i>Notifications</h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($notifs)): ?>
        <div class="text-center py-3 text-muted">
            <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
            Aucune notification non lue
        </div>
        <?php else: ?>
        <ul class="list-unstyled mb-0" id="notif-list">
            <?php foreach ($notifs as $n): ?>
            <li class="d-flex align-items-start border-bottom px-3 py-2" data-notif-id="<?= (int)$n['not_id'] ?>">
                <i class="fas fa-<?= e($typeIcon[$n['not_type']] ?? 'info-circle text-secondary') ?> mt-1 mr-2"></i>
                <div class="flex-grow-1">
                    <div class="font-weight-bold small"><?= e((string)$n['not_titre']) ?></div>
                    <div class="text-muted" style="font-size:.82rem;"><?= e((string)$n['not_message']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
