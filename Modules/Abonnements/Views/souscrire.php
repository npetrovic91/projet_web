<?php
defined('AUTOSAV_ROOT') or die;
$h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$refs = $refs ?? [];
$historique = $historique ?? ['relations' => 0, 'contacts' => 0, 'emails_recus' => 0];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Souscrire un abonnement</h1>
    <a class="btn btn-outline-secondary" href="<?= url('/abonnements') ?>">Retour</a>
  </div>

  <div class="alert alert-info">
    <strong>Historique conservé pour cette société</strong> — une société non abonnée garde toutes ses données ;
    rien n'est perdu en souscrivant :
    <ul class="mb-0">
      <li><?= (int) $historique['relations'] ?> relation(s) avec d'autres sociétés</li>
      <li><?= (int) $historique['contacts'] ?> contact(s) enregistré(s)</li>
      <li><?= (int) $historique['emails_recus'] ?> email(s) reçu(s)</li>
    </ul>
  </div>

  <form method="post" action="<?= url('/abonnements/souscrire') ?>" class="card card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="societe_id" value="<?= (int) ($societeId ?? 0) ?>">
    <div class="form-group">
      <label class="form-label">Formule d'abonnement *</label>
      <select class="form-select" name="fab_formule_id" required>
        <option value="">Sélectionner</option>
        <?php foreach (($refs['formules'] ?? []) as $f): ?>
          <option value="<?= (int) $f['fab_id'] ?>"><?= $h($f['fab_nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mt-3">
      <button class="btn btn-success" type="submit"><i class="fas fa-check mr-1"></i>Souscrire (abonnement + accès applicatif)</button>
    </div>
  </form>
</div>
