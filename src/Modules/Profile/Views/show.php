<?php $profile = $profile ?? []; ?>
<section class="content-header">
  <div class="container-fluid">
    <h1><?= htmlspecialchars($page_title ?? 'Mon profil') ?></h1>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Identite</h3></div>
          <div class="card-body">
            <p><strong><?= htmlspecialchars(($profile['use_firstname'] ?? '') . ' ' . ($profile['use_lastname'] ?? '')) ?></strong></p>
            <p><?= htmlspecialchars($profile['use_email'] ?? '') ?></p>
            <p>Entreprise active : <?= htmlspecialchars($profile['active_company_name'] ?? '-') ?></p>
            <p>Marque active : <?= htmlspecialchars($profile['active_brand_name'] ?? '-') ?></p>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h3 class="card-title">Mot de passe</h3></div>
          <form method="post" action="/profile/password">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <div class="card-body">
              <div class="form-group"><label>Mot de passe actuel</label><input class="form-control" type="password" name="current_password"></div>
              <div class="form-group"><label>Nouveau mot de passe</label><input class="form-control" type="password" name="password"></div>
              <div class="form-group"><label>Confirmation</label><input class="form-control" type="password" name="password_confirm"></div>
            </div>
            <div class="card-footer"><button class="btn btn-secondary">Modifier</button></div>
          </form>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Donnees modifiables</h3></div>
          <form method="post" action="/profile/update">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <div class="card-body">
              <div class="row">
                <div class="col-md-2 form-group"><label>Civilite</label><input class="form-control" name="civility" value="<?= htmlspecialchars($profile['use_civility'] ?? '') ?>"></div>
                <div class="col-md-5 form-group"><label>Nom</label><input class="form-control" name="lastname" required value="<?= htmlspecialchars($profile['use_lastname'] ?? '') ?>"></div>
                <div class="col-md-5 form-group"><label>Prenom</label><input class="form-control" name="firstname" required value="<?= htmlspecialchars($profile['use_firstname'] ?? '') ?>"></div>
              </div>
              <div class="row">
                <div class="col-md-6 form-group"><label>Email</label><input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($profile['use_email'] ?? '') ?>"></div>
                <div class="col-md-3 form-group"><label>Telephone</label><input class="form-control" name="phone" value="<?= htmlspecialchars($profile['use_phone'] ?? '') ?>"></div>
                <div class="col-md-3 form-group"><label>Mobile</label><input class="form-control" name="mobile" value="<?= htmlspecialchars($profile['use_mobile'] ?? '') ?>"></div>
              </div>
              <div class="row">
                <div class="col-md-6 form-group"><label>Langue</label><input class="form-control" name="locale" value="<?= htmlspecialchars($profile['use_locale'] ?? 'fr') ?>"></div>
                <div class="col-md-6 form-group"><label>Fuseau horaire</label><input class="form-control" name="timezone" value="<?= htmlspecialchars($profile['use_timezone'] ?? 'Europe/Paris') ?>"></div>
              </div>
              <div class="alert alert-light border mb-0">
                Les champs sensibles comme les roles, rattachements, fonctions, metiers, qualifications validees et statut de securite sont reserves a l administration habilitee.
              </div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Enregistrer</button></div>
          </form>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header"><h3 class="card-title">Competences</h3></div>
              <div class="card-body p-0">
                <table class="table table-sm mb-0">
                  <tbody>
                  <?php foreach (($profile['skills'] ?? []) as $skill): ?>
                    <tr><td><?= htmlspecialchars($skill['skl_label']) ?></td><td><?= htmlspecialchars($skill['usk_level']) ?></td></tr>
                  <?php endforeach; ?>
                  <?php if (($profile['skills'] ?? []) === []): ?><tr><td class="text-muted p-3">Aucune competence renseignee.</td></tr><?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-header"><h3 class="card-title">Qualifications</h3></div>
              <div class="card-body p-0">
                <table class="table table-sm mb-0">
                  <tbody>
                  <?php foreach (($profile['qualifications'] ?? []) as $qualification): ?>
                    <tr>
                      <td><?= htmlspecialchars($qualification['qua_label']) ?></td>
                      <td><?= htmlspecialchars($qualification['uqu_expires_at'] ?? 'Sans expiration') ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (($profile['qualifications'] ?? []) === []): ?><tr><td class="text-muted p-3">Aucune qualification renseignee.</td></tr><?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <a class="btn btn-outline-info" href="/profile/gdpr">Mes donnees personnelles et demandes RGPD</a>
      </div>
    </div>
  </div>
</section>
