<?php
declare(strict_types=1);
/**
 * Widget "Profil Constructeur"
 * Visible uniquement quand la société active est de type 'constructeur'.
 * Données alimentées par DashboardStatsModel::constructeurStats().
 */
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** @var array $widget_context */
$stats     = $widget_context['constructeur_stats'] ?? [];
$societe   = $widget_context['society']['soc_nom']  ?? ($widget_context['user_name'] ?? 'Constructeur');
$companyId = (int) ($widget_context['active_company_id'] ?? 0);
?>
<div class="card card-outline card-danger">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title flex-grow-1">
            <i class="fas fa-industry mr-2 text-danger"></i>
            <strong><?= e($societe) ?></strong>
            <span class="badge badge-danger ml-2">Constructeur</span>
        </h3>
    </div>

    <div class="card-body p-0">
        <div class="row no-gutters text-center">

            <!-- Collaborateurs -->
            <div class="col-6 col-md-3 border-right border-bottom p-3">
                <div class="display-4 font-weight-bold text-danger">
                    <?= (int)($stats['utilisateurs_total'] ?? 0) ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-users mr-1"></i>Collaborateurs
                </div>
            </div>

            <!-- Marques -->
            <div class="col-6 col-md-3 border-right border-bottom p-3">
                <div class="display-4 font-weight-bold text-warning">
                    <?= (int)($stats['marques_total'] ?? 0) ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-tag mr-1"></i>Marques
                </div>
            </div>

            <!-- Concessions réseau -->
            <div class="col-6 col-md-3 border-right border-bottom p-3">
                <div class="display-4 font-weight-bold text-primary">
                    <?= (int)($stats['concessions_total'] ?? 0) ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-store mr-1"></i>Points de vente
                </div>
            </div>

            <!-- Représentations actives -->
            <div class="col-6 col-md-3 border-bottom p-3">
                <div class="display-4 font-weight-bold text-success">
                    <?= (int)($stats['representations_total'] ?? 0) ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-handshake mr-1"></i>Représentations actives
                </div>
            </div>
        </div>

        <!-- Résumé réseau -->
        <?php
        $summary = is_array($widget_context['company_level_summary'] ?? null)
            ? $widget_context['company_level_summary'] : [];
        ?>
        <?php if (!empty($summary)): ?>
        <div class="px-3 pt-2 pb-1">
            <p class="text-muted small mb-1"><strong>Top sociétés du réseau</strong></p>
        </div>
        <ul class="list-group list-group-flush">
            <?php foreach (array_slice($summary, 0, 4) as $soc): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span class="small"><?= e($soc['societe_nom']) ?></span>
                <span>
                    <span class="badge badge-primary badge-pill"><?= (int)$soc['utilisateurs_total'] ?> util.</span>
                    <span class="badge badge-secondary badge-pill ml-1">Niv.<?= (int)$soc['niveau_applicatif_max'] ?>/<?= (int)$soc['niveau_competence_max'] ?></span>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <div class="card-footer d-flex justify-content-between p-2">
        <a href="/companies" class="btn btn-sm btn-outline-danger"><i class="fas fa-network-wired mr-1"></i>Réseau</a>
        <a href="/users"     class="btn btn-sm btn-outline-secondary"><i class="fas fa-users mr-1"></i>Équipe</a>
        <a href="/brands"    class="btn btn-sm btn-outline-warning"><i class="fas fa-tag mr-1"></i>Marques</a>
    </div>
</div>
