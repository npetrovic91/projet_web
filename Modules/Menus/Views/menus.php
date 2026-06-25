<?php $rows = $rows ?? []; $listes = $listes ?? []; $filters = $filters ?? []; ?>
<section class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Menus</h1>
        <a class="btn btn-primary" href="<?= url('/menus/create') ?>">Créer un menu</a>
    </div>
    <form class="row g-2 mb-3" method="get">
        <div class="col-md-5"><input class="form-control" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Recherche code, nom, module"></div>
        <div class="col-md-4"><select class="form-select" name="module_id"><option value="">Tous les modules</option><?php foreach (($listes['modules'] ?? []) as $m): ?><option value="<?= (int)$m['mod_id'] ?>" <?= (string)($filters['module_id'] ?? '') === (string)$m['mod_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['mod_nom']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><button class="btn btn-outline-secondary w-100">Filtrer</button></div>
    </form>
    <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Code</th><th>Nom</th><th>Module</th><th>Éléments</th><th>Statut</th><th></th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?><tr>
            <td><code><?= htmlspecialchars($r['men_code']) ?></code></td><td><?= htmlspecialchars($r['men_nom']) ?></td><td><?= htmlspecialchars((string)($r['mod_nom'] ?? 'Global')) ?></td><td><?= (int)($r['nombre_elements'] ?? 0) ?></td><td><?= htmlspecialchars((string)($r['statut_libelle'] ?? $r['statut_code'] ?? '')) ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('/menus/' . (int)$r['men_id'] . '/edit') ?>">Modifier</a></td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
</section>
