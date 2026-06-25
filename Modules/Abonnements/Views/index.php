<?php defined('AUTOSAV_ROOT') or die; $h=static fn($v): string=>htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); $stats=$stats??[]; $abonnements=$abonnements??[]; $refs=$refs??[]; $filters=$filters??[]; ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="h3 mb-0">Abonnements sociétés</h1><p class="text-muted mb-0">Séparation entre société enregistrée, société abonnée, espace applicatif actif et modules activés.</p></div>
    <div class="btn-group"><a class="btn btn-primary" href="<?= url('/abonnements/create') ?>">Nouvel abonnement</a><a class="btn btn-outline-secondary" href="<?= url('/abonnements/formules') ?>">Formules</a><a class="btn btn-outline-secondary" href="<?= url('/abonnements/espaces') ?>">Espaces</a><a class="btn btn-outline-secondary" href="<?= url('/abonnements/modules-societes') ?>">Modules sociétés</a></div>
  </div>
  <form method="get" action="<?= url('/abonnements/souscrire') ?>" class="card card-body mb-3">
    <label class="form-label">Souscrire l'abonnement d'une société non abonnée (conserve son historique)</label>
    <div class="input-group">
      <select class="form-select" name="societe_id" required>
        <option value="">Sélectionner une société</option>
        <?php foreach (($refs['societes'] ?? []) as $s): ?>
          <option value="<?= (int) $s['soc_id'] ?>"><?= $h($s['soc_nom']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-success" type="submit">Souscrire</button>
    </div>
  </form>
  <div class="row g-3 mb-3">
    <?php foreach ([['Abonnements', 'abonnements_total'], ['Sociétés abonnées', 'societes_abonnees'], ['Actifs', 'abonnements_actifs'], ['Espaces actifs', 'espaces_actifs'], ['Modules sociétés', 'modules_societes_total']] as [$label,$key]): ?>
      <div class="col-md"><div class="card card-body"><div class="text-muted small"><?= $h($label) ?></div><div class="fs-4 fw-bold"><?= (int)($stats[$key] ?? 0) ?></div></div></div>
    <?php endforeach; ?>
  </div>
  <form method="get" class="card card-body mb-3"><div class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label">Recherche</label><input class="form-control" name="q" value="<?= $h($filters['q'] ?? '') ?>" placeholder="Société, formule, code"></div>
    <div class="col-md-3"><label class="form-label">Société</label><select class="form-select" name="societe_id"><option value="0">Toutes</option><?php foreach(($refs['societes']??[]) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($filters['societe_id']??0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label">Formule</label><select class="form-select" name="formule_id"><option value="0">Toutes</option><?php foreach(($refs['formules']??[]) as $f): ?><option value="<?= (int)$f['fab_id'] ?>" <?= (int)($filters['formule_id']??0)===(int)$f['fab_id']?'selected':'' ?>><?= $h($f['fab_nom']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrer</button></div>
  </div></form>
  <div class="card"><div class="table-responsive"><table class="table table-striped table-hover mb-0">
    <thead><tr><th>Société</th><th>Formule</th><th>Statut abonnement</th><th>Paiement</th><th>Début</th><th>Fin</th><th></th></tr></thead>
    <tbody><?php foreach($abonnements as $a): ?><tr><td><?= $h(($a['soc_code']??'') ? $a['soc_code'].' — '.$a['soc_nom'] : $a['soc_nom']) ?></td><td><?= $h($a['fab_nom'] ?? '—') ?></td><td><?= $h($a['statut_abonnement_libelle'] ?? '—') ?></td><td><?= $h($a['statut_paiement_libelle'] ?? '—') ?></td><td><?= $h($a['abo_debute_le'] ?? '—') ?></td><td><?= $h($a['abo_termine_le'] ?? '—') ?></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= url('/abonnements/'.(int)$a['abo_id'].'/edit') ?>">Modifier</a></td></tr><?php endforeach; ?><?php if(!$abonnements): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucun abonnement trouvé.</td></tr><?php endif; ?></tbody>
  </table></div></div>
</div>
