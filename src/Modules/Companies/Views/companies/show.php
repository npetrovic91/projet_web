<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><?= e($company['com_name']) ?></h2>
    <a class="btn btn-outline-primary btn-sm" href="/companies/<?= (int) $company['com_id'] ?>/edit">Modifier</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h3 class="h5">Informations</h3>
            <p><strong>Type :</strong> <?= e($company['type_label']) ?></p>
            <p><strong>Raison sociale :</strong> <?= e($company['com_legal_name'] ?? '') ?></p>
            <p><strong>Adresse :</strong> <?= e(trim(($company['com_address'] ?? '') . ' ' . ($company['com_zipcode'] ?? '') . ' ' . ($company['com_city'] ?? ''))) ?></p>
            <p><strong>Email :</strong> <?= e($company['com_email'] ?? '') ?></p>
            <p><strong>Telephone :</strong> <?= e($company['com_phone'] ?? '') ?></p>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h3 class="h5">Marques</h3>
            <?php foreach ($brands as $brand): ?><span class="badge badge-info mr-1"><?= e($brand['brd_name']) ?></span><?php endforeach; ?>
            <form class="mt-3" method="POST" action="/companies/brand/attach">
                <input type="hidden" name="<?= e(CSRF_FORM_FIELD) ?>" value="<?= e($csrf_token) ?>">
                <input type="hidden" name="company_id" value="<?= (int) $company['com_id'] ?>">
                <div class="input-group">
                    <select name="brand_id" class="form-control">
                        <?php foreach ($available_brands as $brand): ?><option value="<?= (int) $brand['brd_id'] ?>"><?= e($brand['brd_name']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="input-group-append"><button class="btn btn-primary">Ajouter</button></div>
                </div>
            </form>
        </div></div>
    </div>
</div>

<div class="card mt-3"><div class="card-body">
    <h3 class="h5">Relations structurelles</h3>
    <table class="table table-sm">
        <thead><tr><th>Parent</th><th>Enfant</th><th>Type</th></tr></thead>
        <tbody>
        <?php foreach ($relations as $relation): ?>
            <tr><td><?= e($relation['parent_name']) ?></td><td><?= e($relation['child_name']) ?></td><td><?= e($relation['cor_relation_type']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
