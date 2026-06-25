<?php
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$isEdit = !empty($standard['std_id']);
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Modifier standard' : 'Nouveau standard' ?></h1>
    <a class="btn btn-outline-secondary" href="/standards">Retour</a>
  </div>

  <form class="card card-body mb-3" method="post" action="<?= $isEdit ? '/standards/' . (int)$standard['std_id'] . '/update' : '/standards/store' ?>">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Code</label><input class="form-control" name="std_code" required value="<?= $h($standard['std_code'] ?? '') ?>"></div>
      <div class="col-md-5"><label class="form-label">Nom</label><input class="form-control" name="std_nom" required value="<?= $h($standard['std_nom'] ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Type standard</label><select class="form-select" name="std_type_standard">
        <?php foreach (($refs['types_standard'] ?? []) as $type): ?><option value="<?= $h($type) ?>" <?= (($standard['std_type_standard'] ?? '') === $type) ? 'selected' : '' ?>><?= $h($type) ?></option><?php endforeach; ?>
      </select></div>
      <div class="col-md-6"><label class="form-label">Société propriétaire</label><select class="form-select" name="std_societe_proprietaire_id"><option value="">Global</option>
        <?php foreach (($refs['societes'] ?? []) as $soc): ?><option value="<?= (int)$soc['soc_id'] ?>" <?= ((int)($standard['std_societe_proprietaire_id'] ?? 0) === (int)$soc['soc_id']) ? 'selected' : '' ?>><?= $h($soc['soc_nom']) ?></option><?php endforeach; ?>
      </select></div>
      <div class="col-md-6"><label class="form-label">Statut</label><select class="form-select" name="std_statut_id"><option value="">Actif par défaut</option>
        <?php foreach (($refs['statuts'] ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= ((int)($standard['std_statut_id'] ?? 0) === (int)$st['sta_id']) ? 'selected' : '' ?>><?= $h(($st['sta_libelle'] ?? '') . ' (' . ($st['sta_code'] ?? '') . ')') ?></option><?php endforeach; ?>
      </select></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Enregistrer</button></div>
  </form>

  <?php if ($isEdit): ?>
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center"><span>Versions de ce standard</span><a class="btn btn-sm btn-outline-primary" href="/standards/versions">Gérer les versions</a></div>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Version</th><th>Valide du</th><th>Valide au</th><th>Exigences</th><th></th></tr></thead><tbody>
      <?php foreach (($versions ?? []) as $v): ?><tr><td><?= $h($v['vst_version']) ?></td><td><?= $h($v['vst_valide_du']) ?></td><td><?= $h($v['vst_valide_au'] ?? '-') ?></td><td><?= (int)($v['total_exigences'] ?? 0) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="/standards/versions/<?= (int)$v['vst_id'] ?>/evaluation">Évaluer</a></td></tr><?php endforeach; ?>
      <?php if (empty($versions)): ?><tr><td colspan="5" class="text-center text-muted">Aucune version.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
  <?php endif; ?>
</div>
