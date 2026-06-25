<?php defined('AUTOSAV_ROOT') or die; ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Emails</h1>
    <div>
      <a class="btn btn-outline-primary" href="/emails/templates">Modèles</a>
      <a class="btn btn-outline-secondary" href="/emails/logs">Journaux</a>
      <a class="btn btn-primary" href="/bulk-mail/create">Email groupé</a>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header"><strong>Modèles email</strong></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead><tr><th>Code</th><th>Sujet</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach (($modeles ?? []) as $m): ?>
              <tr>
                <td><a href="/emails/templates/<?= (int)$m['mel_id'] ?>/edit"><?= htmlspecialchars((string)$m['mel_code']) ?></a></td>
                <td><?= htmlspecialchars((string)$m['mel_sujet']) ?></td>
                <td><?= htmlspecialchars((string)($m['statut_libelle'] ?? '-')) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($modeles)): ?><tr><td colspan="3" class="text-muted text-center py-3">Aucun modèle</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header"><strong>Derniers emails journalisés</strong></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead><tr><th>Date</th><th>Destinataire</th><th>Sujet</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach (($journaux ?? []) as $j): ?>
              <tr>
                <td><?= htmlspecialchars((string)$j['jme_cree_le']) ?></td>
                <td><?= htmlspecialchars((string)$j['jme_email_destinataire']) ?></td>
                <td><?= htmlspecialchars((string)$j['jme_sujet']) ?></td>
                <td><?= htmlspecialchars((string)($j['statut_libelle'] ?? '-')) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($journaux)): ?><tr><td colspan="4" class="text-muted text-center py-3">Aucun email journalisé</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
