<?php $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Clés API</h1><a class="btn btn-outline-secondary" href="/connectors">Connecteurs</a></div>
  <?php if (!empty($last_secret)): ?>
    <div class="alert alert-warning"><strong>Secret à copier maintenant :</strong><br><code><?= $h($last_secret['secret'] ?? '') ?></code><br><small>Préfixe public : <?= $h($last_secret['prefixe_public'] ?? '') ?>. Ce secret ne sera plus affiché.</small></div>
  <?php endif; ?>
  <div class="row g-3">
    <div class="col-lg-4">
      <form class="card card-body" method="post" action="/connectors/api-keys/store">
        <?= $csrfField ?? csrf_field() ?>
        <h2 class="h5">Créer une clé</h2>
        <label class="form-label">Nom</label><input class="form-control mb-2" name="nom" required>
        <label class="form-label">Connecteur</label><select class="form-select mb-2" name="connecteur_id"><option value="">Aucun</option><?php foreach (($refs['connecteurs'] ?? []) as $c): ?><option value="<?= (int)$c['con_id'] ?>"><?= $h($c['con_nom']) ?></option><?php endforeach; ?></select>
        <label class="form-label">Société</label><select class="form-select mb-2" name="societe_id"><option value="">Globale</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>"><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select>
        <label class="form-label">Expiration</label><input class="form-control mb-2" type="datetime-local" name="expire_le">
        <label class="form-label">Portées JSON</label><textarea class="form-control font-monospace mb-3" name="portees_json" rows="6">["connecteur.lire"]</textarea>
        <button class="btn btn-primary">Créer</button>
      </form>
    </div>
    <div class="col-lg-8">
      <div class="card"><div class="card-header">Clés existantes</div><div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>Nom</th><th>Préfixe</th><th>Connecteur</th><th>Société</th><th>Expiration</th><th>Dernière utilisation</th><th>Révocation</th><th></th></tr></thead><tbody>
        <?php foreach (($cles_api ?? []) as $k): ?>
          <tr>
            <td><?= $h($k['cap_nom'] ?? '') ?></td><td><code><?= $h($k['cap_prefixe_public'] ?? '') ?></code></td><td><?= $h($k['con_nom'] ?? '-') ?></td><td><?= $h($k['soc_nom'] ?? 'Global') ?></td><td><?= $h($k['cap_expire_le'] ?? '-') ?></td><td><?= $h($k['cap_derniere_utilisation_le'] ?? '-') ?></td><td><?= $h($k['cap_revoque_le'] ?? '-') ?></td>
            <td class="text-end"><?php if (empty($k['cap_revoque_le'])): ?><form method="post" action="/connectors/api-keys/<?= (int)$k['cap_id'] ?>/revoke" data-confirm="Révoquer cette clé API ?"><?= $csrfField ?? csrf_field() ?><button class="btn btn-sm btn-outline-danger">Révoquer</button></form><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($cles_api)): ?><tr><td colspan="8" class="text-center text-muted">Aucune clé API.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
  </div>
</div>
