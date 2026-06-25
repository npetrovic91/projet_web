<?php declare(strict_types=1);
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$dt = static fn(?string $v): string => $v ? date('d/m/Y H:i', strtotime($v)) : '—';
?>
<section class="content-header">
  <div class="container-fluid">
    <h1><?= $e($page_title ?? 'Maintenance applicative') ?></h1>
    <p class="text-muted mb-0">Mode maintenance, politiques, exécutions et journaux alignés sur la base SQL actuelle.</p>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4">
        <div class="card card-outline <?= !empty($state['is_active']) ? 'card-danger' : 'card-success' ?>">
          <div class="card-header"><h3 class="card-title">État du site</h3></div>
          <form method="post" action="/admin/maintenance/toggle">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrf_token ?? '') ?>">
            <div class="card-body">
              <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input" id="mtn_is_active" name="mtn_is_active" value="1" <?= !empty($state['is_active']) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="mtn_is_active">Mode maintenance actif</label>
              </div>
              <div class="form-group">
                <label>Message public</label>
                <textarea class="form-control" name="mtn_message" rows="4"><?= $e($state['message'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label>Rôles autorisés</label>
                <textarea class="form-control" name="mtn_allowed_roles" rows="3"><?= $e(implode("\n", $state['allowed_roles'] ?? [])) ?></textarea>
                <small class="form-text text-muted">Exemple : super_administrateur</small>
              </div>
              <div class="form-group">
                <label>IP autorisées</label>
                <textarea class="form-control" name="mtn_allowed_ips" rows="3"><?= $e(implode("\n", $state['allowed_ips'] ?? [])) ?></textarea>
              </div>
              <dl class="row mb-0 small text-muted">
                <dt class="col-5">Début</dt><dd class="col-7"><?= $e($dt($state['started_at'] ?? null)) ?></dd>
                <dt class="col-5">Fin</dt><dd class="col-7"><?= $e($dt($state['ended_at'] ?? null)) ?></dd>
                <dt class="col-5">Modifié</dt><dd class="col-7"><?= $e($dt($state['updated_at'] ?? null)) ?></dd>
              </dl>
            </div>
            <div class="card-footer">
              <button class="btn btn-primary btn-block">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>

      <div class="col-md-8">
        <div class="row">
          <?php foreach (($indicators ?? []) as $label => $value): ?>
            <div class="col-md-3 col-sm-6">
              <div class="small-box bg-light">
                <div class="inner">
                  <h3><?= (int) $value ?></h3>
                  <p><?= $e(str_replace('_', ' ', $label)) ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card card-outline card-info">
          <div class="card-header"><h3 class="card-title">Politiques de maintenance</h3></div>
          <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0">
              <thead><tr><th>Code</th><th>Action</th><th>Table source</th><th>Fréquence</th><th>Prochaine</th><th>Statut</th></tr></thead>
              <tbody>
              <?php foreach (($policies ?? []) as $policy): ?>
                <tr>
                  <td><code><?= $e($policy['pmt_code'] ?? '') ?></code></td>
                  <td><?= $e($policy['pmt_type_action'] ?? '') ?></td>
                  <td><?= $e($policy['pmt_table_source'] ?? '') ?></td>
                  <td><?= $e($policy['pmt_frequence'] ?? '') ?></td>
                  <td><?= $e($dt($policy['pmt_prochaine_execution_le'] ?? null)) ?></td>
                  <td><?= !empty($policy['pmt_est_active']) ? '<span class="badge badge-success">active</span>' : '<span class="badge badge-secondary">inactive</span>' ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (($policies ?? []) === []): ?>
                <tr><td colspan="6" class="text-center text-muted p-4">Aucune politique de maintenance.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card card-outline card-secondary">
          <div class="card-header"><h3 class="card-title">Dernières exécutions</h3></div>
          <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0">
              <thead><tr><th>Politique</th><th>Statut</th><th>Analysées</th><th>Archivées</th><th>Purgées</th><th>Début</th></tr></thead>
              <tbody>
              <?php foreach (($executions ?? []) as $execution): ?>
                <tr>
                  <td><code><?= $e($execution['exm_code_politique'] ?? '') ?></code></td>
                  <td><?= $e($execution['exm_statut'] ?? '') ?></td>
                  <td><?= (int) ($execution['exm_lignes_analysees'] ?? 0) ?></td>
                  <td><?= (int) ($execution['exm_lignes_archivees'] ?? 0) ?></td>
                  <td><?= (int) ($execution['exm_lignes_purgees'] ?? 0) ?></td>
                  <td><?= $e($dt($execution['exm_debut_le'] ?? null)) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (($executions ?? []) === []): ?>
                <tr><td colspan="6" class="text-center text-muted p-4">Aucune exécution enregistrée.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h3 class="card-title">Événements maintenance</h3></div>
          <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0">
              <thead><tr><th>Type</th><th>Sévérité</th><th>Message</th><th>Par</th><th>Date</th></tr></thead>
              <tbody>
              <?php foreach (($events ?? []) as $event): ?>
                <tr>
                  <td><?= $e($event['mev_event_type'] ?? '') ?></td>
                  <td><?= $e($event['mev_severity'] ?? '') ?></td>
                  <td><?= $e($event['mev_message'] ?? '') ?></td>
                  <td><?= $e($event['created_by_email'] ?? '-') ?></td>
                  <td><?= $e($dt($event['mev_created_at'] ?? null)) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (($events ?? []) === []): ?>
                <tr><td colspan="5" class="text-center text-muted p-4">Aucun événement maintenance.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
