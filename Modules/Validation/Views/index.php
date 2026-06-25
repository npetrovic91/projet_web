<?php
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$stats = $stats ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Validations / opérations sensibles</h1>
    <div class="btn-group">
      <a class="btn btn-primary" href="/validations/create">Nouvelle demande</a>
      <a class="btn btn-outline-secondary" href="/validation-rules">Règles</a>
      <a class="btn btn-outline-secondary" href="/validations/export.json">Export JSON</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Demandes</div><div class="display-6"><?= (int)($stats['demandes_total'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">En attente</div><div class="display-6"><?= (int)($stats['demandes_en_attente'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Approuvées</div><div class="display-6"><?= (int)($stats['demandes_validees'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Règles actives</div><div class="display-6"><?= (int)($stats['regles_actives'] ?? 0) ?></div></div></div></div>
  </div>

  <form class="card card-body mb-3" method="get" action="/validations">
    <div class="row g-2 align-items-end">
      <div class="col-md-4"><label class="form-label">Recherche</label><input class="form-control" name="q" value="<?= $h($filters['q'] ?? '') ?>" placeholder="type, motif, société, utilisateur"></div>
      <div class="col-md-2"><label class="form-label">Décision</label><select class="form-select" name="decision"><option value="">Toutes</option><?php foreach (($refs['decisions'] ?? []) as $d): ?><option value="<?= $h($d) ?>" <?= (($filters['decision'] ?? '') === $d) ? 'selected' : '' ?>><?= $h($d) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label">Société</label><select class="form-select" name="societe_id"><option value="">Toutes</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= ((int)($filters['societe_id'] ?? 0) === (int)$s['soc_id']) ? 'selected' : '' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-1"><button class="btn btn-secondary w-100">Filtrer</button></div>
      <div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="/validations">Réinitialiser</a></div>
    </div>
  </form>

  <div class="card">
    <div class="card-header">Demandes de validation</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead><tr><th>ID</th><th>Type</th><th>Société</th><th>Demandeur</th><th>Cible</th><th>Décision</th><th>Validateur</th><th>Créée le</th><th></th></tr></thead>
        <tbody>
        <?php foreach (($demandes ?? []) as $d): ?>
          <tr>
            <td>#<?= (int)$d['dva_id'] ?></td>
            <td><?= $h($d['dva_type_demande'] ?? '') ?></td>
            <td><?= $h($d['societe_nom'] ?? '-') ?></td>
            <td><?= $h(trim((string)($d['demandeur_nom'] ?? '')) ?: ($d['demandeur_email'] ?? '-')) ?></td>
            <td><?= $h(($d['dva_table_cible'] ?? '-') . (($d['dva_id_cible'] ?? null) ? ' #' . $d['dva_id_cible'] : '')) ?></td>
            <td><?= $h($d['dva_decision'] ?? 'en_attente') ?></td>
            <td><?= $h(trim((string)($d['validateur_nom'] ?? '')) ?: ($d['validateur_email'] ?? '-')) ?></td>
            <td><?= $h($d['dva_cree_le'] ?? '') ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/validations/<?= (int)$d['dva_id'] ?>">Ouvrir</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($demandes)): ?><tr><td colspan="9" class="text-center text-muted">Aucune demande.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
