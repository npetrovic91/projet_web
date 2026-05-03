<?php $user = $user ?? []; ?>
<section class="content-header">
  <div class="container-fluid">
    <h1><?= htmlspecialchars($page_title ?? 'Historique professionnel') ?></h1>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars(($user['use_firstname'] ?? '') . ' ' . ($user['use_lastname'] ?? '')) ?></h3>
      </div>
      <div class="card-body p-0">
        <table class="table table-striped mb-0">
          <thead>
            <tr><th>Entreprise</th><th>Type</th><th>Poste</th><th>Debut</th><th>Fin</th><th>Motif</th></tr>
          </thead>
          <tbody>
            <?php foreach (($history ?? []) as $entry): ?>
              <tr>
                <td><?= htmlspecialchars($entry['com_name']) ?></td>
                <td><?= htmlspecialchars($entry['type_label'] ?? '-') ?></td>
                <td><?= htmlspecialchars($entry['uch_job_title'] ?? '-') ?></td>
                <td><?= htmlspecialchars($entry['uch_started_at'] ?? '-') ?></td>
                <td><?= htmlspecialchars($entry['uch_ended_at'] ?? 'En cours') ?></td>
                <td><?= htmlspecialchars($entry['uch_departure_reason'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (($history ?? []) === []): ?>
              <tr><td colspan="6" class="text-center text-muted p-4">Aucun historique disponible.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer">
        <a class="btn btn-secondary" href="/users/<?= (int) ($user['use_id'] ?? 0) ?>">Retour fiche</a>
      </div>
    </div>
  </div>
</section>
