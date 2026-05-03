<?php
declare(strict_types=1);
?>
<div class="row mb-3">
    <div class="col-md-6">
        <label for="active-company-select" class="form-label">Entreprise active</label>
        <select id="active-company-select" class="form-control" data-current="<?= e($active_company_id ?? '') ?>">
            <?php foreach ($companies as $company): ?>
                <option value="<?= (int) $company['com_id'] ?>" <?= ((int) $company['com_id'] === (int) $active_company_id) ? 'selected' : '' ?>>
                    <?= e($company['com_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label for="active-brand-select" class="form-label">Marque active</label>
        <select id="active-brand-select" class="form-control" data-current="<?= e($active_brand_id ?? '') ?>">
            <option value="">Toutes les marques</option>
            <?php foreach ($brands as $brand): ?>
                <option value="<?= (int) $brand['brd_id'] ?>" <?= ((int) $brand['brd_id'] === (int) $active_brand_id) ? 'selected' : '' ?>>
                    <?= e($brand['brd_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="row" id="dashboard-widgets">
    <?php foreach ($sync_widgets as $widget): ?>
        <div class="col-lg-4 col-md-6 mb-3" data-widget-code="<?= e($widget['code']) ?>">
            <?= $widget['rendered_html'] ?>
        </div>
    <?php endforeach; ?>

    <?php foreach ($async_widgets as $widget): ?>
        <div class="col-lg-4 col-md-6 mb-3 dashboard-widget-async" data-widget-code="<?= e($widget['code']) ?>">
            <div class="card">
                <div class="card-body text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Chargement...
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
window.AutoSAVDashboardConfig = {
    csrfToken: <?= json_encode($csrf_token) ?>,
    asyncWidgets: <?= json_encode(array_column($async_widgets, 'code')) ?>
};
</script>
<script src="<?= APP_URL ?>/assets/js/dashboard.js"></script>
