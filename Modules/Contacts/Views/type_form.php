<?php defined('AUTOSAV_ROOT') or die;
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$type = $type ?? null;
$isEdit = !empty($type['tco_id']);
$action = $isEdit ? '/contacts-societes/types/' . (int)$type['tco_id'] . '/update' : '/contacts-societes/types/store';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0"><?= $isEdit ? 'Modifier le type de contact' : 'Créer un type de contact' ?></h1><a class="btn btn-outline-secondary" href="<?= url('/contacts-societes/types') ?>">Retour</a></div>
  <form method="post" action="<?= url($action) ?>" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Code *</label><input class="form-control" name="tco_code" required value="<?= $h($type['tco_code'] ?? '') ?>" placeholder="administratif, commercial..."></div>
      <div class="col-md-4"><label class="form-label">Nom *</label><input class="form-control" name="tco_nom" required value="<?= $h($type['tco_nom'] ?? '') ?>"></div>
      <div class="col-md-5"><label class="form-label">Statut</label><select class="form-select" name="tco_statut_id"><option value="">Aucun</option><?php foreach (($statuts ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= (int)($type['tco_statut_id'] ?? 0)===(int)$st['sta_id']?'selected':'' ?>><?= $h(($st['sta_domaine'] ?? '') . ' — ' . ($st['sta_nom'] ?? '')) ?></option><?php endforeach; ?></select></div>
      <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="tco_description" rows="4"><?= $h($type['tco_description'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary">Enregistrer</button><?php if ($isEdit): ?><button class="btn btn-outline-danger" formaction="<?= url('/contacts-societes/types/' . (int)$type['tco_id'] . '/delete') ?>" formmethod="post" data-confirm="Supprimer ce type de contact ?">Supprimer</button><?php endif; ?></div>
  </form>
</div>
