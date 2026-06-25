<?php defined('AUTOSAV_ROOT') or die;
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$contact = $contact ?? null;
$refs = $refs ?? [];
$isEdit = !empty($contact['cts_id']);
$action = $isEdit ? '/contacts-societes/' . (int)$contact['cts_id'] . '/update' : '/contacts-societes/store';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Modifier le contact société' : 'Créer un contact société' ?></h1>
    <a class="btn btn-outline-secondary" href="<?= url('/contacts-societes') ?>">Retour</a>
  </div>
  <form method="post" action="<?= url($action) ?>" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Société *</label><select class="form-select" name="cts_societe_id" required><option value="">Sélectionner</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= (int)($contact['cts_societe_id'] ?? $societe_id ?? 0)===(int)$s['soc_id']?'selected':'' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Utilisateur interne</label><select class="form-select" name="cts_utilisateur_id"><option value="">Aucun utilisateur interne</option><?php foreach (($refs['utilisateurs'] ?? []) as $u): ?><option value="<?= (int)$u['uti_id'] ?>" <?= (int)($contact['cts_utilisateur_id'] ?? 0)===(int)$u['uti_id']?'selected':'' ?>><?= $h($u['utilisateur_libelle'] . ' — ' . $u['uti_email']) ?></option><?php endforeach; ?></select><div class="form-text">À utiliser si le contact est déjà un utilisateur de la plateforme.</div></div>
      <div class="col-md-4"><label class="form-label">Email externe</label><input class="form-control" type="email" name="cts_email_externe" value="<?= $h($contact['cts_email_externe'] ?? '') ?>"><div class="form-text">Obligatoire si aucun utilisateur interne n’est lié.</div></div>
      <div class="col-md-3"><label class="form-label">Prénom</label><input class="form-control" name="cts_prenom" value="<?= $h($contact['cts_prenom'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Nom</label><input class="form-control" name="cts_nom" value="<?= $h($contact['cts_nom'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label">Type de contact</label><select class="form-select" name="cts_type_contact_id"><option value="">Aucun</option><?php foreach (($refs['types'] ?? []) as $t): ?><option value="<?= (int)$t['tco_id'] ?>" <?= (int)($contact['cts_type_contact_id'] ?? 0)===(int)$t['tco_id']?'selected':'' ?>><?= $h($t['tco_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label">Statut</label><select class="form-select" name="cts_statut_id"><option value="">Aucun</option><?php foreach (($refs['statuts'] ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= (int)($contact['cts_statut_id'] ?? 0)===(int)$st['sta_id']?'selected':'' ?>><?= $h(($st['sta_domaine'] ?? '') . ' — ' . ($st['sta_nom'] ?? '')) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Enregistrer</button>
      <?php if ($isEdit): ?><button class="btn btn-outline-danger" formaction="<?= url('/contacts-societes/' . (int)$contact['cts_id'] . '/delete') ?>" formmethod="post" data-confirm="Supprimer ce contact ?">Supprimer</button><?php endif; ?>
    </div>
  </form>
</div>
