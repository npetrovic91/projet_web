<?php $rows = $rows ?? []; $listes = $listes ?? []; $filters = $filters ?? []; ?>
<section class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Éléments de menu</h1>
        <a class="btn btn-primary" href="<?= url('/menus/elements/create') ?>">Créer un élément</a>
    </div>
    <form class="row g-2 mb-3" method="get">
        <div class="col-md-4"><input class="form-control" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Recherche libellé, route, permission"></div>
        <div class="col-md-3"><select class="form-select" name="menu_id"><option value="">Tous les menus</option><?php foreach (($listes['menus'] ?? []) as $m): ?><option value="<?= (int)$m['men_id'] ?>" <?= (string)($filters['menu_id'] ?? '') === (string)$m['men_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['men_nom']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><select class="form-select" name="module_id"><option value="">Tous les modules</option><?php foreach (($listes['modules'] ?? []) as $m): ?><option value="<?= (int)$m['mod_id'] ?>" <?= (string)($filters['module_id'] ?? '') === (string)$m['mod_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['mod_nom']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filtrer</button></div>
    </form>
    <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Menu</th><th>Libellé</th><th>Route</th><th>Parent</th><th>Module</th><th>Permission</th><th>Position</th><th></th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?><tr>
            <td><?= htmlspecialchars($r['men_nom']) ?></td><td><?= htmlspecialchars($r['eme_libelle']) ?></td><td><code><?= htmlspecialchars((string)($r['eme_route'] ?? '')) ?></code></td><td><?= htmlspecialchars((string)($r['parent_libelle'] ?? '')) ?></td><td><?= htmlspecialchars((string)($r['mod_nom'] ?? 'Global')) ?></td><td><code><?= htmlspecialchars((string)($r['permission_code'] ?? '')) ?></code></td><td><?= (int)$r['eme_position'] ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('/menus/elements/' . (int)$r['eme_id'] . '/edit') ?>">Modifier</a></td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
</section>
