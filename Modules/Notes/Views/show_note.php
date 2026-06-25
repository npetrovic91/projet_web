<?php $h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $h($note['nte_titre'] ?: 'Note #' . (int)$note['nte_id']) ?></h1>
    <div class="btn-group"><a class="btn btn-outline-secondary" href="<?= url('/notes') ?>">Retour</a><a class="btn btn-primary" href="<?= url('/notes/' . (int)$note['nte_id'] . '/edit') ?>">Modifier</a></div>
  </div>
  <div class="card"><div class="card-body">
    <dl class="row">
      <dt class="col-sm-3">Société</dt><dd class="col-sm-9"><?= $h($note['societe_nom'] ?? 'Globale') ?></dd>
      <dt class="col-sm-3">Cible</dt><dd class="col-sm-9"><code><?= $h($note['nte_cible_type']) ?>#<?= (int)$note['nte_cible_id'] ?></code></dd>
      <dt class="col-sm-3">Auteur</dt><dd class="col-sm-9"><?= $h(trim((string)($note['auteur_nom'] ?? '')) ?: ($note['auteur_email'] ?? '—')) ?></dd>
      <dt class="col-sm-3">Visibilité</dt><dd class="col-sm-9"><?= !empty($note['nte_est_privee']) ? 'Privée' : 'Publique' ?></dd>
      <dt class="col-sm-3">Étiquettes</dt><dd class="col-sm-9"><?php foreach (($note['etiquettes'] ?? []) as $e): ?><span class="badge text-bg-secondary me-1"><?= $h($e['eti_libelle']) ?></span><?php endforeach; ?></dd>
    </dl>
    <hr><div style="white-space:pre-wrap"><?= $h($note['nte_contenu']) ?></div>
  </div></div>
</div>
