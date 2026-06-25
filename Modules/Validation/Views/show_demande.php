<?php
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$json = $demande['dva_donnees_json'] ?? null;
$pretty = $json ? json_encode(json_decode((string)$json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Demande #<?= (int)$demande['dva_id'] ?></h1><a class="btn btn-outline-secondary" href="/validations">Retour</a></div>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3"><div class="card-header">Détail</div><div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-3">UUID</dt><dd class="col-sm-9"><code><?= $h($demande['dva_uuid'] ?? '') ?></code></dd>
          <dt class="col-sm-3">Type</dt><dd class="col-sm-9"><?= $h($demande['dva_type_demande'] ?? '') ?></dd>
          <dt class="col-sm-3">Société</dt><dd class="col-sm-9"><?= $h($demande['societe_nom'] ?? '-') ?></dd>
          <dt class="col-sm-3">Demandeur</dt><dd class="col-sm-9"><?= $h(trim((string)($demande['demandeur_nom'] ?? '')) ?: ($demande['demandeur_email'] ?? '-')) ?></dd>
          <dt class="col-sm-3">Cible</dt><dd class="col-sm-9"><?= $h(($demande['dva_table_cible'] ?? '-') . (($demande['dva_id_cible'] ?? null) ? ' #' . $demande['dva_id_cible'] : '')) ?></dd>
          <dt class="col-sm-3">Motif</dt><dd class="col-sm-9"><?= nl2br($h($demande['dva_motif'] ?? '-')) ?></dd>
          <dt class="col-sm-3">Décision</dt><dd class="col-sm-9"><?= $h($demande['dva_decision'] ?? 'en_attente') ?></dd>
          <dt class="col-sm-3">Validateur</dt><dd class="col-sm-9"><?= $h(trim((string)($demande['validateur_nom'] ?? '')) ?: ($demande['validateur_email'] ?? '-')) ?></dd>
          <dt class="col-sm-3">Commentaire</dt><dd class="col-sm-9"><?= nl2br($h($demande['dva_commentaire_decision'] ?? '-')) ?></dd>
        </dl>
      </div></div>
      <div class="card"><div class="card-header">Données JSON</div><pre class="card-body mb-0 small"><code><?= $h($pretty ?: '{}') ?></code></pre></div>
    </div>
    <div class="col-lg-4">
      <?php if (empty($demande['dva_decision'])): ?>
      <form class="card card-body mb-3" method="post" action="/validations/<?= (int)$demande['dva_id'] ?>/decision">
        <?= $csrfField ?? csrf_field() ?>
        <label class="form-label">Décision</label>
        <select class="form-select mb-2" name="decision" required><option value="approuvee">Approuver</option><option value="refusee">Refuser</option><option value="annulee">Annuler</option></select>
        <label class="form-label">Commentaire</label><textarea class="form-control mb-2" name="commentaire" rows="4"></textarea>
        <button class="btn btn-primary">Enregistrer la décision</button>
      </form>
      <?php endif; ?>
      <form method="post" action="/validations/<?= (int)$demande['dva_id'] ?>/delete" data-confirm="Supprimer cette demande ?">
        <?= $csrfField ?? csrf_field() ?>
        <button class="btn btn-outline-danger w-100">Supprimer logiquement</button>
      </form>
    </div>
  </div>
</div>
