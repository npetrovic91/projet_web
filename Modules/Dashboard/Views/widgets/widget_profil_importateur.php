<?php
declare(strict_types=1);
/**
 * Widget "Profil Importateur"
 * Visible uniquement quand la société active est de type 'importateur'.
 * Données alimentées par DashboardStatsModel::importateurStats().
 */
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** @var array $widget_context */
$stats     = $widget_context['importateur_stats'] ?? [];
$societe   = $widget_context['society']['soc_nom'] ?? ($widget_context['user_name'] ?? 'Importateur');
$companyId = (int) ($widget_context['active_company_id'] ?? 0);
?>
<div class="card card-outline card-warning">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title flex-grow-1">
            <i class="fas fa-shipping-fast mr-2 text-warning"></i>
            <strong><?= e($societe) ?></strong>
            <span class="badge badge-warning ml-2">Importateur</span>
        </h3>
    </div>

    <div class="card-body p-0">
        <div class="row no-gutters text-center">

            <!-- Collaborateurs -->
            <div class="col-6 col-md-3 border-right border-bottom p-3">
                <div class="display-4 font-weight-bold text-warning">
                    <?= (int)($stats['utilisateurs_total'] ?? 0) ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-users mr-1"></i>Collaborateurs
                </div>
            </div>

            <!-- Marques importées -->
            <div class="col-6 col-md-3 border-right border-bottom p-3">
                <div class="display-4 font-weight-bold text-danger">
                    <?= (int)($stats['marques_importees'] ?? 0) ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-tag mr-1"></i>Marques importées
                </div>
            </div>

            <!-- Points de vente du réseau -->
            <div class="col-6 col-md-3 border-right border-bottom p-3">
                <div class="display-4 font-weight-bold text-primary">
                    <?= (int)($stats['concessions_reseau'] ?? 0) ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-store mr-1"></i>Réseau distribution
                </div>
            </div>

            <!-- Représentations actives -->
            <div class="col-6 col-md-3 border-bottom p-3">
                <div class="display-4 font-weight-bold text-success">
                    <?= (int)($stats['representations_total'] ?? 0) ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-handshake mr-1"></i>Représentations
                </div>
            </div>
        </div>

        <!-- Activité réseau récente -->
        <?php
        $rows    = is_array($widget_context['user_company_levels'] ?? null)
            ? $widget_context['user_company_levels'] : [];
        $visible = array_slice($rows, 0, 5);
        ?>
        <?php if (!empty($visible)): ?>
        <div class="px-3 pt-2 pb-1">
            <p class="text-muted small mb-1"><strong>Utilisateurs actifs — réseau</strong></p>
        </div>
        <ul class="list-group list-group-flush">
            <?php foreach ($visible as $u): ?>
            <li class="list-group-item d-flex align-items-center py-2">
                <span class="img-circle d-flex align-items-center justify-content-center bg-warning mr-2"
                      style="width:30px;height:30px;font-size:.9rem;color:#fff;border-radius:50%;flex-shrink:0;">
                    <?= e(strtoupper(substr((string)$u['utilisateur_nom'], 0, 1))) ?>
                </span>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="small font-weight-bold text-truncate"><?= e($u['utilisateur_nom']) ?></div>
                    <div class="text-muted" style="font-size:.8rem;"><?= e($u['societe_nom']) ?></div>
                </div>
                <span class="badge badge-light ml-2"><?= e($u['niveau_applicatif_label']) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="text-center py-3 text-muted small">
            <i class="fas fa-info-circle mr-1"></i>Aucun utilisateur dans le réseau de distribution.
        </div>
        <?php endif; ?>
    </div>

    <div class="card-footer d-flex justify-content-between p-2">
        <a href="/companies" class="btn btn-sm btn-outline-warning"><i class="fas fa-network-wired mr-1"></i>Réseau</a>
        <a href="/brands"    class="btn btn-sm btn-outline-danger"><i class="fas fa-tag mr-1"></i>Marques</a>
        <a href="/users"     class="btn btn-sm btn-outline-secondary"><i class="fas fa-users mr-1"></i>Équipe</a>
    </div>
</div>
