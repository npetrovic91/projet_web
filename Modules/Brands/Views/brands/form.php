<?php declare(strict_types=1); ?>
<?php $isEdit = ($mode ?? 'create') === 'edit'; ?>
<h2 class="h4 mb-3"><?= e($page_title ?? 'Marque') ?></h2>
<?php if (!empty($errors)): ?><div class="alert alert-danger">Code et nom sont obligatoires.</div><?php endif; ?>
<form method="POST" action="<?= $isEdit ? '/brands/' . (int) $brand['brd_id'] . '/update' : '/brands/store' ?>">
    <input type="hidden" name="<?= e(CSRF_FORM_FIELD) ?>" value="<?= e($csrf_token) ?>">
    <div class="row">
        <div class="col-md-4 mb-3"><label>Code</label><input class="form-control" name="brd_code" value="<?= e($brand['brd_code'] ?? '') ?>" required></div>
        <div class="col-md-8 mb-3"><label>Nom</label><input class="form-control" name="brd_name" value="<?= e($brand['brd_name'] ?? '') ?>" required></div>
        <div class="col-md-12 mb-3"><label>Logo URL</label><input class="form-control" name="brd_logo_url" value="<?= e($brand['brd_logo_url'] ?? '') ?>"></div>
    </div>
    <input type="hidden" name="brd_is_active" value="1">
    <button class="btn btn-primary">Enregistrer</button>
    <a href="/brands" class="btn btn-secondary">Annuler</a>
</form>
