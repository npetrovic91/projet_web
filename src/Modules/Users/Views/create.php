<?php
$old = $old ?? [];
$errors = $errors ?? [];
?>
<section class="content-header">
  <div class="container-fluid">
    <h1><?= htmlspecialchars($page_title ?? 'Creer un utilisateur') ?></h1>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <?php if ($errors !== []): ?>
      <div class="alert alert-danger">
        <strong>Validation impossible.</strong>
        <ul class="mb-0">
          <?php foreach ($errors as $message): ?>
            <li><?= htmlspecialchars((string) $message) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="/users/store">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
      <div class="row">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header"><h3 class="card-title">Identite et contact</h3></div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-2 form-group">
                  <label>Civilite</label>
                  <input class="form-control" name="civility" value="<?= htmlspecialchars($old['civility'] ?? '') ?>">
                </div>
                <div class="col-md-5 form-group">
                  <label>Nom *</label>
                  <input class="form-control" name="lastname" required maxlength="120" value="<?= htmlspecialchars($old['lastname'] ?? '') ?>">
                </div>
                <div class="col-md-5 form-group">
                  <label>Prenom *</label>
                  <input class="form-control" name="firstname" required maxlength="120" value="<?= htmlspecialchars($old['firstname'] ?? '') ?>">
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 form-group">
                  <label>Email *</label>
                  <input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </div>
                <div class="col-md-3 form-group">
                  <label>Telephone</label>
                  <input class="form-control" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>
                <div class="col-md-3 form-group">
                  <label>Mobile</label>
                  <input class="form-control" name="mobile" value="<?= htmlspecialchars($old['mobile'] ?? '') ?>">
                </div>
              </div>
              <div class="row">
                <div class="col-md-4 form-group">
                  <label>Numero employe</label>
                  <input class="form-control" name="employee_number" value="<?= htmlspecialchars($old['employee_number'] ?? '') ?>">
                </div>
                <div class="col-md-4 form-group">
                  <label>Service</label>
                  <input class="form-control" name="department" value="<?= htmlspecialchars($old['department'] ?? '') ?>">
                </div>
                <div class="col-md-4 form-group">
                  <label>Poste</label>
                  <input class="form-control" name="job_title" value="<?= htmlspecialchars($old['job_title'] ?? '') ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><h3 class="card-title">Mot de passe initial</h3></div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6 form-group">
                  <label>Mot de passe</label>
                  <input class="form-control" type="password" name="password" autocomplete="new-password">
                </div>
                <div class="col-md-6 form-group">
                  <label>Confirmation</label>
                  <input class="form-control" type="password" name="password_confirm" autocomplete="new-password">
                </div>
              </div>
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="must_change_password" name="must_change_password" value="1" checked>
                <label class="custom-control-label" for="must_change_password">Forcer le changement a la premiere connexion</label>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card">
            <div class="card-header"><h3 class="card-title">Perimetre</h3></div>
            <div class="card-body">
              <div class="form-group">
                <label>Role *</label>
                <select class="form-control" name="role_id" required>
                  <option value="">Selectionner</option>
                  <?php foreach (($roles ?? []) as $role): ?>
                    <option value="<?= (int) $role['rol_id'] ?>" <?= (string) ($old['role_id'] ?? '') === (string) $role['rol_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($role['rol_label']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Entreprise principale *</label>
                <select class="form-control" name="company_id" required>
                  <option value="">Selectionner</option>
                  <?php foreach (($companies ?? []) as $company): ?>
                    <?php $companyId = $company['com_id'] ?? $company['ucm_company_id']; ?>
                    <option value="<?= (int) $companyId ?>" <?= (string) ($old['company_id'] ?? '') === (string) $companyId ? 'selected' : '' ?>>
                      <?= htmlspecialchars($company['com_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Date d arrivee</label>
                <input class="form-control" type="date" name="joined_at" value="<?= htmlspecialchars($old['joined_at'] ?? date('Y-m-d')) ?>">
              </div>
              <button class="btn btn-primary btn-block" type="submit">Creer</button>
              <a href="/users" class="btn btn-secondary btn-block">Annuler</a>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</section>
