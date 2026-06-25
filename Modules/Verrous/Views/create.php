<?php
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$refs = $refs ?? [];
?>
<div class="container py-3">
  <h1 class="h3 mb-3">Créer un verrou d’entité</h1>
  <form method="post" action="<?= url('/verrous/store') ?>" class="card card-body">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Type de cible *</label><select class="form-select" name="cible_type" required><?php foreach (($refs['cibles'] ?? []) as $code=>$label): ?><option value="<?= $h($code) ?>"><?= $h($label) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">ID cible *</label><input class="form-control" name="cible_id" type="number" min="1" required></div>
      <div class="col-md-4"><label class="form-label">Utilisateur détenteur</label><select class="form-select" name="utilisateur_id"><option value="0">Utilisateur courant</option><?php foreach (($refs['utilisateurs'] ?? []) as $u): ?><option value="<?= (int)$u['uti_id'] ?>"><?= $h(trim(($u['pui_prenom'] ?? '') . ' ' . ($u['pui_nom'] ?? '')) ?: $u['uti_email_normalise']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Durée minutes</label><input class="form-control" name="duree_minutes" type="number" min="1" max="1440" value="15"></div>
      <div class="col-md-12"><label class="form-label">Raison</label><input class="form-control" name="raison" maxlength="255" value="Verrou manuel administratif"></div>
    </div>
    <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Créer</button><a class="btn btn-outline-secondary" href="<?= url('/verrous') ?>">Retour</a></div>
  </form>
</div>
