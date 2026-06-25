<?php defined('AUTOSAV_ROOT') or die; ?>
<?php $layout = 'layouts/admin'; ob_start(); ?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-envelope-open-text mr-2 text-primary"></i>Emails en masse</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="/bulk-mail/create" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>Nouvelle campagne
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

<!-- Cartes résumé -->
<?php
$totalCampaigns = count($campaigns ?? []);
$totalSent      = array_sum(array_column($campaigns ?? [], 'ecp_sent_count'));
$totalRecip     = array_sum(array_column($campaigns ?? [], 'ecp_total'));
?>
<div class="row mb-3">
    <div class="col-md-3">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-bullhorn"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Campagnes</span>
                <span class="info-box-number"><?= $totalCampaigns ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-paper-plane"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Emails envoyés</span>
                <span class="info-box-number"><?= number_format($totalSent) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Destinataires totaux</span>
                <span class="info-box-number"><?= number_format($totalRecip) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Tableau campagnes -->
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-1"></i>Campagnes</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Sujet</th>
                    <th>Type</th>
                    <th>Ciblage</th>
                    <th>Statut</th>
                    <th>Destinataires</th>
                    <th>Envoyés</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($campaigns)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Aucune campagne</td></tr>
            <?php else: ?>
            <?php foreach ($campaigns as $c): ?>
            <?php
                $typeBadge = match($c['ecp_type']) {
                    'notification' => '<span class="badge badge-warning">Notification</span>',
                    'newsletter'   => '<span class="badge badge-info">Newsletter</span>',
                    'campaign'     => '<span class="badge badge-primary">Campagne</span>',
                    default        => '<span class="badge badge-secondary">'.$c['ecp_type'].'</span>',
                };
                $statusBadge = match($c['ecp_status']) {
                    'draft'     => '<span class="badge badge-secondary">Brouillon</span>',
                    'sending'   => '<span class="badge badge-warning">En cours</span>',
                    'sent'      => '<span class="badge badge-success">Envoyée</span>',
                    'paused'    => '<span class="badge badge-info">En pause</span>',
                    'cancelled' => '<span class="badge badge-danger">Annulée</span>',
                    default     => '<span class="badge badge-light">'.$c['ecp_status'].'</span>',
                };
                $targetLabel = match($c['ecp_target']) {
                    'all'                 => '<i class="fas fa-globe text-info"></i> Tous',
                    'by_role'             => '<i class="fas fa-user-tag text-primary"></i> '.(htmlspecialchars($c['role_label']??'Rôle')),
                    'by_company'          => '<i class="fas fa-building text-success"></i> '.(htmlspecialchars($c['company_name']??'Entreprise')),
                    'by_role_and_company' => '<i class="fas fa-filter text-warning"></i> Rôle + Entreprise',
                    default               => htmlspecialchars($c['ecp_target']),
                };
                $pct = $c['ecp_total'] > 0 ? round($c['ecp_sent_count'] / $c['ecp_total'] * 100) : 0;
            ?>
                <tr>
                    <td><a href="/bulk-mail/<?= $c['ecp_id'] ?>"><strong><?= htmlspecialchars($c['ecp_subject']) ?></strong></a></td>
                    <td><?= $typeBadge ?></td>
                    <td><?= $targetLabel ?></td>
                    <td><?= $statusBadge ?></td>
                    <td><?= number_format($c['ecp_total']) ?></td>
                    <td>
                        <div class="progress" style="height:14px">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%"><?= $pct ?>%</div>
                        </div>
                        <small><?= $c['ecp_sent_count'] ?>/<?= $c['ecp_total'] ?></small>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($c['ecp_created_at'])) ?></td>
                    <td>
                        <a href="/bulk-mail/<?= $c['ecp_id'] ?>" class="btn btn-xs btn-info" title="Voir">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</section>
<?php $content = ob_get_clean(); include AUTOSAV_ROOT.'/src/layouts/admin.php'; ?>