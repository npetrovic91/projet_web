<?php defined('AUTOSAV_ROOT') or die;
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$contacts = $contacts ?? [];
$refs = $refs ?? [];
$filters = $filters ?? [];
$stats = $stats ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-1">Contacts sociétés</h1>
      <p class="text-muted mb-0">Contacts internes ou externes rattachés aux sociétés abonnées ou non abonnées.</p>
    </div>
    <div class="btn-group">
      <a class="btn btn-primary" href="<?= url('/contacts-societes/create') ?>">Nouveau contact</a>
      <a class="btn btn-outline-secondary" href="<?= url('/contacts-societes/types') ?>">Types de contacts</a>
      <a class="btn btn-outline-secondary" href="<?= url('/contacts-societes/export.json') ?>">Export JSON</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <?php foreach ([
      'contacts_total' => 'Contacts',
      'contacts_internes' => 'Contacts internes',
      'contacts_externes' => 'Contacts externes',
      'societes_couvertes' => 'Sociétés couvertes',
    ] as $key => $label): ?>
      <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small"><?= $h($label) ?></div><div class="fs-3 fw-bold"><?= (int)($stats[$key] ?? 0) ?></div></div></div></div>
    <?php endforeach; ?>
  </div>

  <form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-3"><label class="form-label">Recherche</label><input class="form-control" name="q" value="<?= $h($filters['q'] ?? '') ?>" placeholder="Nom, prénom, email, société"></div>
      <div class="col-md-2"><label class="form-label">Société</label><select class="form-select" name="societe_id"><option value="0">Toutes</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($filters['societe_id'] ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Type</label><select class="form-select" name="type_contact_id"><option value="0">Tous</option><?php foreach (($refs['types'] ?? []) as $t): ?><option value="<?= (int)$t['tco_id'] ?>" <?= (int)($filters['type_contact_id'] ?? 0)===(int)$t['tco_id']?'selected':'' ?>><?= $h($t['tco_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Mode</label><select class="form-select" name="mode"><option value="">Tous</option><option value="interne" <?= ($filters['mode'] ?? '')==='interne'?'selected':'' ?>>Interne</option><option value="externe" <?= ($filters['mode'] ?? '')==='externe'?'selected':'' ?>>Externe</option></select></div>
      <div class="col-md-2"><label class="form-label">Statut</label><select class="form-select" name="statut_id"><option value="0">Tous</option><?php foreach (($refs['statuts'] ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= (int)($filters['statut_id'] ?? 0)===(int)$st['sta_id']?'selected':'' ?>><?= $h(($st['sta_domaine'] ?? '') . ' — ' . ($st['sta_nom'] ?? '')) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-1"><button class="btn btn-outline-primary w-100">Filtrer</button></div>
    </div>
  </form>

  <div class="card"><div class="table-responsive"><table class="table table-striped table-hover mb-0">
    <thead><tr><th>Société</th><th>Contact</th><th>Email</th><th>Mode</th><th>Type</th><th>Statut</th><th>Créé le</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($contacts as $c): ?>
        <tr>
          <td><?= $h($c['societe_nom'] ?? '') ?></td>
          <td><?= $h($c['contact_libelle'] ?? '') ?></td>
          <td><?= $h($c['contact_email'] ?? '') ?></td>
          <td><?= !empty($c['cts_utilisateur_id']) ? 'Interne' : 'Externe' ?></td>
          <td><?= $h($c['type_contact_nom'] ?? '—') ?></td>
          <td><?= $h($c['statut_nom'] ?? '—') ?></td>
          <td><?= $h($c['cts_cree_le'] ?? '') ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= url('/contacts-societes/' . (int)$c['cts_id'] . '/edit') ?>">Modifier</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$contacts): ?><tr><td colspan="8" class="text-center text-muted py-4">Aucun contact société.</td></tr><?php endif; ?>
    </tbody>
  </table></div></div>
</div>
