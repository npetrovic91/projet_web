<?php defined('AUTOSAV_ROOT') or die;
$e = static fn(mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$idColOf = static fn(string $type): string => ['departements'=>'dep_id','secteurs'=>'sec_id','services'=>'srv_id','equipes'=>'equ_id'][$type] ?? 'id';
$labelOf = static function(array $r, string $type): string {
  $codeCol = ['departements'=>'dep_code','secteurs'=>'sec_code','services'=>'srv_code','equipes'=>'equ_code'][$type] ?? '';
  $nomCol = ['departements'=>'dep_nom','secteurs'=>'sec_nom','services'=>'srv_nom','equipes'=>'equ_nom'][$type] ?? '';
  return trim((($r[$codeCol] ?? '') !== '' ? ($r[$codeCol].' — ') : '') . ($r[$nomCol] ?? ''));
};
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-0">Liaisons hiérarchiques</h1><p class="text-muted mb-0">Relations flexibles entre départements, secteurs, services et équipes.</p></div><a class="btn btn-outline-secondary" href="/organisation">Organisation</a></div>

  <form method="get" class="card card-body mb-3"><div class="row">
    <div class="col-md-5"><label>Société</label><select class="form-control" name="societe_id"><option value="">Toutes</option><?php foreach (($societes ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= ((int)($societe_id ?? 0) === (int)$s['soc_id']) ? 'selected' : '' ?>><?= $e($s['soc_nom'] ?? '') ?></option><?php endforeach; ?></select></div>
    <div class="col-md-5"><label>Type à créer</label><select class="form-control" name="type"><?php foreach (($liaisonLabels ?? []) as $k => $v): ?><option value="<?= $e($k) ?>" <?= ($liaison_type === $k) ? 'selected' : '' ?>><?= $e($v) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Préparer</button></div>
  </div></form>

  <div class="card mb-3"><div class="card-header"><h2 class="h5 mb-0">Créer une liaison : <?= $e($liaisonLabels[$liaison_type] ?? $liaison_type) ?></h2></div><div class="card-body">
    <form method="post" action="/organisation/liaisons/store">
      <?= $csrfField ?? csrf_field() ?><input type="hidden" name="type_liaison" value="<?= $e($liaison_type) ?>">
      <div class="row">
        <div class="col-md-3 form-group"><label>Société *</label><select class="form-control" name="societe_id" required><option value="">Sélectionner</option><?php foreach (($societes ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= ((int)($societe_id ?? 0) === (int)$s['soc_id']) ? 'selected' : '' ?>><?= $e($s['soc_nom'] ?? '') ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3 form-group"><label>Parent *</label><select class="form-control" name="parent_id" required><option value="">Sélectionner</option><?php foreach (($options['parents'] ?? []) as $r): ?><option value="<?= (int)($r[$idColOf($options['parent_type'])] ?? 0) ?>"><?= $e($labelOf($r, $options['parent_type'])) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3 form-group"><label>Enfant *</label><select class="form-control" name="child_id" required><option value="">Sélectionner</option><?php foreach (($options['enfants'] ?? []) as $r): ?><option value="<?= (int)($r[$idColOf($options['child_type'])] ?? 0) ?>"><?= $e($labelOf($r, $options['child_type'])) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3 form-group"><label>Début *</label><input type="date" class="form-control" name="debute_le" value="<?= date('Y-m-d') ?>" required></div>
      </div>
      <div class="row"><div class="col-md-3 form-group"><label>Fin</label><input type="date" class="form-control" name="termine_le"></div><div class="col-md-5 form-group"><label>Statut</label><select class="form-control" name="statut_id"><option value="">Aucun</option><?php foreach (($statuts ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>"><?= $e(($st['sta_domaine'] ?? '').' / '.($st['sta_libelle'] ?? '')) ?></option><?php endforeach; ?></select></div><div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary btn-block">Créer la liaison</button></div></div>
    </form>
  </div></div>

  <div class="card"><div class="card-header"><h2 class="h5 mb-0">Liaisons actives</h2></div><div class="card-body p-0 table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Type</th><th>Société</th><th>Parent</th><th>Enfant</th><th>Période</th><th>Statut</th><th class="text-right">Actions</th></tr></thead><tbody>
    <?php foreach (($rows ?? []) as $r): $type=(string)($r['type_liaison'] ?? ''); $pkPrefix = ['departements-secteurs'=>'dse','departements-services'=>'dsv','secteurs-services'=>'ssv','services-equipes'=>'seq','equipes-services'=>'eqs'][$type] ?? ''; $id=(int)($r[$pkPrefix.'_id'] ?? 0); ?>
      <tr><td><?= $e($liaisonLabels[$type] ?? $type) ?></td><td><?= $e($r['societe_nom'] ?? '') ?></td><td><?= $e(($r['parent_code'] ? $r['parent_code'].' — ' : '').($r['parent_nom'] ?? '')) ?></td><td><?= $e(($r['enfant_code'] ? $r['enfant_code'].' — ' : '').($r['enfant_nom'] ?? '')) ?></td><td><?= $e($r[$pkPrefix.'_debute_le'] ?? '') ?> → <?= $e($r[$pkPrefix.'_termine_le'] ?? '—') ?></td><td><?= $e($r['statut_libelle'] ?? '-') ?></td><td class="text-right"><form method="post" action="/organisation/liaisons/<?= $e($type) ?>/<?= $id ?>/delete" data-confirm="Supprimer logiquement cette liaison ?"><?= $csrfField ?? csrf_field() ?><button class="btn btn-sm btn-outline-danger">Supprimer</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucune liaison.</td></tr><?php endif; ?>
  </tbody></table></div></div>
</div>
