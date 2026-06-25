<?php defined('AUTOSAV_ROOT') or die; ?>
<div class="container-fluid py-3">
  <h1 class="h3 mb-3">Journaux emails</h1>
  <div class="card"><div class="card-body p-0">
    <table class="table table-sm table-hover mb-0">
      <thead><tr><th>Date</th><th>Type</th><th>Destinataire</th><th>Société</th><th>Sujet</th><th>Statut</th></tr></thead>
      <tbody>
      <?php foreach (($journaux ?? []) as $j): ?>
        <tr>
          <td><?= htmlspecialchars((string)$j['jme_cree_le']) ?></td>
          <td><code><?= htmlspecialchars((string)($j['jme_type_evenement'] ?? '-')) ?></code></td>
          <td><?= htmlspecialchars((string)($j['jme_email_destinataire'] ?? '-')) ?></td>
          <td><?= htmlspecialchars((string)($j['societe_destinataire_nom'] ?? '-')) ?></td>
          <td><?= htmlspecialchars((string)$j['jme_sujet']) ?></td>
          <td><?= htmlspecialchars((string)($j['statut_libelle'] ?? '-')) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($journaux)): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucun journal email</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div>
