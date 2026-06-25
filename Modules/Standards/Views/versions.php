<?php $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Versions de standards</h1><a class="btn btn-outline-secondary" href="/standards">Standards</a></div>
  <form class="card card-body mb-3" method="post" action="/standards/versions/store">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-2 align-items-end">
      <div class="col-md-4"><label class="form-label">Standard</label><select class="form-select" name="vst_standard_id" required><?php foreach (($refs['standards'] ?? []) as $s): ?><option value="<?= (int)$s['std_id'] ?>"><?= $h($s['std_code'] . ' — ' . $s['std_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Version</label><input class="form-control" name="vst_version" required></div>
      <div class="col-md-2"><label class="form-label">Valide du</label><input class="form-control" type="date" name="vst_valide_du" required></div>
      <div class="col-md-2"><label class="form-label">Valide au</label><input class="form-control" type="date" name="vst_valide_au"></div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Ajouter</button></div>
    </div>
  </form>
  <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Standard</th><th>Version</th><th>Valide du</th><th>Valide au</th><th>Exigences</th><th>Obligatoires</th><th></th></tr></thead><tbody>
    <?php foreach (($versions ?? []) as $v): ?><tr><td><code><?= $h($v['std_code']) ?></code> <?= $h($v['std_nom']) ?></td><td><?= $h($v['vst_version']) ?></td><td><?= $h($v['vst_valide_du']) ?></td><td><?= $h($v['vst_valide_au'] ?? '-') ?></td><td><?= (int)($v['total_exigences'] ?? 0) ?></td><td><?= (int)($v['total_exigences_obligatoires'] ?? 0) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/standards/exigences?version_id=<?= (int)$v['vst_id'] ?>">Exigences</a> <a class="btn btn-sm btn-outline-secondary" href="/standards/versions/<?= (int)$v['vst_id'] ?>/evaluation">Évaluer</a></td></tr><?php endforeach; ?>
    <?php if (empty($versions)): ?><tr><td colspan="7" class="text-center text-muted">Aucune version.</td></tr><?php endif; ?>
  </tbody></table></div></div>
</div>
