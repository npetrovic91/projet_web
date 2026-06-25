<section class="container-fluid">
  <h1>Créer le premier administrateur société</h1>
  <p>Cette action prépare une invitation contrôlée pour le premier administrateur d’une société abonnée ou destinée à accéder à un espace applicatif.</p>
  <form method="post" action="<?= url('/invitations/premier-administrateur/store') ?>">
    <?= csrf_field() ?>
    <div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" required></div>
    <div class="mb-3"><label>Société</label><select class="form-select" name="societe_id" required><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>"><?= htmlspecialchars($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
    <div class="mb-3"><label>Rôle prévu</label><select class="form-select" name="role_prevu_id"><option value="">Super-administrateur par défaut si disponible</option><?php foreach (($refs['roles'] ?? []) as $r): ?><option value="<?= (int)$r['rol_id'] ?>"><?= htmlspecialchars($r['rol_nom']) ?></option><?php endforeach; ?></select></div>
    <div class="mb-3"><label>Message</label><textarea class="form-control" name="message" rows="4">Bienvenue. Merci d’activer votre compte administrateur.</textarea></div>
    <button class="btn btn-primary">Créer l’invitation administrateur</button>
  </form>
</section>
