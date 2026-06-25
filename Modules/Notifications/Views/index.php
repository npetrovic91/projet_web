<?php
declare(strict_types=1);
$channels = is_array($channels ?? null) ? $channels : [];
$templates = is_array($templates ?? null) ? $templates : [];
$events = is_array($events ?? null) ? $events : [];
$contacts = is_array($contacts ?? null) ? $contacts : [];
$rules = is_array($rules ?? null) ? $rules : [];
$recent = is_array($recent_notifications ?? null) ? $recent_notifications : [];
?>
<section class="content-header">
  <div class="container-fluid"><h1><?= htmlspecialchars($page_title ?? 'Notifications et événements') ?></h1></div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="alert alert-info">
      Les notifications sont alignées sur les tables SQL réelles :
      <code>sav_notifications</code>, <code>sav_destinataires_notifications</code>,
      <code>sav_abonnements_evenements</code>, <code>sav_evenements_application</code>,
      <code>sav_contacts_societes</code>, <code>sav_canaux_notifications</code>,
      <code>sav_modeles_notifications</code> et <code>sav_preferences_notifications</code>.
    </div>

    <div class="row">
      <div class="col-md-5">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Nouveau contact de notification</h3></div>
          <form method="post" action="/notifications/contacts/store">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="company_id" value="<?= (int) ($company_id ?? 0) ?>">
            <div class="card-body">
              <div class="form-group"><label>ID utilisateur interne</label><input class="form-control" name="user_id" placeholder="Optionnel si contact externe"></div>
              <div class="row">
                <div class="col-md-6 form-group"><label>Prénom</label><input class="form-control" name="firstname"></div>
                <div class="col-md-6 form-group"><label>Nom</label><input class="form-control" name="lastname"></div>
              </div>
              <div class="form-group"><label>Email externe</label><input class="form-control" type="email" name="email" placeholder="Obligatoire si pas d'utilisateur interne"></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Créer le contact</button></div>
          </form>
        </div>
      </div>

      <div class="col-md-7">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Nouvel abonnement événement</h3></div>
          <form method="post" action="/notifications/rules/store">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="company_id" value="<?= (int) ($company_id ?? 0) ?>">
            <div class="card-body">
              <div class="form-group">
                <label>Événement déclencheur</label>
                <select class="form-control" name="event_code" required>
                  <?php foreach ($events as $event): ?>
                    <option value="<?= htmlspecialchars((string) $event['evt_code']) ?>">
                      <?= htmlspecialchars((string) $event['evt_module'] . ' — ' . (string) $event['evt_label'] . ' [' . (string) $event['evt_code'] . ']') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Contact destinataire</label>
                <select class="form-control" name="contact_id" required>
                  <?php foreach ($contacts as $contact): ?>
                    <?php if (!empty($contact['nco_is_active'])): ?>
                    <option value="<?= (int) $contact['nco_id'] ?>">
                      <?= htmlspecialchars(trim((string) ($contact['nco_firstname'] ?? '') . ' ' . (string) ($contact['nco_lastname'] ?? '')) ?: (string) $contact['nco_email']) ?>
                      — <?= htmlspecialchars((string) $contact['nco_email']) ?>
                    </option>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Canaux</label>
                <select class="form-control" name="channels[]" multiple>
                  <?php foreach ($channels as $channel): ?>
                    <option value="<?= htmlspecialchars((string) $channel['cno_code']) ?>" <?= $channel['cno_code'] === 'application' ? 'selected' : '' ?>>
                      <?= htmlspecialchars((string) $channel['cno_nom']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Modèle de notification</label>
                <select class="form-control" name="template_id">
                  <option value="">Message manuel / défaut</option>
                  <?php foreach ($templates as $template): ?>
                    <option value="<?= (int) $template['mno_id'] ?>"><?= htmlspecialchars((string) $template['mno_nom']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group"><label>Titre personnalisé</label><input class="form-control" name="title" placeholder="Optionnel"></div>
              <div class="form-group"><label>Message personnalisé</label><textarea class="form-control" name="message" rows="2" placeholder="Optionnel"></textarea></div>
              <div class="form-group"><label>Priorité</label><input class="form-control" type="number" min="1" max="9" name="priority" value="3"></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Créer l’abonnement</button></div>
          </form>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Contacts de la société active</h3></div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
          <thead><tr><th>Email</th><th>Nom</th><th>Type</th><th>Statut</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($contacts as $contact): ?>
            <tr>
              <td><?= htmlspecialchars((string) $contact['nco_email']) ?></td>
              <td><?= htmlspecialchars(trim((string) ($contact['nco_firstname'] ?? '') . ' ' . (string) ($contact['nco_lastname'] ?? '')) ?: '-') ?></td>
              <td><?= htmlspecialchars((string) $contact['nco_contact_type']) ?></td>
              <td><?= !empty($contact['nco_is_active']) ? 'Actif' : 'Inactif' ?></td>
              <td class="text-right">
                <form method="post" action="/notifications/contacts/<?= (int) $contact['nco_id'] ?>/toggle">
                  <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                  <input type="hidden" name="active" value="<?= !empty($contact['nco_is_active']) ? 0 : 1 ?>">
                  <button class="btn btn-xs btn-outline-secondary"><?= !empty($contact['nco_is_active']) ? 'Désactiver' : 'Activer' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Abonnements aux événements</h3></div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
          <thead><tr><th>Événement</th><th>Contact</th><th>Canaux</th><th>Statut</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($rules as $rule): ?>
            <tr>
              <td><?= htmlspecialchars((string) $rule['evt_label']) ?><br><small class="text-muted"><?= htmlspecialchars((string) $rule['evt_code']) ?></small></td>
              <td><?= htmlspecialchars((string) ($rule['nco_email'] ?? '-')) ?></td>
              <td><code><?= htmlspecialchars((string) $rule['nru_channels']) ?></code></td>
              <td><?= !empty($rule['nru_is_active']) ? 'Actif' : 'Inactif' ?></td>
              <td class="text-right">
                <form method="post" action="/notifications/rules/<?= (int) $rule['nru_id'] ?>/toggle">
                  <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                  <input type="hidden" name="active" value="<?= !empty($rule['nru_is_active']) ? 0 : 1 ?>">
                  <button class="btn btn-xs btn-outline-secondary"><?= !empty($rule['nru_is_active']) ? 'Désactiver' : 'Activer' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Notifications récentes</h3></div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
          <thead><tr><th>Date</th><th>Type</th><th>Titre</th><th>Destinataires</th><th>Non lues</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $notification): ?>
            <tr>
              <td><?= htmlspecialchars((string) $notification['not_cree_le']) ?></td>
              <td><?= htmlspecialchars((string) $notification['not_type']) ?></td>
              <td><?= htmlspecialchars((string) $notification['not_titre']) ?></td>
              <td><?= (int) ($notification['destinataires_total'] ?? 0) ?></td>
              <td><?= (int) ($notification['destinataires_non_lus'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
