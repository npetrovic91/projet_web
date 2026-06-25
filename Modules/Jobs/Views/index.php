<?php
declare(strict_types=1);
$jobs = $jobs ?? $functions ?? [];
$csrf = $csrf_token ?? '';
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1><i class="fas fa-tools mr-2"></i>Métiers</h1></div>
      <div class="col-sm-6 text-right"><a class="btn btn-primary btn-sm" href="/jobs/create"><i class="fas fa-plus mr-1"></i>Nouveau métier</a></div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <?php $flash = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); ?>
    <?php foreach ($flash as $type => $messages): $class = $type === 'error' ? 'danger' : $type; ?>
      <?php foreach ((array) $messages as $message): ?>
        <div class="alert alert-<?= $e($class) ?> alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $e($message) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="alert alert-info small">
      Compatibilité : les métiers sont stockés dans <code>sav_fonctions</code>, conformément à la règle du projet : Fonction = métier réel.
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><?= count($jobs) ?> métier(s)</h3></div>
      <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
          <thead>
            <tr>
              <th>Code</th>
              <th>Libellé</th>
              <th>Portée</th>
              <th>Type société</th>
              <th>Statut</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jobs as $job): ?>
              <?php $active = (bool) ($job['job_is_active'] ?? true); ?>
              <tr class="<?= $active ? '' : 'text-muted' ?>">
                <td><code><?= $e($job['job_code'] ?? $job['fon_code'] ?? '') ?></code></td>
                <td>
                  <strong><?= $e($job['job_label'] ?? $job['fon_nom'] ?? '') ?></strong>
                  <?php if (!empty($job['job_description'] ?? $job['fon_description'] ?? '')): ?>
                    <br><small class="text-muted"><?= $e($job['job_description'] ?? $job['fon_description']) ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ((int) ($job['job_is_global'] ?? 0) === 1): ?>
                    <span class="badge badge-success">Global</span>
                  <?php else: ?>
                    <span class="badge badge-secondary"><?= $e($job['job_company_name'] ?? 'Société') ?></span>
                  <?php endif; ?>
                </td>
                <td><?= $e($job['job_company_type_name'] ?? '—') ?></td>
                <td><?= $active ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-secondary">Inactif</span>' ?></td>
                <td class="text-right text-nowrap">
                  <a class="btn btn-sm btn-outline-primary" href="/jobs/<?= (int) $job['job_id'] ?>/edit"><i class="fas fa-edit"></i></a>
                  <form method="post" action="/jobs/<?= (int) $job['job_id'] ?>/toggle" style="display:inline" data-confirm="Changer le statut de ce métier ?">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                    <button class="btn btn-sm <?= $active ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><i class="fas fa-<?= $active ? 'ban' : 'check' ?>"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$jobs): ?>
              <tr><td colspan="6" class="text-center text-muted p-4">Aucun métier.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
