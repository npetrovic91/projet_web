<?php defined('AUTOSAV_ROOT') or die; ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Emails groupés</h1>
    <a href="/bulk-mail/create" class="btn btn-primary">Nouvel email groupé</a>
  </div>
  <div class="alert alert-info">Le schéma SQL actuel ne contient pas de table de campagnes email persistantes. Les envois groupés sont donc historisés par lot dans <code>sav_journaux_emails</code>.</div>
  <div class="card"><div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr><th>Code lot</th><th>Sujet</th><th>Modèle</th><th>Total</th><th>Envoyés</th><th>Échecs</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach (($campaigns ?? []) as $c): ?>
        <tr>
          <td><a href="/bulk-mail/<?= urlencode((string)$c['code']) ?>"><code><?= htmlspecialchars((string)$c['code']) ?></code></a></td>
          <td><?= htmlspecialchars((string)$c['sujet']) ?></td>
          <td><?= htmlspecialchars((string)($c['modele_code'] ?? '-')) ?></td>
          <td><?= (int)$c['total'] ?></td>
          <td><?= (int)$c['envoyes'] ?></td>
          <td><?= (int)$c['echoues'] ?></td>
          <td><?= htmlspecialchars((string)($c['cree_le'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($campaigns)): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucun envoi groupé journalisé</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div>
