<?php
/**
 * Widget universel : Résumé du profil
 * Visible pour TOUS les rôles
 *
 * @var array $widget_context
 */
$userId = $widget_context['user_id'] ?? null;

$user = $userId ? db()->fetchOne(
    "SELECT use_firstname, use_lastname, use_email, use_photo_url,
            use_last_login_at, use_civility
     FROM sav_users WHERE use_id = :uid",
    [':uid' => $userId]
) : null;

$roleLabel = implode(', ', array_map(fn($r) => match($r) {
    'SUPERADMIN'     => 'Super Administrateur',
    'CONSTRUCTEUR'   => 'Constructeur',
    'IMPORTATEUR'    => 'Importateur',
    'CONCESSIONNAIRE'=> 'Concessionnaire',
    'REPARATEUR'     => 'Réparateur',
    'MANAGER'        => 'Manager',
    'USER'           => 'Utilisateur',
    default          => $r,
}, $widget_context['user_roles'] ?? []));
?>
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-circle mr-2"></i>Mon profil</h3>
    </div>
    <div class="card-body d-flex align-items-center">
        <?php if (!empty($user['use_photo_url'])): ?>
        <img src="<?= htmlspecialchars($user['use_photo_url'], ENT_QUOTES, 'UTF-8') ?>"
             class="img-circle mr-3" width="60" height="60" alt="Photo">
        <?php else: ?>
        <span class="d-flex align-items-center justify-content-center bg-secondary rounded-circle mr-3"
              style="width:60px;height:60px;font-size:1.8rem;color:#fff;">
            <?= strtoupper(substr($user['use_firstname'] ?? 'U', 0, 1)) ?>
        </span>
        <?php endif; ?>
        <div>
            <div class="font-weight-bold" style="font-size:1.05rem;">
                <?= htmlspecialchars(($user['use_civility'] ?? '') . ' ' . ($user['use_firstname'] ?? '') . ' ' . ($user['use_lastname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="text-muted small">
                <i class="fas fa-envelope mr-1"></i>
                <?= htmlspecialchars($user['use_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="text-muted small mt-1">
                <span class="badge badge-info"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php if (!empty($user['use_last_login_at'])): ?>
            <div class="text-muted small mt-1">
                <i class="fas fa-clock mr-1"></i>Dernière connexion :
                <?= htmlspecialchars(
                    date('d/m/Y H:i', strtotime($user['use_last_login_at'])),
                    ENT_QUOTES, 'UTF-8'
                ) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-footer text-right p-2">
        <a href="/profile" class="btn btn-sm btn-outline-info">
            <i class="fas fa-edit mr-1"></i>Modifier mon profil
        </a>
    </div>
</div>
