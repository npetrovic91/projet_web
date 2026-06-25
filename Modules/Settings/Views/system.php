<?php defined('AUTOSAV_ROOT') or die;
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$list = static fn(array $items): string => implode("\n", array_map('strval', $items));
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-0">Configuration système</h1>
      <p class="text-muted mb-0">Synthèse des paramètres applicatifs et pilotage du mode maintenance.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/settings">Tous les paramètres</a>
  </div>

  <div class="row mb-3">
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($stats['total'] ?? 0) ?></h3><p>Paramètres</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($stats['domaines'] ?? 0) ?></h3><p>Domaines</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= !empty($maintenance['mode_maintenance']) ? 'ON' : 'OFF' ?></h3><p>Maintenance</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($stats['secrets'] ?? 0) ?></h3><p>Secrets</p></div></div></div>
  </div>

  <div class="row">
    <div class="col-md-5">
      <form method="post" action="/settings/maintenance" class="card card-outline <?= !empty($maintenance['mode_maintenance']) ? 'card-danger' : 'card-success' ?>">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf_token ?? '') ?>">
        <div class="card-header"><h3 class="card-title">Mode maintenance</h3></div>
        <div class="card-body">
          <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="mode_maintenance" name="mode_maintenance" value="1" <?= !empty($maintenance['mode_maintenance']) ? 'checked' : '' ?>>
            <label class="custom-control-label" for="mode_maintenance">Maintenance active</label>
          </div>
          <div class="form-group">
            <label>Message public</label>
            <textarea class="form-control" name="message_public" rows="3"><?= $e($maintenance['message_public'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Rôles autorisés</label>
            <textarea class="form-control" name="roles_autorises" rows="3"><?= $e($list($maintenance['roles_autorises'] ?? [])) ?></textarea>
          </div>
          <div class="form-group">
            <label>IP autorisées</label>
            <textarea class="form-control" name="ips_autorisees" rows="3"><?= $e($list($maintenance['ips_autorisees'] ?? [])) ?></textarea>
          </div>
          <dl class="row small text-muted mb-0">
            <dt class="col-5">Dernier début</dt><dd class="col-7"><?= $e($maintenance['dernier_debut_le'] ?? '—') ?></dd>
            <dt class="col-5">Dernière fin</dt><dd class="col-7"><?= $e($maintenance['derniere_fin_le'] ?? '—') ?></dd>
            <dt class="col-5">Modifié</dt><dd class="col-7"><?= $e($maintenance['modifie_le'] ?? '—') ?></dd>
          </dl>
        </div>
        <div class="card-footer"><button class="btn btn-primary btn-block">Enregistrer maintenance</button></div>
      </form>
    </div>

    <div class="col-md-7">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Domaines de configuration</h3></div>
        <div class="card-body p-0 table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Domaine</th><th>Nombre</th><th>Accès rapide</th></tr></thead>
            <tbody>
              <?php foreach (($domains ?? []) as $domain): $name = (string)($domain['domaine'] ?? ''); ?>
                <tr>
                  <td><code><?= $e($name) ?></code></td>
                  <td><?= (int)($domain['total'] ?? 0) ?></td>
                  <td><a class="btn btn-sm btn-outline-primary" href="/settings?domain=<?= urlencode($name) ?>">Voir</a></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($domains)): ?><tr><td colspan="3" class="text-center text-muted py-4">Aucun domaine.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="alert alert-info">
        Les secrets sont stockés dans <code>pap_valeur_chiffree</code> et ne sont jamais réaffichés en clair. Les valeurs non sensibles restent dans <code>pap_valeur_json</code> sous la forme <code>{"valeur": ...}</code>.
      </div>
    </div>
  </div>
</div>
