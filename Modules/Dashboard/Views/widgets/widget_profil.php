<?php
declare(strict_types=1);
/**
 * Widget "Mon profil" — CORRIGÉ B-02
 * Les données utilisateur sont passées via $widget_context (DashboardService::withDashboardData).
 * Aucun appel direct à db() ou Database:: depuis une vue.
 */

// Fallback défensif pour e() si le bootstrap n'est pas encore chargé (ex. tests CLI)
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** @var array $widget_context */
$user      = $widget_context['profil_user'] ?? null;
$roleLabel = implode(', ', array_map('strval', $widget_context['user_roles'] ?? []));

// Fallback : si profil_user non fourni, construire l'affichage depuis les clés de session déjà en contexte
if ($user === null) {
    $user = [
        'pui_civilite'            => '',
        'pui_prenom'              => $widget_context['user_firstname'] ?? '',
        'pui_nom'                 => $widget_context['user_lastname']  ?? '',
        'uti_email'               => $widget_context['user_email']     ?? '',
        'uti_derniere_connexion_le'=> null,
    ];
}

$displayName = trim(
    (string)($user['pui_civilite'] ?? '') . ' ' .
    (string)($user['pui_prenom']   ?? '') . ' ' .
    (string)($user['pui_nom']      ?? '')
);
if ($displayName === '' || $displayName === '  ') {
    $displayName = (string)($user['uti_email'] ?? 'Utilisateur');
}
$initial = strtoupper(substr(trim($displayName), 0, 1)) ?: '?';
?>
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-circle mr-2"></i>Mon profil</h3>
    </div>
    <div class="card-body d-flex align-items-center">
        <span class="d-flex align-items-center justify-content-center bg-secondary rounded-circle mr-3"
              style="width:60px;height:60px;font-size:1.8rem;color:#fff;">
            <?= e($initial) ?>
        </span>
        <div>
            <div class="font-weight-bold" style="font-size:1.05rem;"><?= e($displayName) ?></div>
            <div class="text-muted small"><i class="fas fa-envelope mr-1"></i><?= e((string)($user['uti_email'] ?? '')) ?></div>
            <?php if ($roleLabel !== ''): ?>
                <div class="text-muted small mt-1"><span class="badge badge-info"><?= e($roleLabel) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($user['uti_derniere_connexion_le'])): ?>
                <div class="text-muted small mt-1">
                    <i class="fas fa-clock mr-1"></i>Dernière connexion :
                    <?= e(date('d/m/Y H:i', strtotime((string)$user['uti_derniere_connexion_le']))) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-footer text-right p-2">
        <a href="/profile" class="btn btn-sm btn-outline-info"><i class="fas fa-edit mr-1"></i>Modifier mon profil</a>
    </div>
</div>
