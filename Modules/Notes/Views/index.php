<?php
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$stats = $stats ?? [];
$notes = $notes ?? [];
$refs = $refs ?? [];
$filters = $filters ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-1">Notes / étiquettes</h1>
      <p class="text-muted mb-0">Notes génériques attachables aux entités via <code>cible_type</code> / <code>cible_id</code>.</p>
    </div>
    <div class="btn-group">
      <a class="btn btn-primary" href="<?= url('/notes/create') ?>">Nouvelle note</a>
      <a class="btn btn-outline-secondary" href="<?= url('/labels') ?>">Étiquettes</a>
      <a class="btn btn-outline-secondary" href="<?= url('/label-assignments') ?>">Affectations</a>
      <a class="btn btn-outline-secondary" href="<?= url('/notes/export.json') ?>">Export JSON</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <?php foreach ([
      'notes_total' => 'Notes',
      'notes_privees' => 'Notes privées',
      'etiquettes_total' => 'Étiquettes',
      'affectations_total' => 'Affectations',
    ] as $key => $label): ?>
      <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small"><?= $h($label) ?></div><div class="fs-3 fw-bold"><?= (int)($stats[$key] ?? 0) ?></div></div></div></div>
    <?php endforeach; ?>
  </div>

  <form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-3"><label class="form-label">Recherche</label><input class="form-control" name="q" value="<?= $h($filters['q'] ?? '') ?>"></div>
      <div class="col-md-2"><label class="form-label">Société</label><select class="form-select" name="societe_id"><option value="0">Toutes</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($filters['societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Cible type</label><input class="form-control" name="cible_type" value="<?= $h($filters['cible_type'] ?? '') ?>" placeholder="societe, utilisateur..."></div>
      <div class="col-md-1"><label class="form-label">Cible ID</label><input class="form-control" name="cible_id" value="<?= (int)($filters['cible_id'] ?? 0) ?>"></div>
      <div class="col-md-2"><label class="form-label">Visibilité</label><select class="form-select" name="visibilite"><option value="">Toutes</option><option value="publique" <?= ($filters['visibilite'] ?? '')==='publique'?'selected':'' ?>>Publique</option><option value="privee" <?= ($filters['visibilite'] ?? '')==='privee'?'selected':'' ?>>Privée</option></select></div>
      <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrer</button></div>
    </div>
  </form>

  <div class="card"><div class="table-responsive"><table class="table table-striped table-hover mb-0">
    <thead><tr><th>ID</th><th>Société</th><th>Cible</th><th>Titre</th><th>Étiquettes</th><th>Visibilité</th><th>Créée</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($notes as $n): ?>
        <tr>
          <td><?= (int)$n['nte_id'] ?></td>
          <td><?= $h($n['societe_nom'] ?? '—') ?></td>
          <td><code><?= $h($n['nte_cible_type']) ?>#<?= (int)$n['nte_cible_id'] ?></code></td>
          <td><a href="<?= url('/notes/' . (int)$n['nte_id']) ?>"><?= $h($n['nte_titre'] ?: mb_substr((string)$n['nte_contenu'], 0, 60)) ?></a></td>
          <td><?= $h($n['etiquettes_libelles'] ?? '') ?></td>
          <td><?= !empty($n['nte_est_privee']) ? 'Privée' : 'Publique' ?></td>
          <td><?= $h($n['nte_cree_le'] ?? '') ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= url('/notes/' . (int)$n['nte_id'] . '/edit') ?>">Modifier</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$notes): ?><tr><td colspan="8" class="text-center text-muted py-4">Aucune note.</td></tr><?php endif; ?>
    </tbody>
  </table></div></div>
</div>
