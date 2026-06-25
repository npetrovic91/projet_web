<?php
declare(strict_types=1);
$events = is_array($events ?? null) ? $events : [];
$rules = is_array($rules ?? null) ? $rules : [];
?>
<section class="content-header">
  <div class="container-fluid"><h1><?= htmlspecialchars($page_title ?? 'Événements applicatifs') ?></h1></div>
</section>
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Catalogue événementiel détecté</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
              <thead><tr><th>Code</th><th>Libellé</th><th>Source</th><th>Actif</th></tr></thead>
              <tbody>
              <?php foreach ($events as $event): ?>
                <tr>
                  <td><code><?= htmlspecialchars((string) $event['evt_code']) ?></code></td>
                  <td><?= htmlspecialchars((string) $event['evt_label']) ?></td>
                  <td><?= htmlspecialchars((string) ($event['source_table'] ?? '-')) ?></td>
                  <td><?= !empty($event['evt_is_active']) ? 'Oui' : 'Non' ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Abonnements de notification</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
              <thead><tr><th>Événement</th><th>Contact</th><th>Canaux</th><th>Actif</th></tr></thead>
              <tbody>
              <?php foreach ($rules as $rule): ?>
                <tr>
                  <td><code><?= htmlspecialchars((string) $rule['evt_code']) ?></code></td>
                  <td><?= htmlspecialchars((string) ($rule['nco_email'] ?? '-')) ?></td>
                  <td><?= htmlspecialchars((string) $rule['nru_channels']) ?></td>
                  <td><?= !empty($rule['nru_is_active']) ? 'Oui' : 'Non' ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <p><a class="btn btn-primary" href="/notifications/rules">Gérer les abonnements aux événements</a></p>
  </div>
</section>
