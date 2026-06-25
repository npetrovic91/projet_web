<?php
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$refs = $refs ?? [];
$filters = $filters ?? [];
?>
<form method="get" class="card card-body mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Société</label><select class="form-select" name="societe_id"><option value="0">Toutes</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($filters['societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label">Portée</label><select class="form-select" name="portee_type"><option value="">Toutes</option><?php foreach (($refs['portees'] ?? []) as $code=>$label): ?><option value="<?= $h($code) ?>" <?= ($filters['portee_type'] ?? '')===$code?'selected':'' ?>><?= $h($label) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label">ID portée</label><input class="form-control" name="portee_id" value="<?= (int)($filters['portee_id'] ?? 0) ?>"></div>
    <div class="col-md-2"><label class="form-label">État</label><select class="form-select" name="etat"><option value="">Tous</option><option value="ouvert" <?= ($filters['etat'] ?? '')==='ouvert'?'selected':'' ?>>Ouvert</option><option value="ferme" <?= ($filters['etat'] ?? '')==='ferme'?'selected':'' ?>>Fermé</option></select></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrer</button></div>
  </div>
</form>
