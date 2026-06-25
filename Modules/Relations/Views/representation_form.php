<?php defined('AUTOSAV_ROOT') or die;
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$representation = $representation ?? null;
$refs = $refs ?? [];
$isEdit = !empty($representation['rma_id']);
$action = $isEdit ? '/representations-marques/' . (int)$representation['rma_id'] . '/update' : '/representations-marques/store';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Modifier la représentation marque' : 'Créer une représentation marque' ?></h1>
    <a class="btn btn-outline-secondary" href="<?= url('/representations-marques') ?>">Retour</a>
  </div>
  <form method="post" action="<?= url($action) ?>" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Concession *</label><select class="form-select" name="rma_concession_societe_id" required><option value="">Sélectionner</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($representation['rma_concession_societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Marque *</label><select class="form-select" name="rma_marque_societe_id" required><option value="">Sélectionner</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($representation['rma_marque_societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Importateur</label><select class="form-select" name="rma_importateur_societe_id"><option value="">Aucun</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($representation['rma_importateur_societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Constructeur</label><select class="form-select" name="rma_constructeur_societe_id"><option value="">Aucun</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($representation['rma_constructeur_societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Début *</label><input type="date" class="form-control" name="rma_debute_le" required value="<?= $h($representation['rma_debute_le'] ?? date('Y-m-d')) ?>"></div>
      <div class="col-md-4"><label class="form-label">Fin</label><input type="date" class="form-control" name="rma_termine_le" value="<?= $h($representation['rma_termine_le'] ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Statut</label><select class="form-select" name="rma_statut_id"><option value="">Aucun</option><?php foreach (($refs['statuts'] ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= (int)($representation['rma_statut_id'] ?? 0)===(int)$st['sta_id']?'selected':'' ?>><?= $h(($st['sta_domaine'] ?? '') . ' — ' . ($st['sta_nom'] ?? '')) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary">Enregistrer</button><?php if ($isEdit): ?><button class="btn btn-outline-danger" formaction="<?= url('/representations-marques/' . (int)$representation['rma_id'] . '/delete') ?>" formmethod="post" data-confirm="Supprimer cette représentation ?">Supprimer</button><?php endif; ?></div>
  </form>
</div>
