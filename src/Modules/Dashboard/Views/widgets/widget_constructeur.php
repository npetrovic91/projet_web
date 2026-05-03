<?php
/**
 * Widget Constructeur : Vue réseau global
 * Rôles : SUPERADMIN, CONSTRUCTEUR
 * Async (via AJAX)
 *
 * @var array $widget_context
 */

// Si dans un contexte entreprise constructeur → filtre sur ce constructeur
$companyId     = $widget_context['active_company_id'] ?? null;

$nbImportateurs = (int) db()->fetchColumn(
    "SELECT COUNT(*) FROM sav_company_relations cr
     INNER JOIN sav_companies c ON cr.crl_child_id = c.com_id
     INNER JOIN sav_company_types ct ON c.com_type_id = ct.ctp_id
     WHERE cr.crl_parent_id = :cid AND ct.ctp_code = 'IMPORTATEUR' AND cr.crl_is_active = 1",
    [':cid' => $companyId ?? 0]
);

$nbConcessions = (int) db()->fetchColumn(
    "SELECT COUNT(DISTINCT c2.com_id)
     FROM sav_company_relations r1
     INNER JOIN sav_company_relations r2 ON r1.crl_child_id = r2.crl_parent_id
     INNER JOIN sav_companies c2 ON r2.crl_child_id = c2.com_id
     WHERE r1.crl_parent_id = :cid AND r1.crl_is_active = 1 AND r2.crl_is_active = 1",
    [':cid' => $companyId ?? 0]
);
?>
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-network-wired mr-2"></i>Mon réseau
        </h3>
    </div>
    <div class="card-body row text-center no-gutters">
        <div class="col-6 border-right py-3">
            <div class="h2 mb-0 font-weight-bold text-success"><?= $nbImportateurs ?></div>
            <small class="text-muted">Importateurs</small>
        </div>
        <div class="col-6 py-3">
            <div class="h2 mb-0 font-weight-bold text-primary"><?= $nbConcessions ?></div>
            <small class="text-muted">Concessions</small>
        </div>
    </div>
    <div class="card-footer text-right p-2">
        <a href="/companies" class="btn btn-sm btn-outline-success">
            <i class="fas fa-list mr-1"></i>Voir le réseau
        </a>
    </div>
</div>
