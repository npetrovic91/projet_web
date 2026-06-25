<?php
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$stats = $stats ?? [];
$verrous = $verrous ?? [];
$sessions = $sessions ?? [];
$contextes = $contextes ?? [];
$maintenance = $maintenance ?? [];
$refs = $refs ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-1">Verrous et concurrence</h1>
      <p class="text-muted mb-0">Suivi des verrous d’entités, sessions actives, changements de contexte et verrous de maintenance.</p>
    </div>
    <div class="btn-group">
      <a class="btn btn-primary" href="<?= url('/verrous/create') ?>">Créer un verrou</a>
      <a class="btn btn-outline-secondary" href="<?= url('/verrous/sessions') ?>">Sessions</a>
      <a class="btn btn-outline-secondary" href="<?= url('/verrous/contextes') ?>">Contextes</a>
      <a class="btn btn-outline-secondary" href="<?= url('/verrous/maintenance') ?>">Maintenance</a>
      <a class="btn btn-outline-secondary" href="<?= url('/verrous/export.json') ?>">Export JSON</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <?php foreach (['verrous_actifs'=>'Verrous actifs','verrous_expires'=>'Verrous expirés','sessions_actives'=>'Sessions actives','contextes_24h'=>'Contextes 24 h','maintenance_active'=>'Maintenances verrouillées'] as $key=>$label): ?>
      <div class="col-md"><div class="card"><div class="card-body"><div class="text-muted small"><?= $h($label) ?></div><div class="fs-3 fw-bold"><?= (int)($stats[$key] ?? 0) ?></div></div></div></div>
    <?php endforeach; ?>
  </div>

  <?php include __DIR__ . '/_filters.php'; ?>

  <div class="card mb-3"><div class="card-header fw-bold">Verrous d’entités</div><div class="table-responsive"><table class="table table-striped table-hover mb-0">
    <thead><tr><th>Cible</th><th>Utilisateur</th><th>Société session</th><th>Verrouillé le</th><th>Expire le</th><th>État</th><th>Raison</th><th></th></tr></thead><tbody>
    <?php foreach ($verrous as $v): ?>
      <?php $actif = empty($v['ven_libere_le']) && strtotime((string)$v['ven_expire_le']) > time(); $expire = empty($v['ven_libere_le']) && !$actif; ?>
      <tr>
        <td><span class="badge bg-secondary"><?= $h($v['ven_cible_type']) ?></span> #<?= (int)$v['ven_cible_id'] ?></td>
        <td><?= $h(trim(($v['pui_prenom'] ?? '') . ' ' . ($v['pui_nom'] ?? '')) ?: $v['uti_email_normalise']) ?></td>
        <td><?= $h($v['societe_active_nom'] ?? '—') ?></td>
        <td><?= $h($v['ven_verrouille_le']) ?></td>
        <td><?= $h($v['ven_expire_le']) ?></td>
        <td><?= $actif ? '<span class="badge bg-warning text-dark">Actif</span>' : ($expire ? '<span class="badge bg-secondary">Expiré</span>' : '<span class="badge bg-success">Libéré</span>') ?></td>
        <td><?= $h($v['ven_raison'] ?? '') ?></td>
        <td class="text-end"><?php if (empty($v['ven_libere_le'])): ?><form method="post" action="<?= url('/verrous/' . (int)$v['ven_id'] . '/release') ?>" class="d-inline"><?= $csrfField ?? csrf_field() ?><button class="btn btn-sm btn-outline-success">Libérer</button></form><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$verrous): ?><tr><td colspan="8" class="text-center text-muted py-4">Aucun verrou.</td></tr><?php endif; ?>
  </tbody></table></div></div>

  <div class="row g-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header fw-bold">Dernières sessions</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Utilisateur</th><th>Société</th><th>Dernière activité</th><th>Expire</th></tr></thead><tbody><?php foreach (array_slice($sessions,0,8) as $s): ?><tr><td><?= $h(trim(($s['pui_prenom'] ?? '') . ' ' . ($s['pui_nom'] ?? '')) ?: $s['uti_email_normalise']) ?></td><td><?= $h($s['societe_active_nom'] ?? '—') ?></td><td><?= $h($s['seu_derniere_activite_le']) ?></td><td><?= $h($s['seu_expire_le']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header fw-bold">Derniers contextes</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Utilisateur</th><th>Action</th><th>Société</th><th>Date</th></tr></thead><tbody><?php foreach (array_slice($contextes,0,8) as $c): ?><tr><td><?= $h(trim(($c['pui_prenom'] ?? '') . ' ' . ($c['pui_nom'] ?? '')) ?: $c['uti_email_normalise']) ?></td><td><?= $h($c['hcu_action']) ?></td><td><?= $h($c['societe_nom'] ?? '—') ?></td><td><?= $h($c['hcu_cree_le']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
  </div>
</div>
