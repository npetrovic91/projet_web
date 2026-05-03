<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1><?= htmlspecialchars($page_title ?? 'Metiers') ?></h1>
    <a href="/jobs/create" class="btn btn-primary">Nouveau metier</a>
  </div>
</section>
<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-body p-0">
        <table class="table table-striped mb-0">
          <thead><tr><th>Code</th><th>Libelle</th><th>Portee</th><th>Statut</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
          <?php foreach (($jobs ?? []) as $job): ?>
            <tr>
              <td><?= htmlspecialchars($job['job_code']) ?></td>
              <td><?= htmlspecialchars($job['job_label']) ?></td>
              <td><?= !empty($job['job_is_global']) ? 'Commun' : htmlspecialchars($job['job_company_name'] ?? 'Structure') ?></td>
              <td><?= !empty($job['job_is_active']) ? 'Actif' : 'Inactif' ?></td>
              <td class="text-right">
                <a class="btn btn-sm btn-outline-secondary" href="/jobs/<?= (int) $job['job_id'] ?>/edit">Modifier</a>
                <form class="d-inline" method="post" action="/jobs/<?= (int) $job['job_id'] ?>/toggle">
                  <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                  <button class="btn btn-sm btn-outline-warning"><?= !empty($job['job_is_active']) ? 'Desactiver' : 'Activer' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (($jobs ?? []) === []): ?>
            <tr><td colspan="5" class="text-center text-muted p-4">Aucun metier disponible.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
