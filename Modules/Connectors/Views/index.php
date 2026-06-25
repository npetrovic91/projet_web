<?php
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$stats = $stats ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Connecteurs / API keys / Webhooks</h1>
    <div class="btn-group">
      <a class="btn btn-primary" href="/connectors/create">Nouveau connecteur</a>
      <a class="btn btn-outline-secondary" href="/connectors/api-keys">Clés API</a>
      <a class="btn btn-outline-secondary" href="/connectors/webhooks">Webhooks</a>
      <a class="btn btn-outline-secondary" href="/connectors/events">Événements</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Connecteurs</div><div class="display-6"><?= (int)($stats['connecteurs'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Clés API actives</div><div class="display-6"><?= (int)($stats['cles_api'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Webhooks 24h</div><div class="display-6"><?= (int)($stats['webhooks_24h'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Événements 24h</div><div class="display-6"><?= (int)($stats['evenements_24h'] ?? 0) ?></div></div></div></div>
  </div>

  <form class="card card-body mb-3" method="get" action="/connectors">
    <div class="row g-2 align-items-end">
      <div class="col-md-5"><label class="form-label">Recherche</label><input class="form-control" name="q" value="<?= $h($filters['q'] ?? '') ?>" placeholder="code, nom, type"></div>
      <div class="col-md-3"><label class="form-label">Type</label><input class="form-control" name="type" value="<?= $h($filters['type'] ?? '') ?>" placeholder="webhook, api, erp..."></div>
      <div class="col-md-2"><button class="btn btn-secondary w-100">Filtrer</button></div>
      <div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="/connectors">Réinitialiser</a></div>
    </div>
  </form>

  <div class="card mb-4">
    <div class="card-header">Connecteurs externes et inter-applicatifs</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead><tr><th>Code</th><th>Nom</th><th>Type</th><th>Société</th><th>Module</th><th>Clés</th><th>Dernier webhook</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach (($connecteurs ?? []) as $c): ?>
          <tr>
            <td><code><?= $h($c['con_code'] ?? '') ?></code></td>
            <td><?= $h($c['con_nom'] ?? '') ?></td>
            <td><?= $h($c['con_type'] ?? '') ?></td>
            <td><?= $h($c['soc_nom'] ?? 'Global') ?></td>
            <td><?= $h($c['mod_nom'] ?? '-') ?></td>
            <td><?= (int)($c['total_cles_api'] ?? 0) ?></td>
            <td><?= $h($c['dernier_webhook_le'] ?? '-') ?></td>
            <td><?= $h($c['statut_libelle'] ?? $c['statut_code'] ?? '-') ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/connectors/<?= (int)$c['con_id'] ?>/edit">Modifier</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($connecteurs)): ?><tr><td colspan="9" class="text-center text-muted">Aucun connecteur.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card h-100"><div class="card-header">Connecteurs inter-modules</div><div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Source</th><th>Cible</th><th>Code</th><th>Nom</th></tr></thead><tbody>
        <?php foreach (($connecteurs_modules ?? []) as $cm): ?>
          <tr><td><?= $h($cm['module_source_nom'] ?? '') ?></td><td><?= $h($cm['module_cible_nom'] ?? 'Externe') ?></td><td><code><?= $h($cm['cmo_code'] ?? '') ?></code></td><td><?= $h($cm['cmo_nom'] ?? '') ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($connecteurs_modules)): ?><tr><td colspan="4" class="text-center text-muted">Aucun connecteur inter-module.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100"><div class="card-header">Derniers webhooks</div><div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Date</th><th>Connecteur</th><th>URL</th><th>HTTP</th><th>Succès</th></tr></thead><tbody>
        <?php foreach (array_slice(($webhooks ?? []), 0, 10) as $w): ?>
          <tr><td><?= $h($w['jwh_cree_le'] ?? '') ?></td><td><?= $h($w['con_nom'] ?? '-') ?></td><td class="text-truncate" style="max-width:240px"><?= $h($w['jwh_url'] ?? '') ?></td><td><?= $h($w['jwh_code_http'] ?? '-') ?></td><td><?= isset($w['jwh_succes']) ? ((int)$w['jwh_succes'] ? 'Oui' : 'Non') : '-' ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($webhooks)): ?><tr><td colspan="5" class="text-center text-muted">Aucun journal webhook.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
  </div>
</div>
