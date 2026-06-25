<?php defined('AUTOSAV_ROOT') or die; ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Modèles email</h1>
    <a class="btn btn-primary" href="/emails/templates/create">Nouveau modèle</a>
  </div>
  <div class="card"><div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr><th>Code</th><th>Sujet</th><th>Statut</th><th>Modifié</th><th class="text-right">Actions</th></tr></thead>
      <tbody>
      <?php foreach (($modeles ?? []) as $m): ?>
        <tr>
          <td><code><?= htmlspecialchars((string)$m['mel_code']) ?></code></td>
          <td><?= htmlspecialchars((string)$m['mel_sujet']) ?></td>
          <td><?= htmlspecialchars((string)($m['statut_libelle'] ?? '-')) ?></td>
          <td><?= htmlspecialchars((string)($m['mel_modifie_le'] ?? $m['mel_cree_le'] ?? '')) ?></td>
          <td class="text-right">
            <a class="btn btn-sm btn-outline-primary" href="/emails/templates/<?= (int)$m['mel_id'] ?>/edit">Modifier</a>
            <form class="d-inline" method="post" action="/emails/templates/<?= (int)$m['mel_id'] ?>/delete" data-confirm="Supprimer ce modèle ?">
              <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
              <button class="btn btn-sm btn-outline-danger">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($modeles)): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucun modèle email</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div>
