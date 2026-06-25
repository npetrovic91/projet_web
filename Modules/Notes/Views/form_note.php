<?php
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$note = $note ?? null;
$refs = $refs ?? [];
$isEdit = is_array($note);
$selectedEtiquettes = [];
foreach (($note['etiquettes'] ?? []) as $e) { $selectedEtiquettes[] = (int)$e['eti_id']; }
?>
<div class="container py-3">
  <h1 class="h3 mb-3"><?= $isEdit ? 'Modifier note' : 'Nouvelle note' ?></h1>
  <form method="post" action="<?= url($isEdit ? '/notes/' . (int)$note['nte_id'] . '/update' : '/notes/store') ?>" class="card card-body">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Société</label><select name="societe_id" class="form-select"><option value="">Globale</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($note['nte_societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Type de cible</label><input class="form-control" name="cible_type" value="<?= $h($note['nte_cible_type'] ?? 'general') ?>" required></div>
      <div class="col-md-4"><label class="form-label">ID cible</label><input type="number" min="0" class="form-control" name="cible_id" value="<?= (int)($note['nte_cible_id'] ?? 0) ?>" required></div>
      <div class="col-md-8"><label class="form-label">Titre</label><input class="form-control" name="titre" maxlength="255" value="<?= $h($note['nte_titre'] ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Statut</label><select class="form-select" name="statut_id"><option value="">Automatique</option><?php foreach (($refs['statuts'] ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= (int)($note['nte_statut_id'] ?? 0)===(int)$st['sta_id']?'selected':'' ?>><?= $h($st['sta_domaine'].' / '.$st['sta_libelle']) ?></option><?php endforeach; ?></select></div>
      <div class="col-12"><label class="form-label">Contenu</label><textarea class="form-control" name="contenu" rows="8" required><?= $h($note['nte_contenu'] ?? '') ?></textarea></div>
      <div class="col-md-8"><label class="form-label">Étiquettes</label><select class="form-select" name="etiquettes[]" multiple size="6"><?php foreach (($refs['etiquettes'] ?? []) as $e): ?><option value="<?= (int)$e['eti_id'] ?>" <?= in_array((int)$e['eti_id'], $selectedEtiquettes, true)?'selected':'' ?>><?= $h($e['eti_libelle']) ?><?= !empty($e['societe_nom']) ? ' — '.$h($e['societe_nom']) : '' ?></option><?php endforeach; ?></select><div class="form-text">Maintenir Ctrl/Cmd pour sélectionner plusieurs étiquettes.</div></div>
      <div class="col-md-4 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="est_privee" value="1" id="est_privee" <?= !empty($note['nte_est_privee'])?'checked':'' ?>><label class="form-check-label" for="est_privee">Note privée</label></div></div>
      <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Enregistrer</button><a class="btn btn-outline-secondary" href="<?= url('/notes') ?>">Retour</a></div>
    </div>
  </form>
</div>
