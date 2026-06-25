<?php $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Exigences de standards</h1><a class="btn btn-outline-secondary" href="/standards/versions">Versions</a></div>
  <form class="card card-body mb-3" method="post" action="/standards/exigences/store">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-2 align-items-end">
      <div class="col-md-3"><label class="form-label">Version</label><select class="form-select" name="evs_version_standard_id" required><?php foreach (($refs['versions'] ?? []) as $v): ?><option value="<?= (int)$v['vst_id'] ?>" <?= ((int)$version_id === (int)$v['vst_id']) ? 'selected' : '' ?>><?= $h($v['std_code'] . ' — ' . $v['vst_version']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Type</label><select class="form-select" name="evs_type_exigence"><option value="competence">Compétence</option><option value="certification">Certification</option><option value="materiel">Matériel</option><option value="formation">Formation</option></select></div>
      <div class="col-md-2"><label class="form-label">Compétence</label><select class="form-select" name="evs_competence_id"><option value="">-</option><?php foreach (($refs['competences'] ?? []) as $c): ?><option value="<?= (int)$c['cmp_id'] ?>"><?= $h($c['cmp_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Certification</label><select class="form-select" name="evs_certification_id"><option value="">-</option><?php foreach (($refs['certifications'] ?? []) as $c): ?><option value="<?= (int)$c['cer_id'] ?>"><?= $h($c['cer_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Niveau min.</label><select class="form-select" name="evs_niveau_competence_minimum_id"><option value="">-</option><?php foreach (($refs['niveaux'] ?? []) as $n): ?><option value="<?= (int)$n['nco_id'] ?>"><?= $h($n['nco_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-1"><label class="form-label">Oblig.</label><input class="form-check-input d-block mt-2" type="checkbox" name="evs_est_obligatoire" value="1" checked></div>
      <div class="col-md-12"><button class="btn btn-primary">Ajouter l’exigence</button></div>
    </div>
  </form>
  <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Standard</th><th>Version</th><th>Type</th><th>Compétence</th><th>Certification</th><th>Niveau minimum</th><th>Obligatoire</th><th></th></tr></thead><tbody>
    <?php foreach (($exigences ?? []) as $e): ?><tr><td><code><?= $h($e['std_code']) ?></code> <?= $h($e['std_nom']) ?></td><td><?= $h($e['vst_version']) ?></td><td><?= $h($e['evs_type_exigence']) ?></td><td><?= $h($e['cmp_nom'] ?? '-') ?></td><td><?= $h($e['cer_nom'] ?? '-') ?></td><td><?= $h($e['nco_nom'] ?? '-') ?></td><td><?= ((int)$e['evs_est_obligatoire'] === 1) ? 'Oui' : 'Non' ?></td><td class="text-end"><form method="post" action="/standards/exigences/<?= (int)$e['evs_id'] ?>/delete" data-confirm="Supprimer cette exigence ?"><?= $csrfField ?? csrf_field() ?><button class="btn btn-sm btn-outline-danger">Supprimer</button></form></td></tr><?php endforeach; ?>
    <?php if (empty($exigences)): ?><tr><td colspan="8" class="text-center text-muted">Aucune exigence.</td></tr><?php endif; ?>
  </tbody></table></div></div>
</div>
