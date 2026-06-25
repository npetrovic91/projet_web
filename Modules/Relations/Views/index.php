<?php defined('AUTOSAV_ROOT') or die;
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$relations = $relations ?? [];
$refs = $refs ?? [];
$filters = $filters ?? [];
$stats = $stats ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-1">Relations sociétés</h1>
      <p class="text-muted mb-0">Liens clients, fournisseurs, sous-traitants, partenaires, réseaux, constructeurs, importateurs, marques et concessions.</p>
    </div>
    <div class="btn-group">
      <a class="btn btn-primary" href="<?= url('/relations-societes/create') ?>">Nouvelle relation</a>
      <a class="btn btn-outline-secondary" href="<?= url('/relations-societes/types') ?>">Types</a>
      <a class="btn btn-outline-secondary" href="<?= url('/representations-marques') ?>">Représentations marques</a>
      <a class="btn btn-outline-secondary" href="<?= url('/relations-societes/export.json') ?>">Export JSON</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <?php foreach (['relations_total'=>'Relations','relations_actives'=>'Actives','relations_terminees'=>'Terminées','representations_total'=>'Représentations marques'] as $key => $label): ?>
      <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small"><?= $h($label) ?></div><div class="fs-3 fw-bold"><?= (int)($stats[$key] ?? 0) ?></div></div></div></div>
    <?php endforeach; ?>
  </div>

  <form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-3"><label class="form-label">Recherche</label><input class="form-control" name="q" value="<?= $h($filters['q'] ?? '') ?>" placeholder="Société, type, code"></div>
      <div class="col-md-2"><label class="form-label">Société impliquée</label><select class="form-select" name="societe_id"><option value="0">Toutes</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($filters['societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Type</label><select class="form-select" name="type_id"><option value="0">Tous</option><?php foreach (($refs['types'] ?? []) as $t): ?><option value="<?= (int)$t['tre_id'] ?>" <?= (int)($filters['type_id'] ?? 0)===(int)$t['tre_id']?'selected':'' ?>><?= $h($t['tre_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Période</label><select class="form-select" name="periode"><option value="">Toutes</option><option value="active" <?= ($filters['periode'] ?? '')==='active'?'selected':'' ?>>Active</option><option value="terminee" <?= ($filters['periode'] ?? '')==='terminee'?'selected':'' ?>>Terminée</option></select></div>
      <div class="col-md-2"><label class="form-label">Statut</label><select class="form-select" name="statut_id"><option value="0">Tous</option><?php foreach (($refs['statuts'] ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= (int)($filters['statut_id'] ?? 0)===(int)$st['sta_id']?'selected':'' ?>><?= $h(($st['sta_domaine'] ?? '') . ' — ' . ($st['sta_nom'] ?? '')) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-1"><button class="btn btn-outline-primary w-100">Filtrer</button></div>
    </div>
  </form>

  <div class="card"><div class="table-responsive"><table class="table table-striped table-hover mb-0">
    <thead><tr><th>Source</th><th>Type</th><th>Cible</th><th>Début</th><th>Fin</th><th>Statut</th><th>Créée par</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($relations as $r): ?>
        <tr>
          <td><?= $h($r['societe_source_nom'] ?? '') ?></td>
          <td><code><?= $h($r['tre_code'] ?? '') ?></code><br><span class="small text-muted"><?= $h($r['type_relation_nom'] ?? '') ?></span></td>
          <td><?= $h($r['societe_cible_nom'] ?? '') ?></td>
          <td><?= $h($r['rso_debute_le'] ?? '') ?></td>
          <td><?= $h($r['rso_termine_le'] ?? '—') ?></td>
          <td><?= $h($r['statut_nom'] ?? '—') ?></td>
          <td><?= $h($r['cree_par_societe_nom'] ?? '—') ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= url('/relations-societes/' . (int)$r['rso_id'] . '/edit') ?>">Modifier</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$relations): ?><tr><td colspan="8" class="text-center text-muted py-4">Aucune relation société.</td></tr><?php endif; ?>
    </tbody>
  </table></div></div>
</div>
