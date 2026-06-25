<?php defined('AUTOSAV_ROOT') or die;
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmtValue = static function (array $row) use ($e): string {
    if (!empty($row['pap_est_secret'])) {
        return '<span class="badge badge-dark">secret masqué</span>';
    }
    $decoded = json_decode((string) ($row['pap_valeur_json'] ?? ''), true);
    $value = is_array($decoded) && array_key_exists('valeur', $decoded) ? $decoded['valeur'] : null;
    if (is_bool($value)) {
        return $value ? '<span class="badge badge-success">true</span>' : '<span class="badge badge-secondary">false</span>';
    }
    if (is_array($value)) {
        return '<code>' . $e(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</code>';
    }
    return '<code>' . $e($value) . '</code>';
};
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-0">Paramètres application</h1>
      <p class="text-muted mb-0">Configuration persistée en base dans <code>sav_parametres_application</code>.</p>
    </div>
    <div>
      <a class="btn btn-outline-secondary" href="/settings/export.json">Exporter JSON</a>
      <a class="btn btn-outline-primary" href="/settings/system">Configuration système</a>
      <a class="btn btn-primary" href="/settings/create">Nouveau paramètre</a>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($stats['total'] ?? 0) ?></h3><p>Paramètres</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($stats['domaines'] ?? 0) ?></h3><p>Domaines</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($stats['systeme'] ?? 0) ?></h3><p>Système</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($stats['secrets'] ?? 0) ?></h3><p>Secrets</p></div></div></div>
  </div>

  <div class="card card-outline card-info mb-3">
    <div class="card-header"><h3 class="card-title">Recherche</h3></div>
    <div class="card-body">
      <form method="get" action="/settings" class="row g-2">
        <div class="col-md-3">
          <label>Domaine</label>
          <select class="form-control" name="domain">
            <option value="">Tous</option>
            <?php foreach (($domains ?? []) as $domain): $name = (string)($domain['domaine'] ?? ''); ?>
              <option value="<?= $e($name) ?>" <?= (($filters['domain'] ?? '') === $name) ? 'selected' : '' ?>><?= $e($name) ?> (<?= (int)($domain['total'] ?? 0) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-7">
          <label>Recherche</label>
          <input class="form-control" name="q" value="<?= $e($filters['q'] ?? '') ?>" placeholder="Domaine, clé ou description">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-primary btn-block">Filtrer</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th>Domaine</th><th>Clé</th><th>Valeur</th><th>Statut</th><th>Type</th><th>Modifié</th><th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach (($settings ?? []) as $row): ?>
          <tr>
            <td><span class="badge badge-light"><?= $e($row['pap_domaine'] ?? '') ?></span></td>
            <td><code><?= $e($row['pap_cle'] ?? '') ?></code><br><small class="text-muted"><?= $e($row['pap_description'] ?? '') ?></small></td>
            <td style="max-width:360px;white-space:normal"><?= $fmtValue($row) ?></td>
            <td><?= $e($row['statut_libelle'] ?? '-') ?></td>
            <td>
              <?= !empty($row['pap_est_secret']) ? '<span class="badge badge-dark">secret</span>' : '' ?>
              <?= !empty($row['pap_est_systeme']) ? '<span class="badge badge-primary">système</span>' : '<span class="badge badge-secondary">client</span>' ?>
            </td>
            <td><?= $e($row['pap_modifie_le'] ?? $row['pap_cree_le'] ?? '') ?></td>
            <td class="text-right">
              <a class="btn btn-sm btn-outline-primary" href="/settings/<?= (int)$row['pap_id'] ?>/edit">Modifier</a>
              <form method="post" action="/settings/<?= (int)$row['pap_id'] ?>/delete" class="d-inline" data-confirm="Supprimer logiquement ce paramètre ?">
                <input type="hidden" name="_csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                <button class="btn btn-sm btn-outline-danger">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($settings)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Aucun paramètre trouvé.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
