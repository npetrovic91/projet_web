<?php $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Journaux Webhooks</h1><a class="btn btn-outline-secondary" href="/connectors">Connecteurs</a></div>
  <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
    <thead><tr><th>Date</th><th>Connecteur</th><th>Événement</th><th>Méthode</th><th>URL</th><th>HTTP</th><th>Succès</th><th>Durée</th><th>Erreur</th></tr></thead><tbody>
    <?php foreach (($webhooks ?? []) as $w): ?>
      <tr>
        <td><?= $h($w['jwh_cree_le'] ?? '') ?></td><td><?= $h($w['con_nom'] ?? '-') ?></td><td><?= $h($w['eva_code'] ?? '-') ?></td><td><?= $h($w['jwh_methode'] ?? '') ?></td><td class="text-truncate" style="max-width:360px"><?= $h($w['jwh_url'] ?? '') ?></td><td><?= $h($w['jwh_code_http'] ?? '-') ?></td><td><?= isset($w['jwh_succes']) ? ((int)$w['jwh_succes'] ? 'Oui' : 'Non') : '-' ?></td><td><?= $h($w['jwh_duree_ms'] ?? '-') ?> ms</td><td><?= $h($w['jwh_message_erreur'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($webhooks)): ?><tr><td colspan="9" class="text-center text-muted">Aucun journal webhook.</td></tr><?php endif; ?>
    </tbody></table></div></div>
</div>
