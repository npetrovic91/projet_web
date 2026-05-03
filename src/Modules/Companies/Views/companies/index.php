<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Entreprises</h2>
    <a href="/companies/create" class="btn btn-primary btn-sm">Nouvelle entreprise</a>
</div>

<form method="GET" action="/companies" class="row mb-3">
    <div class="col-md-4"><input class="form-control" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Recherche"></div>
    <div class="col-md-3">
        <select class="form-control" name="type">
            <option value="">Tous les types</option>
            <?php foreach ($types as $type): ?>
                <option value="<?= e($type['cty_code']) ?>" <?= (($filters['type_code'] ?? '') === $type['cty_code']) ? 'selected' : '' ?>>
                    <?= e($type['cty_label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-secondary btn-block" type="submit">Filtrer</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Nom</th><th>Type</th><th>Ville</th><th>Holding</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($companies as $company): ?>
                <tr>
                    <td><?= e($company['com_name']) ?></td>
                    <td><?= e($company['type_label']) ?></td>
                    <td><?= e($company['com_city'] ?? '') ?></td>
                    <td><?= e($company['holding_name'] ?? '') ?></td>
                    <td><?= !empty($company['com_is_active']) ? 'Active' : 'Inactive' ?></td>
                    <td class="text-right"><a class="btn btn-sm btn-outline-primary" href="/companies/<?= (int) $company['com_id'] ?>">Voir</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
