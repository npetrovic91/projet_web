<?php
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$groupes = $groupes ?? [];
$refs = $refs ?? [];
$jours = $refs['jours'] ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Calendriers de travail</h1><p class="text-muted mb-0">Lecture consolidée des horaires réguliers et exceptions par portée.</p></div><div class="btn-group"><a class="btn btn-outline-secondary" href="<?= url('/horaires') ?>">Horaires</a><a class="btn btn-outline-secondary" href="<?= url('/horaires/exceptions') ?>">Exceptions</a></div></div>
  <?php include __DIR__ . '/_filters.php'; ?>
  <?php foreach ($groupes as $g): ?>
    <div class="card mb-3"><div class="card-header"><strong><?= $h($g['societe_nom'] ?? '') ?></strong> — <span class="badge bg-secondary"><?= $h($g['portee_type'] ?? '') ?></span> <?= $h($g['portee_libelle'] ?? '') ?></div><div class="card-body">
      <div class="row g-2">
        <?php foreach ($jours as $num=>$label): ?><div class="col-md"><div class="border rounded p-2 h-100"><div class="fw-bold small mb-1"><?= $h($label) ?></div><?php foreach (($g['jours'][$num] ?? []) as $htr): ?><div class="small"><?= !empty($htr['htr_est_ferme']) ? 'Fermé' : $h(substr((string)$htr['htr_ouvre_a'],0,5) . ' - ' . substr((string)$htr['htr_ferme_a'],0,5)) ?></div><?php endforeach; ?><?php if (empty($g['jours'][$num])): ?><div class="text-muted small">—</div><?php endif; ?></div></div><?php endforeach; ?>
      </div>
      <?php if (!empty($g['exceptions'])): ?><hr><div class="fw-bold small mb-2">Exceptions</div><ul class="mb-0"><?php foreach ($g['exceptions'] as $e): ?><li><?= $h($e['eht_date']) ?> — <?= !empty($e['eht_est_ferme']) ? 'Fermé' : $h(substr((string)$e['eht_ouvre_a'],0,5) . ' - ' . substr((string)$e['eht_ferme_a'],0,5)) ?> <?= $h($e['eht_raison'] ? ' — ' . $e['eht_raison'] : '') ?></li><?php endforeach; ?></ul><?php endif; ?>
    </div></div>
  <?php endforeach; ?>
  <?php if (!$groupes): ?><div class="alert alert-info">Aucun calendrier trouvé avec ces filtres.</div><?php endif; ?>
</div>
