<?php defined('AUTOSAV_ROOT') or die;
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$representations = $representations ?? [];
$refs = $refs ?? [];
$filters = $filters ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="h3 mb-0">Représentations marques</h1><p class="text-muted mb-0">Liens concession / marque / importateur / constructeur utilisés pour le contexte actif et les standards.</p></div>
    <div class="btn-group"><a class="btn btn-primary" href="<?= url('/representations-marques/create') ?>">Nouvelle représentation</a><a class="btn btn-outline-secondary" href="<?= url('/relations-societes') ?>">Relations sociétés</a></div>
  </div>
  <form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
      <?php foreach (['societe'=>'Société impliquée','concession'=>'Concession','marque'=>'Marque','importateur'=>'Importateur','constructeur'=>'Constructeur'] as $key => $label): ?>
        <div class="col-md"><label class="form-label"><?= $h($label) ?></label><select class="form-select" name="<?= $h($key) ?>_id"><option value="0">Toutes</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($filters[$key . '_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <?php endforeach; ?>
      <div class="col-md-1"><button class="btn btn-outline-primary w-100">Filtrer</button></div>
    </div>
  </form>
  <div class="card"><div class="table-responsive"><table class="table table-striped table-hover mb-0">
    <thead><tr><th>Concession</th><th>Marque</th><th>Importateur</th><th>Constructeur</th><th>Début</th><th>Fin</th><th>Statut</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($representations as $r): ?>
        <tr><td><?= $h($r['concession_nom'] ?? '') ?></td><td><?= $h($r['marque_nom'] ?? '') ?></td><td><?= $h($r['importateur_nom'] ?? '—') ?></td><td><?= $h($r['constructeur_nom'] ?? '—') ?></td><td><?= $h($r['rma_debute_le'] ?? '') ?></td><td><?= $h($r['rma_termine_le'] ?? '—') ?></td><td><?= $h($r['statut_nom'] ?? '—') ?></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= url('/representations-marques/' . (int)$r['rma_id'] . '/edit') ?>">Modifier</a></td></tr>
      <?php endforeach; ?>
      <?php if (!$representations): ?><tr><td colspan="8" class="text-center text-muted py-4">Aucune représentation marque.</td></tr><?php endif; ?>
    </tbody>
  </table></div></div>
</div>
