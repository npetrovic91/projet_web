<?php defined('AUTOSAV_ROOT') or die;
$e = static fn(mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$stats = $data['stats'] ?? [];
$cards = [
  ['Départements', 'departements', $stats['departements'] ?? 0, 'Niveau organisationnel supérieur.'],
  ['Secteurs', 'secteurs', $stats['secteurs'] ?? 0, 'Niveau intermédiaire facultatif.'],
  ['Services', 'services', $stats['services'] ?? 0, 'Services opérationnels et administratifs.'],
  ['Équipes', 'equipes', $stats['equipes'] ?? 0, 'Équipes de travail rattachables aux services.'],
];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-0">Organisation interne</h1>
      <p class="text-muted mb-0">Départements, secteurs, services, équipes et liaisons hiérarchiques par société.</p>
    </div>
    <div>
      <a class="btn btn-outline-secondary" href="/organisation/export.json<?= $societe_id ? '?societe_id='.(int)$societe_id : '' ?>">Exporter JSON</a>
      <a class="btn btn-primary" href="/organisation/liaisons<?= $societe_id ? '?societe_id='.(int)$societe_id : '' ?>">Liaisons</a>
    </div>
  </div>

  <form method="get" class="card card-body mb-3">
    <div class="row">
      <div class="col-md-8">
        <label>Société</label>
        <select class="form-control" name="societe_id">
          <option value="">Toutes les sociétés</option>
          <?php foreach (($societes ?? []) as $s): ?>
            <option value="<?= (int)$s['soc_id'] ?>" <?= ((int)($societe_id ?? 0) === (int)$s['soc_id']) ? 'selected' : '' ?>><?= $e(($s['soc_code'] ? $s['soc_code'].' — ' : '').$s['soc_nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary btn-block">Filtrer</button></div>
    </div>
  </form>

  <div class="row">
    <?php foreach ($cards as [$label, $route, $count, $desc]): ?>
      <div class="col-md-3 mb-3"><div class="card h-100"><div class="card-body">
        <h2 class="h5 mb-1"><?= $e($label) ?></h2>
        <div class="display-4"><?= (int)$count ?></div>
        <p class="text-muted"><?= $e($desc) ?></p>
        <a class="btn btn-sm btn-primary" href="/organisation/<?= $e($route) ?><?= $societe_id ? '?societe_id='.(int)$societe_id : '' ?>">Ouvrir</a>
        <a class="btn btn-sm btn-outline-primary" href="/organisation/<?= $e($route) ?>/create">Créer</a>
      </div></div></div>
    <?php endforeach; ?>
  </div>

  <div class="row mb-3">
    <div class="col-md-6"><div class="card"><div class="card-body"><h3 class="h5">Liaisons actives</h3><div class="display-4"><?= (int)($stats['liaisons'] ?? 0) ?></div><a href="/organisation/liaisons<?= $societe_id ? '?societe_id='.(int)$societe_id : '' ?>">Gérer les liaisons</a></div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-body"><h3 class="h5">Affectations utilisateurs</h3><div class="display-4"><?= (int)($stats['affectations_utilisateurs'] ?? 0) ?></div><p class="text-muted mb-0">Départements, services et équipes déjà reliés aux utilisateurs.</p></div></div></div>
  </div>

  <div class="card">
    <div class="card-header"><h3 class="card-title">Vue synthétique par société</h3></div>
    <div class="card-body table-responsive p-0">
      <table class="table table-sm table-hover mb-0">
        <thead><tr><th>Société</th><th>Départements</th><th>Secteurs</th><th>Services</th><th>Équipes</th></tr></thead>
        <tbody>
        <?php foreach (($data['organigramme'] ?? []) as $bloc): ?>
          <tr>
            <td><strong><?= $e($bloc['societe']['soc_nom'] ?? '') ?></strong><br><small class="text-muted"><?= $e($bloc['societe']['soc_code'] ?? '') ?></small></td>
            <td><?= count($bloc['departements'] ?? []) ?></td>
            <td><?= count($bloc['secteurs'] ?? []) ?></td>
            <td><?= count($bloc['services'] ?? []) ?></td>
            <td><?= count($bloc['equipes'] ?? []) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($data['organigramme'])): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucune structure interne.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
