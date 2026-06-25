<?php
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$stats = $stats ?? [];
$horaires = $horaires ?? [];
$exceptions = $exceptions ?? [];
$refs = $refs ?? [];
$jours = $refs['jours'] ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-1">Horaires de travail</h1>
      <p class="text-muted mb-0">Calendriers par société, département, service, équipe ou utilisateur.</p>
    </div>
    <div class="btn-group">
      <a class="btn btn-primary" href="<?= url('/horaires/create') ?>">Nouvel horaire</a>
      <a class="btn btn-outline-secondary" href="<?= url('/horaires/exceptions') ?>">Exceptions</a>
      <a class="btn btn-outline-secondary" href="<?= url('/horaires/calendrier') ?>">Calendrier</a>
      <a class="btn btn-outline-secondary" href="<?= url('/horaires/export.json') ?>">Export JSON</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <?php foreach (['horaires_total'=>'Horaires', 'horaires_fermes'=>'Jours fermés', 'exceptions_total'=>'Exceptions', 'exceptions_futures'=>'Exceptions futures'] as $key=>$label): ?>
      <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small"><?= $h($label) ?></div><div class="fs-3 fw-bold"><?= (int)($stats[$key] ?? 0) ?></div></div></div></div>
    <?php endforeach; ?>
  </div>

  <?php include __DIR__ . '/_filters.php'; ?>

  <div class="card mb-3"><div class="card-header fw-bold">Horaires réguliers</div><div class="table-responsive"><table class="table table-striped table-hover mb-0">
    <thead><tr><th>Société</th><th>Portée</th><th>Jour</th><th>Ouverture</th><th>Fermeture</th><th>État</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($horaires as $row): ?>
        <tr>
          <td><?= $h($row['societe_nom'] ?? '') ?></td>
          <td><span class="badge bg-secondary"><?= $h($row['htr_portee_type']) ?></span> <?= $h($row['portee_libelle'] ?? 'Société entière') ?></td>
          <td><?= $h($jours[(int)$row['htr_jour_semaine']] ?? $row['htr_jour_semaine']) ?></td>
          <td><?= $h(substr((string)($row['htr_ouvre_a'] ?? ''), 0, 5) ?: '—') ?></td>
          <td><?= $h(substr((string)($row['htr_ferme_a'] ?? ''), 0, 5) ?: '—') ?></td>
          <td><?= !empty($row['htr_est_ferme']) ? '<span class="badge bg-danger">Fermé</span>' : '<span class="badge bg-success">Ouvert</span>' ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= url('/horaires/' . (int)$row['htr_id'] . '/edit') ?>">Modifier</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$horaires): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucun horaire.</td></tr><?php endif; ?>
    </tbody>
  </table></div></div>

  <div class="card"><div class="card-header fw-bold">Prochaines exceptions</div><div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Date</th><th>Société</th><th>Portée</th><th>Horaires</th><th>Raison</th></tr></thead>
    <tbody><?php foreach ($exceptions as $e): ?><tr><td><?= $h($e['eht_date']) ?></td><td><?= $h($e['societe_nom'] ?? '') ?></td><td><?= $h($e['eht_portee_type']) ?> — <?= $h($e['portee_libelle'] ?? '') ?></td><td><?= !empty($e['eht_est_ferme']) ? 'Fermé' : $h(substr((string)$e['eht_ouvre_a'],0,5) . ' - ' . substr((string)$e['eht_ferme_a'],0,5)) ?></td><td><?= $h($e['eht_raison'] ?? '') ?></td></tr><?php endforeach; ?><?php if (!$exceptions): ?><tr><td colspan="5" class="text-center text-muted py-3">Aucune exception future.</td></tr><?php endif; ?></tbody>
  </table></div></div>
</div>
