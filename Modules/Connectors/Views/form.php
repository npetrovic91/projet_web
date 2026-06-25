<?php
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$isEdit = !empty($connecteur);
$action = $isEdit ? '/connectors/' . (int)$connecteur['con_id'] . '/update' : '/connectors/store';
$config = $connecteur['con_configuration_json'] ?? "{}";
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Modifier connecteur' : 'Nouveau connecteur' ?></h1>
    <a class="btn btn-outline-secondary" href="/connectors">Retour</a>
  </div>
  <form class="card card-body" method="post" action="<?= $h($action) ?>">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" required value="<?= $h($connecteur['con_code'] ?? '') ?>"></div>
      <div class="col-md-5"><label class="form-label">Nom</label><input class="form-control" name="nom" required value="<?= $h($connecteur['con_nom'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Type</label><input class="form-control" name="type" value="<?= $h($connecteur['con_type'] ?? 'webhook') ?>"></div>
      <div class="col-md-4"><label class="form-label">Module</label><select class="form-select" name="module_id"><option value="">Global</option><?php foreach (($refs['modules'] ?? []) as $m): ?><option value="<?= (int)$m['mod_id'] ?>" <?= (int)($connecteur['con_module_id'] ?? 0)===(int)$m['mod_id']?'selected':'' ?>><?= $h($m['mod_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Société</label><select class="form-select" name="societe_id"><option value="">Toutes</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($connecteur['con_societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Statut</label><select class="form-select" name="statut_id"><option value="">Aucun</option><?php foreach (($refs['statuts'] ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= (int)($connecteur['con_statut_id'] ?? 1)===(int)$st['sta_id']?'selected':'' ?>><?= $h($st['sta_domaine'].' / '.$st['sta_libelle']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-8"><label class="form-label">Configuration JSON</label><textarea class="form-control font-monospace" name="configuration_json" rows="10"><?= $h($config ?: '{}') ?></textarea></div>
      <div class="col-md-4"><label class="form-label">Secret</label><input class="form-control" name="secret" type="password" placeholder="Laisser vide pour conserver"><div class="form-text">Le secret est hashé/masqué, jamais affiché en clair.</div></div>
    </div>
    <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Enregistrer</button></div>
  </form>
  <?php if ($isEdit): ?>
  <form class="mt-3" method="post" action="/connectors/<?= (int)$connecteur['con_id'] ?>/delete" data-confirm="Supprimer logiquement ce connecteur ?">
    <?= $csrfField ?? csrf_field() ?><button class="btn btn-outline-danger">Supprimer logiquement</button>
  </form>
  <?php endif; ?>
</div>
