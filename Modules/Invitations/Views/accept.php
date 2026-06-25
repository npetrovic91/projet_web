<section class="container py-5" style="max-width:720px">
  <h1>Accepter une invitation</h1>
  <?php if (!$invitation): ?><div class="alert alert-danger">Invitation introuvable ou invalide.</div><?php else: ?>
  <p>Vous êtes invité à rejoindre <strong><?= htmlspecialchars($invitation['soc_nom'] ?? '') ?></strong> avec l’adresse <strong><?= htmlspecialchars($invitation['inv_email'] ?? '') ?></strong>.</p>
  <form method="post" action="<?= url('/invitations/accept/' . rawurlencode($token ?? '')) ?>">
    <?= csrf_field() ?>
    <div class="mb-3"><label>Prénom</label><input class="form-control" name="prenom"></div>
    <div class="mb-3"><label>Nom</label><input class="form-control" name="nom"></div>
    <div class="mb-3"><label>Mot de passe</label><input class="form-control" name="password" type="password" required minlength="12"></div>
    <div class="mb-3"><label>Confirmation</label><input class="form-control" name="password_confirmation" type="password" required minlength="12"></div>
    <button class="btn btn-primary">Accepter et créer mon accès</button>
  </form>
  <?php endif; ?>
</section>
