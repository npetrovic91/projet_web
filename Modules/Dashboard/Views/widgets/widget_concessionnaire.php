<?php
declare(strict_types=1);
// B-05 : fallback défensif — e() est globalement définie par AuthHelper.php
// mais ce guard rend le widget autonome en contexte CLI ou test unitaire.
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** @var array $widget_context */
$rows = is_array($widget_context['user_company_levels'] ?? null) ? $widget_context['user_company_levels'] : [];
$nbUsers = count(array_unique(array_map(static fn(array $r): int => (int)$r['utilisateur_id'], $rows)));
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users mr-2"></i>Équipe société <span class="badge badge-primary ml-1"><?= $nbUsers ?></span></h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
        <div class="text-center py-3 text-muted"><i class="fas fa-user-slash fa-2x mb-2 d-block text-light"></i>Aucun utilisateur dans cette structure</div>
        <?php else: ?>
        <ul class="list-unstyled mb-0">
            <?php foreach (array_slice($rows, 0, 5) as $u): ?>
            <li class="d-flex align-items-center px-3 py-2 border-bottom">
                <span class="img-circle d-flex align-items-center justify-content-center bg-secondary mr-2" style="width:32px;height:32px;font-size:1rem;color:#fff;border-radius:50%;"><?= e(strtoupper(substr((string)$u['utilisateur_nom'], 0, 1))) ?></span>
                <div>
                    <div class="small font-weight-bold"><?= e((string)$u['utilisateur_nom']) ?></div>
                    <div class="text-muted" style="font-size:.8rem;"><?= e((string)$u['societe_nom']) ?> — <?= e((string)$u['niveau_applicatif_label']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <div class="card-footer text-right p-2"><a href="/users" class="btn btn-sm btn-outline-primary"><i class="fas fa-users-cog mr-1"></i>Gestion équipe</a></div>
</div>
