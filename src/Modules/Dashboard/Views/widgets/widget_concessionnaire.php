<?php
/**
 * Widget Concessionnaire : Équipe de la concession active
 * Rôles : SUPERADMIN, CONSTRUCTEUR, IMPORTATEUR, CONCESSIONNAIRE, MANAGER
 * Sync
 *
 * @var array $widget_context
 */
$companyId  = $widget_context['active_company_id'] ?? null;
$nbUsers    = 0;
$recentUsers = [];

if ($companyId) {
    $nbUsers = (int) db()->fetchColumn(
        "SELECT COUNT(*) FROM sav_user_companies uc
         INNER JOIN sav_users u ON uc.ucm_user_id = u.use_id
         WHERE uc.ucm_company_id = :cid AND uc.ucm_is_active = 1
           AND u.use_is_active = 1 AND u.use_deleted_at IS NULL",
        [':cid' => $companyId]
    );
    $recentUsers = db()->fetchAll(
        "SELECT u.use_firstname, u.use_lastname, u.use_email, u.use_photo_url
         FROM sav_user_companies uc
         INNER JOIN sav_users u ON uc.ucm_user_id = u.use_id
         WHERE uc.ucm_company_id = :cid AND uc.ucm_is_active = 1
           AND u.use_is_active = 1 AND u.use_deleted_at IS NULL
         ORDER BY uc.ucm_created_at DESC LIMIT 5",
        [':cid' => $companyId]
    );
}
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users mr-2"></i>Équipe
            <span class="badge badge-primary ml-1"><?= $nbUsers ?></span>
        </h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentUsers)): ?>
        <div class="text-center py-3 text-muted">
            <i class="fas fa-user-slash fa-2x mb-2 d-block text-light"></i>
            Aucun utilisateur dans cette structure
        </div>
        <?php else: ?>
        <ul class="list-unstyled mb-0">
            <?php foreach ($recentUsers as $u): ?>
            <li class="d-flex align-items-center px-3 py-2 border-bottom">
                <span class="img-circle d-flex align-items-center justify-content-center bg-secondary mr-2"
                      style="width:32px;height:32px;font-size:1rem;color:#fff;border-radius:50%;">
                    <?= strtoupper(substr($u['use_firstname'] ?? 'U', 0, 1)) ?>
                </span>
                <div>
                    <div class="small font-weight-bold">
                        <?= htmlspecialchars($u['use_firstname'] . ' ' . $u['use_lastname'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="text-muted" style="font-size:.8rem;">
                        <?= htmlspecialchars($u['use_email'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <div class="card-footer text-right p-2">
        <a href="/users" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-users-cog mr-1"></i>Gestion équipe
        </a>
    </div>
</div>
