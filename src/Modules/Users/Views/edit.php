<?php
$user = $user ?? [];
$old = $old ?: $user;
$errors = $errors ?? [];
?>
<section class="content-header">
  <div class="container-fluid">
    <h1><?= htmlspecialchars($page_title ?? 'Modifier utilisateur') ?></h1>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <?php if ($errors !== []): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $message): ?>
            <li><?= htmlspecialchars((string) $message) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="/users/<?= (int) $user['use_id'] ?>/update">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Informations utilisateur</h3></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-2 form-group">
              <label>Civilite</label>
              <input class="form-control" name="civility" value="<?= htmlspecialchars($old['civility'] ?? $old['use_civility'] ?? '') ?>">
            </div>
            <div class="col-md-5 form-group">
              <label>Nom *</label>
              <input class="form-control" name="lastname" required value="<?= htmlspecialchars($old['lastname'] ?? $old['use_lastname'] ?? '') ?>">
            </div>
            <div class="col-md-5 form-group">
              <label>Prenom *</label>
              <input class="form-control" name="firstname" required value="<?= htmlspecialchars($old['firstname'] ?? $old['use_firstname'] ?? '') ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 form-group">
              <label>Email *</label>
              <input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($old['email'] ?? $old['use_email'] ?? '') ?>">
            </div>
            <div class="col-md-3 form-group">
              <label>Telephone</label>
              <input class="form-control" name="phone" value="<?= htmlspecialchars($old['phone'] ?? $old['use_phone'] ?? '') ?>">
            </div>
            <div class="col-md-3 form-group">
              <label>Mobile</label>
              <input class="form-control" name="mobile" value="<?= htmlspecialchars($old['mobile'] ?? $old['use_mobile'] ?? '') ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 form-group">
              <label>Numero employe</label>
              <input class="form-control" name="employee_number" value="<?= htmlspecialchars($old['employee_number'] ?? $old['use_employee_number'] ?? '') ?>">
            </div>
            <div class="col-md-4 form-group">
              <label>Service</label>
              <input class="form-control" name="department" value="<?= htmlspecialchars($old['department'] ?? $old['use_department'] ?? '') ?>">
            </div>
            <div class="col-md-4 form-group">
              <label>Poste</label>
              <input class="form-control" name="job_title" value="<?= htmlspecialchars($old['job_title'] ?? $old['use_job_title'] ?? '') ?>">
            </div>
          </div>
          <hr>
          <div class="row">
            <div class="col-md-6 form-group">
              <label>Nouveau mot de passe</label>
              <input class="form-control" type="password" name="password" autocomplete="new-password">
            </div>
            <div class="col-md-6 form-group">
              <label>Confirmation</label>
              <input class="form-control" type="password" name="password_confirm" autocomplete="new-password">
            </div>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-primary" type="submit">Enregistrer</button>
          <a class="btn btn-secondary" href="/users/<?= (int) $user['use_id'] ?>">Annuler</a>
        </div>
      </div>
    </form>
  </div>
</section>
