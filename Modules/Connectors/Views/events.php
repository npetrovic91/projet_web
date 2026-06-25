<?php $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Événements applicatifs</h1><a class="btn btn-outline-secondary" href="/connectors">Connecteurs</a></div>
  <div class="row g-3">
    <div class="col-lg-4">
      <form class="card card-body" method="post" action="/connectors/events/store">
        <?= $csrfField ?? csrf_field() ?>
        <h2 class="h5">Créer un événement</h2>
        <label class="form-label">Code</label><input class="form-control mb-2" name="code" required placeholder="societe.creee">
        <label class="form-label">Nom</label><input class="form-control mb-2" name="nom" required>
        <label class="form-label">Module</label><select class="form-select mb-2" name="module_id"><option value="">Noyau</option><?php foreach (($refs['modules'] ?? []) as $m): ?><option value="<?= (int)$m['mod_id'] ?>"><?= $h($m['mod_nom']) ?></option><?php endforeach; ?></select>
        <label class="form-label">Société</label><select class="form-select mb-2" name="societe_id"><option value="">Globale</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>"><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select>
        <label class="form-label">Cible type</label><input class="form-control mb-2" name="cible_type">
        <label class="form-label">Cible ID</label><input class="form-control mb-2" type="number" name="cible_id">
        <label class="form-label">Données JSON</label><textarea class="form-control font-monospace mb-3" name="donnees_json" rows="6">{}</textarea>
        <button class="btn btn-primary">Créer</button>
      </form>
    </div>
    <div class="col-lg-8">
      <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>Date</th><th>Code</th><th>Nom</th><th>Module</th><th>Société</th><th>Cible</th><th>File</th></tr></thead><tbody>
        <?php foreach (($evenements ?? []) as $e): ?>
          <tr><td><?= $h($e['eva_cree_le'] ?? '') ?></td><td><code><?= $h($e['eva_code'] ?? '') ?></code></td><td><?= $h($e['eva_nom'] ?? '') ?></td><td><?= $h($e['mod_nom'] ?? '-') ?></td><td><?= $h($e['soc_nom'] ?? 'Global') ?></td><td><?= $h(($e['eva_cible_type'] ?? '-') . ' #' . ($e['eva_cible_id'] ?? '-')) ?></td><td><?= (int)($e['total_file'] ?? 0) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($evenements)): ?><tr><td colspan="7" class="text-center text-muted">Aucun événement.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
  </div>
</div>
