<?php defined('AUTOSAV_ROOT') or die;
$e = static fn(mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$idCol = ['departements'=>'dep_id','secteurs'=>'sec_id','services'=>'srv_id','equipes'=>'equ_id'][$type] ?? 'id';
$codeCol = ['departements'=>'dep_code','secteurs'=>'sec_code','services'=>'srv_code','equipes'=>'equ_code'][$type] ?? 'code';
$nomCol = ['departements'=>'dep_nom','secteurs'=>'sec_nom','services'=>'srv_nom','equipes'=>'equ_nom'][$type] ?? 'nom';
$descCol = ['departements'=>'dep_description','secteurs'=>'sec_description','services'=>'srv_description','equipes'=>'equ_description'][$type] ?? 'description';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="h3 mb-0"><?= $e($title) ?></h1><p class="text-muted mb-0">Structures internes alignées sur la base SQL actuelle.</p></div>
    <div><a class="btn btn-outline-secondary" href="/organisation">Organisation</a> <a class="btn btn-primary" href="/organisation/<?= $e($type) ?>/create">Créer</a></div>
  </div>
  <form method="get" class="card card-body mb-3"><div class="row">
    <div class="col-md-5"><label>Recherche</label><input class="form-control" name="q" value="<?= $e($filters['q'] ?? '') ?>" placeholder="Code, nom ou description"></div>
    <div class="col-md-5"><label>Société</label><select class="form-control" name="societe_id"><option value="">Toutes</option><?php foreach (($societes ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= ((int)($filters['societe_id'] ?? 0) === (int)$s['soc_id']) ? 'selected' : '' ?>><?= $e($s['soc_nom'] ?? '') ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Filtrer</button></div>
  </div></form>
  <div class="card"><div class="card-body p-0 table-responsive"><table class="table table-sm table-hover mb-0">
    <thead><tr><th>Société</th><th>Code</th><th>Nom</th><th>Description</th><th>Statut</th><th>Modifié</th><th class="text-right">Actions</th></tr></thead><tbody>
    <?php foreach (($rows ?? []) as $r): $id=(int)($r[$idCol] ?? 0); ?>
      <tr><td><?= $e($r['societe_nom'] ?? '') ?></td><td><code><?= $e($r[$codeCol] ?? '') ?></code></td><td><strong><?= $e($r[$nomCol] ?? '') ?></strong></td><td><?= $e($r[$descCol] ?? '') ?></td><td><?= $e($r['statut_libelle'] ?? '-') ?></td><td><?= $e($r[str_replace('_id','_modifie_le',$idCol)] ?? $r[str_replace('_id','_cree_le',$idCol)] ?? '') ?></td><td class="text-right"><a class="btn btn-sm btn-outline-primary" href="/organisation/<?= $e($type) ?>/<?= $id ?>/edit">Modifier</a><form method="post" action="/organisation/<?= $e($type) ?>/<?= $id ?>/delete" class="d-inline" data-confirm="Supprimer logiquement cette structure ?"><?= $csrfField ?? csrf_field() ?><button class="btn btn-sm btn-outline-danger">Supprimer</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucune structure.</td></tr><?php endif; ?>
    </tbody></table></div></div>
</div>
