<?php defined('AUTOSAV_ROOT') or die;
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$stats = $data['stats'] ?? [];
$cards = [
  ['Pays', 'pays', $stats['pays'] ?? 0, 'Codes ISO2 / ISO3 et activation pays.'],
  ['Devises', 'devises', $stats['devises'] ?? 0, 'Codes ISO, symboles et décimales.'],
  ['Fuseaux horaires', 'fuseaux', $stats['fuseaux'] ?? 0, 'Noms IANA et libellés.'],
  ['Taux de TVA', 'tva', $stats['tva'] ?? 0, 'Taux par pays et période de validité.'],
  ['Statuts', 'statuts', $stats['statuts'] ?? 0, 'Statuts par domaine et table métier.'],
  ['Transitions', 'transitions', $stats['transitions'] ?? 0, 'Passages autorisés entre statuts.'],
];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-0">Référentiels système</h1>
      <p class="text-muted mb-0">Gestion centralisée des pays, devises, fuseaux horaires, TVA, statuts et transitions.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/referentiels/export.json">Exporter JSON</a>
  </div>

  <div class="row">
    <?php foreach ($cards as [$label, $route, $count, $desc]): ?>
      <div class="col-md-4 mb-3">
        <div class="card h-100">
          <div class="card-body">
            <h2 class="h5 mb-1"><?= $e($label) ?></h2>
            <div class="display-4"><?= (int) $count ?></div>
            <p class="text-muted mb-3"><?= $e($desc) ?></p>
            <a class="btn btn-primary btn-sm" href="/referentiels/<?= $e($route) ?>">Ouvrir</a>
            <a class="btn btn-outline-primary btn-sm" href="/referentiels/<?= $e($route) ?>/create">Créer</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header"><h3 class="card-title">Domaines de statuts</h3></div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm mb-0">
        <thead><tr><th>Domaine</th><th>Total</th><th>Accès</th></tr></thead>
        <tbody>
        <?php foreach (($data['domaines_statuts'] ?? []) as $domain): $name = (string)($domain['domaine'] ?? ''); ?>
          <tr>
            <td><code><?= $e($name) ?></code></td>
            <td><?= (int)($domain['total'] ?? 0) ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="/referentiels/statuts?domaine=<?= urlencode($name) ?>">Voir les statuts</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($data['domaines_statuts'])): ?><tr><td colspan="3" class="text-center text-muted py-4">Aucun domaine de statut.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
