<?php defined('AUTOSAV_ROOT') or die;
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$relation = $relation ?? null;
$refs = $refs ?? [];
$isEdit = !empty($relation['rso_id']);
$action = $isEdit ? '/relations-societes/' . (int)$relation['rso_id'] . '/update' : '/relations-societes/store';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Modifier la relation société' : 'Créer une relation société' ?></h1>
    <a class="btn btn-outline-secondary" href="<?= url('/relations-societes') ?>">Retour</a>
  </div>
  <form method="post" action="<?= url($action) ?>" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Société source *</label><select class="form-select" name="rso_societe_source_id" required><option value="">Sélectionner</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($relation['rso_societe_source_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Type de relation *</label><select class="form-select" name="rso_type_relation_societe_id" required><option value="">Sélectionner</option><?php foreach (($refs['types'] ?? []) as $t): ?><option value="<?= (int)$t['tre_id'] ?>" <?= (int)($relation['rso_type_relation_societe_id'] ?? 0)===(int)$t['tre_id']?'selected':'' ?>><?= $h($t['tre_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Société cible *</label><select class="form-select" name="rso_societe_cible_id" required><option value="">Sélectionner</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($relation['rso_societe_cible_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label">Début *</label><input type="date" class="form-control" name="rso_debute_le" required value="<?= $h($relation['rso_debute_le'] ?? date('Y-m-d')) ?>"></div>
      <div class="col-md-3"><label class="form-label">Fin</label><input type="date" class="form-control" name="rso_termine_le" value="<?= $h($relation['rso_termine_le'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Créée par société</label><select class="form-select" name="rso_cree_par_societe_id"><option value="">Source par défaut</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($relation['rso_cree_par_societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label">Statut</label><select class="form-select" name="rso_statut_id"><option value="">Aucun</option><?php foreach (($refs['statuts'] ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= (int)($relation['rso_statut_id'] ?? 0)===(int)$st['sta_id']?'selected':'' ?>><?= $h(($st['sta_domaine'] ?? '') . ' — ' . ($st['sta_nom'] ?? '')) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary">Enregistrer</button><?php if ($isEdit): ?><button class="btn btn-outline-danger" formaction="<?= url('/relations-societes/' . (int)$relation['rso_id'] . '/delete') ?>" formmethod="post" data-confirm="Supprimer cette relation ?">Supprimer</button><?php endif; ?></div>
  </form>
</div>
