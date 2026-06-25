<?php defined('AUTOSAV_ROOT') or die;
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$types = $types ?? [];
$filters = $filters ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="h3 mb-0">Types de relations sociétés</h1><p class="text-muted mb-0">Référentiel des liens commerciaux, techniques, hiérarchiques, réseaux et marketplace.</p></div>
    <div class="btn-group"><a class="btn btn-primary" href="<?= url('/relations-societes/types/create') ?>">Nouveau type</a><a class="btn btn-outline-secondary" href="<?= url('/relations-societes') ?>">Relations</a></div>
  </div>
  <form method="get" class="card card-body mb-3"><div class="row g-2"><div class="col-md-10"><input class="form-control" name="q" value="<?= $h($filters['q'] ?? '') ?>" placeholder="Recherche code, nom, description"></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrer</button></div></div></form>
  <div class="card"><div class="table-responsive"><table class="table table-striped mb-0">
    <thead><tr><th>Code</th><th>Nom</th><th>Directionnel</th><th>Description</th><th>Statut</th><th>Relations</th><th></th></tr></thead>
    <tbody><?php foreach ($types as $t): ?><tr><td><code><?= $h($t['tre_code']) ?></code></td><td><?= $h($t['tre_nom']) ?></td><td><?= !empty($t['tre_est_directionnel']) ? 'Oui' : 'Non' ?></td><td><?= $h($t['tre_description'] ?? '') ?></td><td><?= $h($t['statut_nom'] ?? '—') ?></td><td><?= (int)($t['relations_total'] ?? 0) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= url('/relations-societes/types/' . (int)$t['tre_id'] . '/edit') ?>">Modifier</a></td></tr><?php endforeach; ?><?php if (!$types): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucun type de relation.</td></tr><?php endif; ?></tbody>
  </table></div></div>
</div>
