<?php
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$isEdit = !empty($regle['rva_id']);
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0"><?= $isEdit ? 'Modifier règle de validation' : 'Nouvelle règle de validation' ?></h1><a class="btn btn-outline-secondary" href="/validation-rules">Retour</a></div>
  <form class="card card-body" method="post" action="<?= $isEdit ? '/validation-rules/' . (int)$regle['rva_id'] . '/update' : '/validation-rules/store' ?>">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Code</label><input class="form-control" name="rva_code" required value="<?= $h($regle['rva_code'] ?? '') ?>"></div>
      <div class="col-md-5"><label class="form-label">Nom</label><input class="form-control" name="rva_nom" required value="<?= $h($regle['rva_nom'] ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Type opération</label><select class="form-select" name="rva_type_operation"><?php foreach (($refs['types_operation'] ?? []) as $t): ?><option value="<?= $h($t) ?>" <?= (($regle['rva_type_operation'] ?? '') === $t) ? 'selected' : '' ?>><?= $h($t) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Module</label><select class="form-select" name="rva_module_id"><option value="">Tous modules</option><?php foreach (($refs['modules'] ?? []) as $m): ?><option value="<?= (int)$m['mod_id'] ?>" <?= ((int)($regle['rva_module_id'] ?? 0) === (int)$m['mod_id']) ? 'selected' : '' ?>><?= $h($m['mod_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Société</label><select class="form-select" name="rva_societe_id"><option value="">Globale</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= ((int)($regle['rva_societe_id'] ?? 0) === (int)$s['soc_id']) ? 'selected' : '' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Validateurs requis</label><input class="form-control" type="number" min="1" max="9" name="rva_nombre_validateurs_requis" value="<?= (int)($regle['rva_nombre_validateurs_requis'] ?? 1) ?>"></div>
      <div class="col-md-2"><label class="form-label">Statut</label><select class="form-select" name="rva_statut_id"><option value="">Actif</option><?php foreach (($refs['statuts'] ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= ((int)($regle['rva_statut_id'] ?? 0) === (int)$st['sta_id']) ? 'selected' : '' ?>><?= $h($st['sta_libelle']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Permission validateur</label><select class="form-select" name="rva_permission_validateur_code"><option value="">Aucune permission spécifique</option><?php foreach (($refs['permissions'] ?? []) as $p): ?><option value="<?= $h($p['per_code']) ?>" <?= (($regle['rva_permission_validateur_code'] ?? '') === $p['per_code']) ? 'selected' : '' ?>><?= $h($p['per_code']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-12"><label class="form-label">Conditions JSON</label><textarea class="form-control font-monospace" name="rva_conditions_json" rows="8" placeholder='{"montant_min": 1000, "service": "atelier"}'><?= $h($regle['rva_conditions_json'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Enregistrer</button></div>
  </form>
  <?php if ($isEdit): ?><form class="mt-3" method="post" action="/validation-rules/<?= (int)$regle['rva_id'] ?>/delete" data-confirm="Supprimer cette règle ?"><?= $csrfField ?? csrf_field() ?><button class="btn btn-outline-danger">Supprimer logiquement</button></form><?php endif; ?>
</div>
