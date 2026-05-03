<?php declare(strict_types=1); ?>
<?php $isEdit = ($mode ?? 'create') === 'edit'; ?>
<h2 class="h4 mb-3"><?= e($page_title ?? 'Entreprise') ?></h2>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">Veuillez corriger les champs obligatoires.</div>
<?php endif; ?>
<form method="POST" action="<?= $isEdit ? '/companies/' . (int) $company['com_id'] . '/update' : '/companies/store' ?>">
    <input type="hidden" name="<?= e(CSRF_FORM_FIELD) ?>" value="<?= e($csrf_token) ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label>Type</label>
            <select name="com_type_id" class="form-control" required>
                <option value="">Selectionner</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= (int) $type['cty_id'] ?>" <?= ((int)($company['com_type_id'] ?? 0) === (int)$type['cty_id']) ? 'selected' : '' ?>>
                        <?= e($type['cty_label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label>Holding</label>
            <select name="com_holding_id" class="form-control">
                <option value="">Aucune</option>
                <?php foreach ($holdings as $holding): ?>
                    <option value="<?= (int) $holding['com_id'] ?>" <?= ((int)($company['com_holding_id'] ?? 0) === (int)$holding['com_id']) ? 'selected' : '' ?>>
                        <?= e($holding['com_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3"><label>Nom</label><input name="com_name" class="form-control" value="<?= e($company['com_name'] ?? '') ?>" required></div>
        <div class="col-md-6 mb-3"><label>Raison sociale</label><input name="com_legal_name" class="form-control" value="<?= e($company['com_legal_name'] ?? '') ?>"></div>
        <div class="col-md-4 mb-3"><label>SIRET</label><input name="com_siret" class="form-control" value="<?= e($company['com_siret'] ?? '') ?>"></div>
        <div class="col-md-8 mb-3"><label>Adresse</label><input name="com_address" class="form-control" value="<?= e($company['com_address'] ?? '') ?>"></div>
        <div class="col-md-3 mb-3"><label>Code postal</label><input name="com_zipcode" class="form-control" value="<?= e($company['com_zipcode'] ?? '') ?>"></div>
        <div class="col-md-5 mb-3"><label>Ville</label><input name="com_city" class="form-control" value="<?= e($company['com_city'] ?? '') ?>"></div>
        <div class="col-md-4 mb-3"><label>Pays</label><input name="com_country" class="form-control" value="<?= e($company['com_country'] ?? 'France') ?>"></div>
        <div class="col-md-6 mb-3"><label>Email</label><input name="com_email" type="email" class="form-control" value="<?= e($company['com_email'] ?? '') ?>"></div>
        <div class="col-md-6 mb-3"><label>Telephone</label><input name="com_phone" class="form-control" value="<?= e($company['com_phone'] ?? '') ?>"></div>
    </div>
    <input type="hidden" name="com_status" value="active">
    <input type="hidden" name="com_is_active" value="1">
    <button class="btn btn-primary" type="submit">Enregistrer</button>
    <a href="/companies" class="btn btn-secondary">Annuler</a>
</form>
