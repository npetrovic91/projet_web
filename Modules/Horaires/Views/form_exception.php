<?php
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$exception = $exception ?? null;
$refs = $refs ?? [];
$isEdit = is_array($exception);
$action = $isEdit ? '/horaires/exceptions/' . (int)$exception['eht_id'] . '/update' : '/horaires/exceptions/store';
?>
<div class="container py-3">
  <h1 class="h3 mb-3"><?= $isEdit ? 'Modifier exception horaire' : 'Nouvelle exception horaire' ?></h1>
  <form method="post" action="<?= url($action) ?>" class="card card-body">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Société *</label><select class="form-select" name="societe_id" required><option value="">Sélectionner</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($exception['eht_societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label">Portée *</label><select class="form-select" name="portee_type"><?php foreach (($refs['portees'] ?? []) as $code=>$label): ?><option value="<?= $h($code) ?>" <?= ($exception['eht_portee_type'] ?? 'societe')===$code?'selected':'' ?>><?= $h($label) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">ID portée</label><input class="form-control" name="portee_id" value="<?= (int)($exception['eht_portee_id'] ?? 0) ?>"></div>
      <div class="col-md-3"><label class="form-label">Date *</label><input type="date" class="form-control" name="date" required value="<?= $h($exception['eht_date'] ?? date('Y-m-d')) ?>"></div>
      <div class="col-md-3"><label class="form-label">Ouverture</label><input type="time" class="form-control" name="ouvre_a" value="<?= $h(substr((string)($exception['eht_ouvre_a'] ?? ''),0,5)) ?>"></div>
      <div class="col-md-3"><label class="form-label">Fermeture</label><input type="time" class="form-control" name="ferme_a" value="<?= $h(substr((string)($exception['eht_ferme_a'] ?? ''),0,5)) ?>"></div>
      <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="est_ferme" value="1" <?= !empty($exception['eht_est_ferme'])?'checked':'' ?>><label class="form-check-label">Fermé toute la journée</label></div></div>
      <div class="col-md-12"><label class="form-label">Raison</label><input class="form-control" name="raison" maxlength="255" value="<?= $h($exception['eht_raison'] ?? '') ?>"></div>
    </div>
    <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Enregistrer</button><a class="btn btn-outline-secondary" href="<?= url('/horaires/exceptions') ?>">Retour</a></div>
  </form>
  <?php if ($isEdit): ?><form method="post" action="<?= url('/horaires/exceptions/' . (int)$exception['eht_id'] . '/delete') ?>" class="mt-3" data-confirm="Supprimer cette exception ?"><?= $csrfField ?? csrf_field() ?><button class="btn btn-outline-danger">Supprimer</button></form><?php endif; ?>
</div>
