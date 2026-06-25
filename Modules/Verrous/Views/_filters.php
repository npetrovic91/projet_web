<?php
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$refs = $refs ?? [];
$filters = $filters ?? [];
?>
<form method="get" class="card card-body mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Recherche</label><input class="form-control" name="q" value="<?= $h($filters['q'] ?? '') ?>" placeholder="Utilisateur, cible, raison"></div>
    <div class="col-md-2"><label class="form-label">Type cible</label><select class="form-select" name="cible_type"><option value="">Tous</option><?php foreach (($refs['cibles'] ?? []) as $code=>$label): ?><option value="<?= $h($code) ?>" <?= ($filters['cible_type'] ?? '')===$code?'selected':'' ?>><?= $h($label) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label">Utilisateur</label><select class="form-select" name="utilisateur_id"><option value="0">Tous</option><?php foreach (($refs['utilisateurs'] ?? []) as $u): ?><option value="<?= (int)$u['uti_id'] ?>" <?= (int)($filters['utilisateur_id'] ?? 0)===(int)$u['uti_id']?'selected':'' ?>><?= $h(trim(($u['pui_prenom'] ?? '') . ' ' . ($u['pui_nom'] ?? '')) ?: $u['uti_email_normalise']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label">État verrou</label><select class="form-select" name="etat"><option value="">Tous</option><?php foreach (($refs['etats'] ?? []) as $code=>$label): ?><option value="<?= $h($code) ?>" <?= ($filters['etat'] ?? '')===$code?'selected':'' ?>><?= $h($label) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrer</button></div>
  </div>
</form>
