<?php
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$exceptions = $exceptions ?? [];
$refs = $refs ?? [];
$filters = $filters ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="h3 mb-1">Exceptions horaires</h1><p class="text-muted mb-0">Fermetures exceptionnelles, jours fériés, ouvertures spéciales et adaptations ponctuelles.</p></div>
    <div class="btn-group"><a class="btn btn-primary" href="<?= url('/horaires/exceptions/create') ?>">Nouvelle exception</a><a class="btn btn-outline-secondary" href="<?= url('/horaires') ?>">Horaires</a></div>
  </div>
  <?php include __DIR__ . '/_filters.php'; ?>
  <form method="get" class="card card-body mb-3"><div class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label">Date début</label><input type="date" name="date_debut" class="form-control" value="<?= $h($filters['date_debut'] ?? '') ?>"></div><div class="col-md-3"><label class="form-label">Date fin</label><input type="date" name="date_fin" class="form-control" value="<?= $h($filters['date_fin'] ?? '') ?>"></div><div class="col-md-3"><label class="form-label">Période</label><select class="form-select" name="periode"><option value="">Toutes</option><option value="futures" <?= ($filters['periode'] ?? '')==='futures'?'selected':'' ?>>Futures</option><option value="passees" <?= ($filters['periode'] ?? '')==='passees'?'selected':'' ?>>Passées</option></select></div><div class="col-md-3"><button class="btn btn-outline-primary w-100">Filtrer les dates</button></div></div></form>
  <div class="card"><div class="table-responsive"><table class="table table-striped table-hover mb-0"><thead><tr><th>Date</th><th>Société</th><th>Portée</th><th>Ouverture</th><th>Fermeture</th><th>État</th><th>Raison</th><th></th></tr></thead><tbody>
    <?php foreach ($exceptions as $e): ?><tr><td><?= $h($e['eht_date']) ?></td><td><?= $h($e['societe_nom'] ?? '') ?></td><td><span class="badge bg-secondary"><?= $h($e['eht_portee_type']) ?></span> <?= $h($e['portee_libelle'] ?? '') ?></td><td><?= $h(substr((string)($e['eht_ouvre_a'] ?? ''),0,5) ?: '—') ?></td><td><?= $h(substr((string)($e['eht_ferme_a'] ?? ''),0,5) ?: '—') ?></td><td><?= !empty($e['eht_est_ferme']) ? '<span class="badge bg-danger">Fermé</span>' : '<span class="badge bg-success">Ouvert</span>' ?></td><td><?= $h($e['eht_raison'] ?? '') ?></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= url('/horaires/exceptions/' . (int)$e['eht_id'] . '/edit') ?>">Modifier</a></td></tr><?php endforeach; ?>
    <?php if (!$exceptions): ?><tr><td colspan="8" class="text-center text-muted py-4">Aucune exception.</td></tr><?php endif; ?>
  </tbody></table></div></div>
</div>
