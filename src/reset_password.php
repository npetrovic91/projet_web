<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Marques</h2>
    <a href="/brands/create" class="btn btn-primary btn-sm">Nouvelle marque</a>
</div>
<form method="GET" action="/brands" class="row mb-3">
    <div class="col-md-5"><input class="form-control" name="search" value="<?= e($search ?? '') ?>" placeholder="Recherche marque"></div>
    <div class="col-md-2"><button class="btn btn-secondary btn-block">Filtrer</button></div>
</form>
<div class="card">
    <table class="table table-hover mb-0">
        <thead><tr><th>Code</th><th>Nom</th><th>Active</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($brands as $brand): ?>
            <tr>
                <td><?= e($brand['brd_code']) ?></td>
                <td><?= e($brand['brd_name']) ?></td>
                <td><?= !empty($brand['brd_is_active']) ? 'Oui' : 'Non' ?></td>
                <td class="text-right"><a class="btn btn-sm btn-outline-primary" href="/brands/<?= (int) $brand['brd_id'] ?>/edit">Modifier</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
