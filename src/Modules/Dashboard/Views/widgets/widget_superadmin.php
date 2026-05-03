<?php
/**
 * Widget SuperAdmin : Statistiques globales de l'application
 * Variables disponibles :
 * @var array $widget_context  Contexte utilisateur
 * @var array $widget_config   Config perso utilisateur
 *
 * Ce widget est synchrone (pas d'ajax_endpoint en base).
 * Les compteurs sont calculés à l'affichage (page chargée).
 */
$total_users    = (int) db()->fetchColumn("SELECT COUNT(*) FROM sav_users WHERE use_deleted_at IS NULL");
$total_companies= (int) db()->fetchColumn("SELECT COUNT(*) FROM sav_companies WHERE com_deleted_at IS NULL AND com_is_active=1");
$blocked_ips    = (int) db()->fetchColumn("SELECT COUNT(*) FROM sav_ip_blacklist WHERE ibl_is_active=1");
$blocked_emails = (int) db()->fetchColumn("SELECT COUNT(*) FROM sav_email_blacklist WHERE ebl_is_active=1");
$pending_gdpr   = (int) db()->fetchColumn("SELECT COUNT(*) FROM sav_gdpr_requests WHERE gdr_status='pending'");
$maint_active   = (int) db()->fetchColumn("SELECT mtn_is_active FROM sav_maintenance WHERE mtn_id=1");
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-shield-alt mr-2"></i>Supervision globale
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="row no-gutters text-center">
            <div class="col-6 border-right border-bottom p-3">
                <div class="h3 mb-0 font-weight-bold text-primary"><?= $total_users ?></div>
                <small class="text-muted">Utilisateurs actifs</small>
            </div>
            <div class="col-6 border-bottom p-3">
                <div class="h3 mb-0 font-weight-bold text-info"><?= $total_companies ?></div>
                <small class="text-muted">Entreprises</small>
            </div>
            <div class="col-6 border-right p-3">
                <div class="h3 mb-0 font-weight-bold <?= ($blocked_ips + $blocked_emails > 0) ? 'text-danger' : 'text-success' ?>">
                    <?= $blocked_ips + $blocked_emails ?>
                </div>
                <small class="text-muted">Blocages actifs</small>
            </div>
            <div class="col-6 p-3">
                <div class="h3 mb-0 font-weight-bold <?= $pending_gdpr > 0 ? 'text-warning' : 'text-success' ?>">
                    <?= $pending_gdpr ?>
                </div>
                <small class="text-muted">Demandes RGPD</small>
            </div>
        </div>
        <?php if ($maint_active): ?>
        <div class="alert alert-warning mb-0 rounded-0 py-2 text-center">
            <i class="fas fa-tools mr-2"></i>
            <strong>Mode maintenance activé</strong>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-footer text-right p-2">
        <a href="/admin" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-cog mr-1"></i>Administration
        </a>
    </div>
</div>
