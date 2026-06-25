<?php defined('AUTOSAV_ROOT') or die;
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$type = $type ?? null;
$isEdit = !empty($type['tre_id']);
$action = $isEdit ? '/relations-societes/types/' . (int)$type['tre_id'] . '/update' : '/relations-societes/types/store';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0"><?= $isEdit ? 'Modifier le type de relation' : 'Créer un type de relation' ?></h1><a class="btn btn-outline-secondary" href="<?= url('/relations-societes/types') ?>">Retour</a></div>
  <form method="post" action="<?= url($action) ?>" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Code *</label><input class="form-control" name="tre_code" required value="<?= $h($type['tre_code'] ?? '') ?>" placeholder="client_de, fournisseur_de..."></div>
      <div class="col-md-4"><label class="form-label">Nom *</label><input class="form-control" name="tre_nom" required value="<?= $h($type['tre_nom'] ?? '') ?>"></div>
      <div class="col-md-2"><label class="form-label">Directionnel</label><div class="form-check mt-2"><input type="checkbox" class="form-check-input" name="tre_est_directionnel" value="1" <?= !isset($type['tre_est_directionnel']) || !empty($type['tre_est_directionnel']) ? 'checked' : '' ?>><label class="form-check-label">Oui</label></div></div>
      <div class="col-md-3"><label class="form-label">Statut</label><select class="form-select" name="tre_statut_id"><option value="">Aucun</option><?php foreach (($statuts ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= (int)($type['tre_statut_id'] ?? 0)===(int)$st['sta_id']?'selected':'' ?>><?= $h(($st['sta_domaine'] ?? '') . ' — ' . ($st['sta_nom'] ?? '')) ?></option><?php endforeach; ?></select></div>
      <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="tre_description" rows="4"><?= $h($type['tre_description'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary">Enregistrer</button><?php if ($isEdit): ?><button class="btn btn-outline-danger" formaction="<?= url('/relations-societes/types/' . (int)$type['tre_id'] . '/delete') ?>" formmethod="post" data-confirm="Supprimer ce type de relation ?">Supprimer</button><?php endif; ?></div>
  </form>
</div>
