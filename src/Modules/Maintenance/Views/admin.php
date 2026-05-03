<section class="content-header">
  <div class="container-fluid"><h1><?= htmlspecialchars($page_title ?? 'Maintenance') ?></h1></div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4">
        <div class="card card-outline <?= !empty($state['is_active']) ? 'card-danger' : 'card-success' ?>">
          <div class="card-header"><h3 class="card-title">Etat du site</h3></div>
          <form method="post" action="/admin/maintenance/toggle">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <div class="card-body">
              <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input" id="mtn_is_active" name="mtn_is_active" value="1" <?= !empty($state['is_active']) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="mtn_is_active">Mode maintenance actif</label>
              </div>
              <div class="form-group">
                <label>Message public</label>
                <textarea class="form-control" name="mtn_message" rows="4"><?= htmlspecialchars($state['message'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label>Roles autorises</label>
                <textarea class="form-control" name="mtn_allowed_roles" rows="3"><?= htmlspecialchars(implode("\n", $state['allowed_roles'] ?? [])) ?></textarea>
              </div>
              <div class="form-group">
                <label>IP autorisees</label>
                <textarea class="form-control" name="mtn_allowed_ips" rows="3"><?= htmlspecialchars(implode("\n", $state['allowed_ips'] ?? [])) ?></textarea>
              </div>
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
            <div class="col-md-4">
              <div class="small-box bg-light">
                <div class="inner">
                  <h3><?= (int) $value ?></h3>
                  <p><?= htmlspecialchars(str_replace('_', ' ', $label)) ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card">
          <div class="card-header"><h3 class="card-title">Evenements maintenance</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
              <thead><tr><th>Type</th><th>Severite</th><th>Message</th><th>Par</th><th>Date</th></tr></thead>
              <tbody>
              <?php foreach (($events ?? []) as $event): ?>
                <tr>
                  <td><?= htmlspecialchars($event['mev_event_type']) ?></td>
                  <td><?= htmlspecialchars($event['mev_severity']) ?></td>
                  <td><?= htmlspecialchars($event['mev_message']) ?></td>
                  <td><?= htmlspecialchars($event['created_by_email'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($event['mev_created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (($events ?? []) === []): ?>
                <tr><td colspan="5" class="text-center text-muted p-4">Aucun evenement maintenance.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
