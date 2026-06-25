<section class="container-fluid">
  <h1>Invitations utilisateurs</h1>
  <p>Gestion des invitations contrôlées, rattachées aux sociétés et aux rôles/fonctions prévus.</p>
  <div class="row g-3 mb-3">
    <?php foreach (($stats ?? []) as $libelle => $valeur): ?>
      <div class="col"><div class="card"><div class="card-body"><strong><?= htmlspecialchars((string)$valeur) ?></strong><br><?= htmlspecialchars((string)$libelle) ?></div></div></div>
    <?php endforeach; ?>
  </div>
  <p><a class="btn btn-primary" href="<?= url('/invitations/create') ?>">Créer une invitation</a> <a class="btn btn-outline-secondary" href="<?= url('/invitations/premier-administrateur') ?>">Premier administrateur société</a></p>
  <table class="table table-striped"><thead><tr><th>Email</th><th>Société</th><th>Rôle prévu</th><th>Fonction prévue</th><th>État</th><th>Expire le</th><th></th></tr></thead><tbody>
  <?php foreach (($invitations ?? []) as $inv): ?>
    <tr>
      <td><?= htmlspecialchars($inv['inv_email'] ?? '') ?></td>
      <td><?= htmlspecialchars($inv['soc_nom'] ?? '') ?></td>
      <td><?= htmlspecialchars($inv['rol_nom'] ?? '—') ?></td>
      <td><?= htmlspecialchars($inv['fon_nom'] ?? '—') ?></td>
      <td><?= htmlspecialchars($inv['etat_calcule'] ?? '') ?></td>
      <td><?= htmlspecialchars($inv['inv_expire_le'] ?? '') ?></td>
      <td><a href="<?= url('/invitations/' . (int)$inv['inv_id'] . '/edit') ?>">Modifier</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table>
</section>
