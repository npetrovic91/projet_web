<section class="content-header">
  <div class="container-fluid"><h1><?= htmlspecialchars($page_title ?? 'Tableau RGPD') ?></h1></div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Demandes RGPD</h3></div>
      <div class="card-body p-0">
        <table class="table table-striped mb-0">
          <thead><tr><th>Utilisateur</th><th>Type</th><th>Statut</th><th>Date</th><th></th></tr></thead>
          <tbody>
          <?php foreach (($requests ?? []) as $request): ?>
            <tr>
              <td><?= htmlspecialchars($request['use_email']) ?></td>
              <td><?= htmlspecialchars($request['grq_type']) ?></td>
              <td><?= htmlspecialchars($request['grq_status']) ?></td>
              <td><?= htmlspecialchars($request['grq_created_at']) ?></td>
              <td class="text-right"><a class="btn btn-sm btn-outline-primary" href="/gdpr/<?= (int) $request['grq_id'] ?>">Traiter</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (($requests ?? []) === []): ?>
            <tr><td colspan="5" class="text-center text-muted p-4">Aucune demande.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Dernieres actions RGPD</h3></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <tbody>
          <?php foreach (($actions ?? []) as $action): ?>
            <tr>
              <td><?= htmlspecialchars($action['gac_action']) ?></td>
              <td><?= htmlspecialchars($action['target_email'] ?? '-') ?></td>
              <td><?= htmlspecialchars($action['admin_email'] ?? '-') ?></td>
              <td><?= htmlspecialchars($action['gac_created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
