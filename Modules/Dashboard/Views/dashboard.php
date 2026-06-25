<?php
declare(strict_types=1);

$companies = is_array($companies ?? null) ? $companies : [];
$brands = is_array($brands ?? null) ? $brands : [];
$sync_widgets = is_array($sync_widgets ?? null) ? $sync_widgets : [];
$async_widgets = is_array($async_widgets ?? null) ? $async_widgets : [];
$context = is_array($context ?? null) ? $context : [];
$society = $_SESSION['society'] ?? [];
$user = $_SESSION['user'] ?? [];
$stats = is_array($context['dashboard_stats'] ?? null) ? $context['dashboard_stats'] : [];
$sortRule = (string)($context['dashboard_sort_rule'] ?? 'utilisateur > société_appartenance > niveaux');

$nomSociete = (string)($society['soc_nom'] ?? $society['name'] ?? $society['nom'] ?? '');
$marquesSociete = (string)($society['marques_label'] ?? '');
$roleLabel = !empty($user['role_names']) && is_array($user['role_names'])
    ? implode(', ', $user['role_names'])
    : (string)($user['role_label'] ?? implode(', ', (array)($context['user_roles'] ?? [])));
?>

<?php if (!empty($terms_pending)): ?>
<div class="alert alert-warning">
    <i class="fas fa-file-contract mr-1"></i>
    Vous devez accepter les conditions d’utilisation pour continuer à utiliser l’application.
</div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-8">
        <div class="card card-outline card-primary mb-0">
            <div class="card-body py-3">
                <h5 class="mb-1">
                    <i class="fas fa-store mr-2"></i>
                    <?= e($nomSociete !== '' ? $nomSociete : 'Aucune société active') ?>
                </h5>
                <?php if ($marquesSociete !== ''): ?>
                    <div class="text-muted"><i class="fas fa-tags mr-1"></i><?= e($marquesSociete) ?></div>
                <?php endif; ?>
                <?php if ($roleLabel !== ''): ?>
                    <div class="text-muted"><i class="fas fa-user-shield mr-1"></i><?= e($roleLabel) ?></div>
                <?php endif; ?>
                <div class="small text-muted mt-1">
                    <i class="fas fa-sort-alpha-down mr-1"></i>
                    Tri statistiques : <?= e($sortRule) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-secondary mb-0">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Contexte actif</div>
        
                <div>Société : <strong><?= e((string)($active_company_id ?: '—')) ?></strong></div>
                <div>Marque : <strong><?= e((string)($active_brand_id ?: 'Toutes')) ?></strong></div>
                <div>Portée : <strong><?= !empty($context['global_scope']) ? 'Globale' : 'Société active' ?></strong></div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-3 col-6 mb-2">
        <div class="small-box bg-info mb-0">
            <div class="inner"><h3><?= (int)($stats['users_total'] ?? 0) ?></h3><p>Utilisateurs</p></div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <div class="small-box bg-success mb-0">
            <div class="inner"><h3><?= (int)($stats['companies_total'] ?? 0) ?></h3><p>Sociétés</p></div>
            <div class="icon"><i class="fas fa-building"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <div class="small-box bg-warning mb-0">
            <div class="inner"><h3><?= (int)($stats['brands_total'] ?? 0) ?></h3><p>Marques représentées</p></div>
            <div class="icon"><i class="fas fa-tags"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <div class="small-box bg-danger mb-0">
            <div class="inner"><h3><?= (int)($stats['failed_logins_24h'] ?? 0) ?></h3><p>Échecs connexion / 24h</p></div>
            <div class="icon"><i class="fas fa-shield-alt"></i></div>
        </div>
    </div>
</div>

<?php if (!empty($companies) || !empty($brands)): ?>
<div class="row mb-3">
    <?php if (!empty($companies)): ?>
    <div class="col-md-6">
        <label for="active-company-select" class="form-label">Société active</label>
        <select id="active-company-select" class="form-control" data-current="<?= e((string)($active_company_id ?? '')) ?>">
            <?php foreach ($companies as $company): ?>
                <?php
                $id = (int)($company['soc_id'] ?? $company['id'] ?? $company['com_id'] ?? 0);
                $label = (string)($company['soc_nom'] ?? $company['name'] ?? $company['nom'] ?? $company['com_name'] ?? ('Société #' . $id));
                ?>
                <option value="<?= $id ?>" <?= ($id === (int)$active_company_id) ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if (!empty($brands)): ?>
    <div class="col-md-6">
        <label for="active-brand-select" class="form-label">Marque active</label>
        <select id="active-brand-select" class="form-control" data-current="<?= e((string)($active_brand_id ?? '')) ?>">
            <option value="">Toutes les marques</option>
            <?php foreach ($brands as $brand): ?>
                <?php
                $id = (int)($brand['soc_id'] ?? $brand['id'] ?? $brand['brd_id'] ?? 0);
                $label = (string)($brand['soc_nom'] ?? $brand['name'] ?? $brand['nom'] ?? $brand['brd_name'] ?? ('Marque #' . $id));
                ?>
                <option value="<?= $id ?>" <?= ($id === (int)$active_brand_id) ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row" id="dashboard-widgets">
    <?php foreach ($sync_widgets as $widget): ?>
        <?php $widgetClass = in_array((string)$widget['code'], ['user_company_levels'], true) ? 'col-lg-8 col-md-12' : 'col-lg-4 col-md-6'; ?>
        <div class="<?= $widgetClass ?> mb-3" data-widget-code="<?= e((string)$widget['code']) ?>">
            <?= $widget['rendered_html'] ?>
        </div>
    <?php endforeach; ?>

    <?php foreach ($async_widgets as $widget): ?>
        <?php $widgetClass = in_array((string)$widget['code'], ['user_company_levels'], true) ? 'col-lg-8 col-md-12' : 'col-lg-4 col-md-6'; ?>
        <div class="<?= $widgetClass ?> mb-3 dashboard-widget-async" data-widget-code="<?= e((string)$widget['code']) ?>">
            <div class="card">
                <div class="card-body text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Chargement...
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($sync_widgets) && empty($async_widgets)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle mr-1"></i>
    Aucun widget n’est configuré pour votre profil.
</div>
<?php endif; ?>

<script<?= $nonceAttr ?? '' ?>>
window.AutoSAVDashboardConfig = {
    csrfToken: <?= json_encode((string)($csrf_token ?? '')) ?>,
    asyncWidgets: <?= json_encode(array_column($async_widgets, 'code')) ?>
};
</script>
<script<?= $nonceAttr ?? '' ?> src="<?= APP_URL ?>/assets/js/dashboard.js"></script>
