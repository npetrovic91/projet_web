<section class="content-header">
  <div class="container-fluid">
    <h1><?= htmlspecialchars($page_title ?? 'Mes donnees personnelles') ?></h1>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-5">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Nouvelle demande</h3></div>
          <form method="post" action="/profile/gdpr/request">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <div class="card-body">
              <div class="form-group">
                <label>Type de demande</label>
                <select class="form-control" name="type">
                  <option value="access">Acces a mes donnees</option>
                  <option value="rectification">Rectification</option>
                  <option value="erasure">Effacement</option>
                  <option value="export">Export</option>
                </select>
              </div>
              <div class="form-group">
                <label>Message</label>
                <textarea class="form-control" name="message"></textarea>
              </div>
            </div>
            <div class="card-footer">
              <button class="btn btn-primary">Envoyer la demande</button>
              <a class="btn btn-secondary" href="/profile/gdpr/export">Exporter maintenant</a>
            </div>
          </form>
        </div>
      </div>
      <div class="col-md-7">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Historique des demandes</h3></div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead><tr><th>Type</th><th>Statut</th><th>Date</th></tr></thead>
              <tbody>
                <?php foreach (($requests ?? []) as $request): ?>
                  <tr>
                    <td><?= htmlspecialchars($request['grq_type']) ?></td>
                    <td><?= htmlspecialchars($request['grq_status']) ?></td>
                    <td><?= htmlspecialchars($request['grq_created_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (($requests ?? []) === []): ?>
                  <tr><td colspan="3" class="text-center text-muted p-4">Aucune demande RGPD enregistree.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
