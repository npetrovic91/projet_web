<section class="content-header">
  <div class="container-fluid"><h1><?= htmlspecialchars($page_title ?? 'Notifications') ?></h1></div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-5">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Nouveau contact</h3></div>
          <form method="post" action="/notifications/contacts/store">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="company_id" value="<?= (int) ($company_id ?? 0) ?>">
            <div class="card-body">
              <div class="form-group"><label>ID utilisateur interne</label><input class="form-control" name="user_id" placeholder="Optionnel"></div>
              <div class="row">
                <div class="col-md-6 form-group"><label>Prenom</label><input class="form-control" name="firstname"></div>
                <div class="col-md-6 form-group"><label>Nom</label><input class="form-control" name="lastname"></div>
              </div>
              <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
              <div class="form-group"><label>Telephone</label><input class="form-control" name="phone"></div>
              <div class="form-group"><label>Societe externe</label><input class="form-control" name="company_name"></div>
              <div class="form-group"><label>Role</label><input class="form-control" name="role_label"></div>
              <div class="form-group">
                <label>Canal prefere</label>
                <select class="form-control" name="preferred_channel">
                  <option value="email">Email</option>
                  <option value="app">Application</option>
                </select>
              </div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Creer le contact</button></div>
          </form>
        </div>
      </div>
      <div class="col-md-7">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Nouvelle regle</h3></div>
          <form method="post" action="/notifications/rules/store">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="company_id" value="<?= (int) ($company_id ?? 0) ?>">
            <div class="card-body">
              <div class="form-group">
                <label>Evenement declencheur</label>
                <select class="form-control" name="event_trigger_id" required>
                  <?php foreach (($events ?? []) as $event): ?>
                    <option value="<?= (int) $event['evt_id'] ?>"><?= htmlspecialchars($event['evt_module'] . ' - ' . $event['evt_label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Contact</label>
                <select class="form-control" name="contact_id" required>
                  <?php foreach (($contacts ?? []) as $contact): ?>
                    <option value="<?= (int) $contact['nco_id'] ?>"><?= htmlspecialchars($contact['nco_email']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Canaux</label>
                <select class="form-control" name="channels[]" multiple>
                  <option value="email" selected>Email</option>
                  <option value="app">Application</option>
                </select>
              </div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Creer la regle</button></div>
          </form>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Contacts</h3></div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
          <thead><tr><th>Email</th><th>Type</th><th>Role</th><th>Statut</th><th></th></tr></thead>
          <tbody>
          <?php foreach (($contacts ?? []) as $contact): ?>
            <tr>
              <td><?= htmlspecialchars($contact['nco_email']) ?></td>
              <td><?= htmlspecialchars($contact['nco_contact_type']) ?></td>
              <td><?= htmlspecialchars($contact['nco_role_label'] ?? '-') ?></td>
              <td><?= !empty($contact['nco_is_active']) ? 'Actif' : 'Inactif' ?></td>
              <td class="text-right">
                <form method="post" action="/notifications/contacts/<?= (int) $contact['nco_id'] ?>/toggle">
                  <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                  <input type="hidden" name="active" value="<?= !empty($contact['nco_is_active']) ? 0 : 1 ?>">
                  <button class="btn btn-xs btn-outline-secondary"><?= !empty($contact['nco_is_active']) ? 'Desactiver' : 'Activer' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Regles actives</h3></div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
          <thead><tr><th>Evenement</th><th>Contact</th><th>Canaux</th><th>Statut</th><th></th></tr></thead>
          <tbody>
          <?php foreach (($rules ?? []) as $rule): ?>
            <tr>
              <td><?= htmlspecialchars($rule['evt_label']) ?></td>
              <td><?= htmlspecialchars($rule['nco_email']) ?></td>
              <td><?= htmlspecialchars($rule['nru_channels']) ?></td>
              <td><?= !empty($rule['nru_is_active']) ? 'Active' : 'Inactive' ?></td>
              <td class="text-right">
                <form method="post" action="/notifications/rules/<?= (int) $rule['nru_id'] ?>/toggle">
                  <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                  <input type="hidden" name="active" value="<?= !empty($rule['nru_is_active']) ? 0 : 1 ?>">
                  <button class="btn btn-xs btn-outline-secondary"><?= !empty($rule['nru_is_active']) ? 'Desactiver' : 'Activer' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
