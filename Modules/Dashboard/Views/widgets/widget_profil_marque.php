<?php
declare(strict_types=1);
/**
 * Widget "Profil Marque"
 * Visible uniquement quand la société active est de type 'marque'.
 * Données alimentées par DashboardStatsModel::marqueStats().
 */
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** @var array $widget_context */
$stats     = $widget_context['marque_stats'] ?? [];
$societe   = $widget_context['society']['soc_nom'] ?? ($widget_context['user_name'] ?? 'Marque');
$companyId = (int) ($widget_context['active_company_id'] ?? 0);

// Taux de représentations principales
$reprTotal     = (int)($stats['representations_total'] ?? 0);
$reprPrincipale = (int)($stats['principale_count']      ?? 0);
$txPrincipale  = $reprTotal > 0 ? round(($reprPrincipale / $reprTotal) * 100) : 0;
?>
<div class="card card-outline card-info">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title flex-grow-1">
            <i class="fas fa-star mr-2 text-info"></i>
            <strong><?= e($societe) ?></strong>
            <span class="badge badge-info ml-2">Marque</span>
        </h3>
    </div>

    <div class="card-body p-0">
        <div class="row no-gutters text-center">

            <!-- Collaborateurs -->
            <div class="col-6 col-md-3 border-right border-bottom p-3">
                <div class="display-4 font-weight-bold text-secondary">
                    <?= (int)($stats['utilisateurs_total'] ?? 0) ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-users mr-1"></i>Collaborateurs
                </div>
            </div>

            <!-- Points de vente -->
            <div class="col-6 col-md-3 border-right border-bottom p-3">
                <div class="display-4 font-weight-bold text-info">
                    <?= (int)($stats['concessions_total'] ?? 0) ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-store mr-1"></i>Points de vente
                </div>
            </div>

            <!-- Représentations actives -->
            <div class="col-6 col-md-3 border-right border-bottom p-3">
                <div class="display-4 font-weight-bold text-success">
                    <?= $reprTotal ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-handshake mr-1"></i>Représentations
                </div>
            </div>

            <!-- Marque principale -->
            <div class="col-6 col-md-3 border-bottom p-3">
                <div class="display-4 font-weight-bold text-warning">
                    <?= $reprPrincipale ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="fas fa-award mr-1"></i>Marque principale
                </div>
            </div>
        </div>

        <!-- Barre de taux marque principale -->
        <?php if ($reprTotal > 0): ?>
        <div class="px-3 pt-3 pb-2">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span><i class="fas fa-award mr-1 text-warning"></i>Représentations où cette marque est principale</span>
                <strong><?= $txPrincipale ?>&nbsp;%</strong>
            </div>
            <div class="progress" style="height:8px;">
                <div class="progress-bar bg-warning" role="progressbar"
                     style="width:<?= $txPrincipale ?>%"
                     aria-valuenow="<?= $txPrincipale ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top points de vente par ce réseau -->
        <?php
        $summary = is_array($widget_context['company_level_summary'] ?? null)
            ? $widget_context['company_level_summary'] : [];
        ?>
        <?php if (!empty($summary)): ?>
        <div class="px-3 pt-2 pb-1">
            <p class="text-muted small mb-1"><strong>Réseau de distribution</strong></p>
        </div>
        <ul class="list-group list-group-flush">
            <?php foreach (array_slice($summary, 0, 4) as $soc): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span class="small"><?= e($soc['societe_nom']) ?></span>
                <span>
                    <span class="badge badge-info badge-pill"><?= (int)$soc['utilisateurs_total'] ?> util.</span>
                    <?php if ((int)$soc['certifications_total'] > 0): ?>
                    <span class="badge badge-success badge-pill ml-1">
                        <i class="fas fa-certificate"></i> <?= (int)$soc['certifications_total'] ?>
                    </span>
                    <?php endif; ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="text-center py-3 text-muted small">
            <i class="fas fa-info-circle mr-1"></i>Aucun point de vente lié à cette marque pour le moment.
        </div>
        <?php endif; ?>
    </div>

    <div class="card-footer d-flex justify-content-between p-2">
        <a href="/companies" class="btn btn-sm btn-outline-info"><i class="fas fa-store mr-1"></i>Points de vente</a>
        <a href="/users"     class="btn btn-sm btn-outline-secondary"><i class="fas fa-users mr-1"></i>Équipe</a>
    </div>
</div>
