<section class="container-fluid">
  <h1><?= htmlspecialchars($pageTitle ?? 'Invitation') ?></h1>
  <form method="post" action="<?= url($invitation ? '/invitations/' . (int)$invitation['inv_id'] . '/update' : '/invitations/store') ?>">
    <?= csrf_field() ?>
    <div class="mb-3"><label>Email</label><input class="form-control" name="email" type="email" required value="<?= htmlspecialchars($invitation['inv_email'] ?? '') ?>"></div>
    <div class="mb-3"><label>Société</label><select class="form-select" name="societe_id" required><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= ((int)($invitation['inv_societe_id'] ?? 0)===(int)$s['soc_id'])?'selected':'' ?>><?= htmlspecialchars($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
    <div class="mb-3"><label>Rôle prévu</label><select class="form-select" name="role_prevu_id"><option value="">— Aucun —</option><?php foreach (($refs['roles'] ?? []) as $r): ?><option value="<?= (int)$r['rol_id'] ?>" <?= ((int)($invitation['inv_role_prevu_id'] ?? 0)===(int)$r['rol_id'])?'selected':'' ?>><?= htmlspecialchars($r['rol_nom']) ?></option><?php endforeach; ?></select></div>
    <div class="mb-3"><label>Fonction prévue</label><select class="form-select" name="fonction_prevue_id"><option value="">— Aucune —</option><?php foreach (($refs['fonctions'] ?? []) as $f): ?><option value="<?= (int)$f['fon_id'] ?>" <?= ((int)($invitation['inv_fonction_prevue_id'] ?? 0)===(int)$f['fon_id'])?'selected':'' ?>><?= htmlspecialchars($f['fon_nom']) ?></option><?php endforeach; ?></select></div>
    <div class="mb-3"><label>Durée de validité en jours</label><input class="form-control" name="expire_jours" type="number" min="1" max="60" value="7"></div>
    <div class="mb-3"><label>Message</label><textarea class="form-control" name="message" rows="4"><?= htmlspecialchars($invitation['inv_message'] ?? '') ?></textarea></div>
    <button class="btn btn-primary">Enregistrer</button>
    <a class="btn btn-secondary" href="<?= url('/invitations') ?>">Retour</a>
  </form>
  <?php if ($invitation): ?>
    <hr>
    <form method="post" action="<?= url('/invitations/' . (int)$invitation['inv_id'] . '/resend') ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-warning">Régénérer le jeton</button></form>
    <form method="post" action="<?= url('/invitations/' . (int)$invitation['inv_id'] . '/cancel') ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-danger">Annuler</button></form>
  <?php endif; ?>
</section>
