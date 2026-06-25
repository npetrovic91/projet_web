<?php
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$stats = $stats ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Standards / versions / exigences</h1>
    <div class="btn-group">
      <a class="btn btn-primary" href="/standards/create">Nouveau standard</a>
      <a class="btn btn-outline-secondary" href="/standards/versions">Versions</a>
      <a class="btn btn-outline-secondary" href="/standards/exigences">Exigences</a>
      <a class="btn btn-outline-secondary" href="/standards/export.json">Export JSON</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Standards</div><div class="display-6"><?= (int)($stats['standards'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Versions actives</div><div class="display-6"><?= (int)($stats['versions_actives'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Exigences</div><div class="display-6"><?= (int)($stats['exigences'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Obligatoires</div><div class="display-6"><?= (int)($stats['exigences_obligatoires'] ?? 0) ?></div></div></div></div>
  </div>

  <form class="card card-body mb-3" method="get" action="/standards">
    <div class="row g-2 align-items-end">
      <div class="col-md-5"><label class="form-label">Recherche</label><input class="form-control" name="q" value="<?= $h($filters['q'] ?? '') ?>" placeholder="code, nom, type"></div>
      <div class="col-md-3"><label class="form-label">Type</label><input class="form-control" name="type" value="<?= $h($filters['type'] ?? '') ?>" placeholder="constructeur, importateur..."></div>
      <div class="col-md-2"><button class="btn btn-secondary w-100">Filtrer</button></div>
      <div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="/standards">Réinitialiser</a></div>
    </div>
  </form>

  <div class="card">
    <div class="card-header">Standards</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead><tr><th>Code</th><th>Nom</th><th>Type</th><th>Société propriétaire</th><th>Versions</th><th>Dernière version</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          <?php foreach (($standards ?? []) as $s): ?>
            <tr>
              <td><code><?= $h($s['std_code'] ?? '') ?></code></td>
              <td><?= $h($s['std_nom'] ?? '') ?></td>
              <td><?= $h($s['std_type_standard'] ?? '') ?></td>
              <td><?= $h($s['societe_proprietaire_nom'] ?? 'Global') ?></td>
              <td><?= (int)($s['total_versions'] ?? 0) ?></td>
              <td><?= $h($s['derniere_version_valide_du'] ?? '-') ?></td>
              <td><?= $h($s['statut_libelle'] ?? $s['statut_code'] ?? '-') ?></td>
              <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/standards/<?= (int)$s['std_id'] ?>/edit">Modifier</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($standards)): ?><tr><td colspan="8" class="text-center text-muted">Aucun standard.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
