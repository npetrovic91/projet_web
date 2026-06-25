<?php $h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); $isEdit = is_array($etiquette ?? null); ?>
<div class="container py-3">
  <h1 class="h3 mb-3"><?= $isEdit ? 'Modifier étiquette' : 'Nouvelle étiquette' ?></h1>
  <form method="post" action="<?= url($isEdit ? '/labels/' . (int)$etiquette['eti_id'] . '/update' : '/labels/store') ?>" class="card card-body">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Société</label><select name="societe_id" class="form-select"><option value="">Globale</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($etiquette['eti_societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" maxlength="80" value="<?= $h($etiquette['eti_code'] ?? '') ?>" required></div>
      <div class="col-md-4"><label class="form-label">Libellé</label><input class="form-control" name="libelle" maxlength="120" value="<?= $h($etiquette['eti_libelle'] ?? '') ?>" required></div>
      <div class="col-md-4"><label class="form-label">Couleur</label><input class="form-control" name="couleur" maxlength="20" placeholder="#0d6efd" value="<?= $h($etiquette['eti_couleur'] ?? '') ?>"></div>
      <div class="col-md-8"><label class="form-label">Description</label><input class="form-control" name="description" value="<?= $h($etiquette['eti_description'] ?? '') ?>"></div>
      <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Enregistrer</button><a class="btn btn-outline-secondary" href="<?= url('/labels') ?>">Retour</a></div>
    </div>
  </form>
</div>
