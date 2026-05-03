<?php
/**
 * Widget universel : Notifications non lues
 *
 * @var array $widget_context
 */
$userId = $widget_context['user_id'] ?? null;
$notifs = $userId ? db()->fetchAll(
    "SELECT ntf_id, ntf_type, ntf_title, ntf_message, ntf_link, ntf_created_at
     FROM sav_notifications
     WHERE ntf_user_id = :uid AND ntf_is_read = 0
       AND (ntf_expires_at IS NULL OR ntf_expires_at > NOW())
     ORDER BY ntf_created_at DESC LIMIT 5",
    [':uid' => $userId]
) : [];

$typeIcon = ['info' => 'info-circle text-info', 'warning' => 'exclamation-triangle text-warning',
             'success' => 'check-circle text-success', 'error' => 'times-circle text-danger',
             'gdpr' => 'user-shield text-primary', 'security' => 'shield-alt text-danger'];
?>
<div class="card card-outline card-warning">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title flex-grow-1">
            <i class="fas fa-bell mr-2"></i>Notifications
        </h3>
        <?php if (count($notifs) > 0): ?>
        <button class="btn btn-xs btn-outline-secondary ml-auto js-mark-all-read" title="Tout marquer comme lu">
            <i class="fas fa-check-double"></i>
        </button>
        <?php endif; ?>
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
            <li class="d-flex align-items-start border-bottom px-3 py-2"
                data-notif-id="<?= (int) $n['ntf_id'] ?>">
                <i class="fas fa-<?= $typeIcon[$n['ntf_type']] ?? 'info-circle text-secondary' ?> mt-1 mr-2"></i>
                <div class="flex-grow-1">
                    <div class="font-weight-bold small">
                        <?= htmlspecialchars($n['ntf_title'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="text-muted" style="font-size:.82rem;">
                        <?= htmlspecialchars($n['ntf_message'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
                <button class="btn btn-xs btn-link text-muted ml-2 js-notif-read"
                        data-id="<?= (int) $n['ntf_id'] ?>" title="Marquer comme lu">
                    <i class="fas fa-times"></i>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
