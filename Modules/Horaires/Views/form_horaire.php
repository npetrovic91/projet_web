<?php
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$horaire = $horaire ?? null;
$refs = $refs ?? [];
$isEdit = is_array($horaire);
$action = $isEdit ? '/horaires/' . (int)$horaire['htr_id'] . '/update' : '/horaires/store';
?>
<div class="container py-3">
  <h1 class="h3 mb-3"><?= $isEdit ? 'Modifier horaire' : 'Nouvel horaire' ?></h1>
  <form method="post" action="<?= url($action) ?>" class="card card-body">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Société *</label><select class="form-select" name="societe_id" required><option value="">Sélectionner</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($horaire['htr_societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label">Portée *</label><select class="form-select" name="portee_type"><?php foreach (($refs['portees'] ?? []) as $code=>$label): ?><option value="<?= $h($code) ?>" <?= ($horaire['htr_portee_type'] ?? 'societe')===$code?'selected':'' ?>><?= $h($label) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">ID portée</label><input class="form-control" name="portee_id" value="<?= (int)($horaire['htr_portee_id'] ?? 0) ?>"><div class="form-text">Vide ou 0 = société entière.</div></div>
      <div class="col-md-3"><label class="form-label">Jour *</label><select class="form-select" name="jour_semaine" required><?php foreach (($refs['jours'] ?? []) as $id=>$label): ?><option value="<?= (int)$id ?>" <?= (int)($horaire['htr_jour_semaine'] ?? date('N'))===(int)$id?'selected':'' ?>><?= $h($label) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label">Ouverture</label><input type="time" class="form-control" name="ouvre_a" value="<?= $h(substr((string)($horaire['htr_ouvre_a'] ?? ''),0,5)) ?>"></div>
      <div class="col-md-3"><label class="form-label">Fermeture</label><input type="time" class="form-control" name="ferme_a" value="<?= $h(substr((string)($horaire['htr_ferme_a'] ?? ''),0,5)) ?>"></div>
      <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="est_ferme" value="1" <?= !empty($horaire['htr_est_ferme'])?'checked':'' ?>><label class="form-check-label">Fermé toute la journée</label></div></div>
    </div>
    <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Enregistrer</button><a class="btn btn-outline-secondary" href="<?= url('/horaires') ?>">Retour</a></div>
  </form>
  <?php if ($isEdit): ?><form method="post" action="<?= url('/horaires/' . (int)$horaire['htr_id'] . '/delete') ?>" class="mt-3" data-confirm="Supprimer cet horaire ?"><?= $csrfField ?? csrf_field() ?><button class="btn btn-outline-danger">Supprimer</button></form><?php endif; ?>
</div>
