<?php
$job = $job ?? [];
$selectedTypes = array_map('intval', array_column($job['allowed_company_types'] ?? [], 'jct_company_type_id'));
?>
<section class="content-header">
  <div class="container-fluid"><h1><?= htmlspecialchars($page_title ?? 'Metier') ?></h1></div>
</section>
<section class="content">
  <div class="container-fluid">
    <?php if (($errors ?? []) !== []): ?>
      <div class="alert alert-danger"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= htmlspecialchars($action ?? '/jobs/store') ?>">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
      <div class="card">
        <div class="card-body">
          <div class="form-group">
            <label>Code</label>
            <input class="form-control" name="job_code" required value="<?= htmlspecialchars($job['job_code'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Libelle</label>
            <input class="form-control" name="job_label" required value="<?= htmlspecialchars($job['job_label'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" name="job_description"><?= htmlspecialchars($job['job_description'] ?? '') ?></textarea>
          </div>
          <?php if (!empty($isSuperAdmin) && empty($job)): ?>
            <div class="custom-control custom-checkbox mb-3">
              <input class="custom-control-input" id="job_is_global" type="checkbox" name="job_is_global" value="1">
              <label class="custom-control-label" for="job_is_global">Metier commun a toutes les structures</label>
            </div>
          <?php endif; ?>
          <div class="form-group">
            <label>Types d entreprises concernes</label>
            <select class="form-control" name="company_type_ids[]" multiple>
              <?php foreach (($companyTypes ?? []) as $type): ?>
                <option value="<?= (int) $type['cty_id'] ?>" <?= in_array((int) $type['cty_id'], $selectedTypes, true) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($type['cty_label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-primary">Enregistrer</button>
          <a class="btn btn-secondary" href="/jobs">Annuler</a>
        </div>
      </div>
    </form>
  </div>
</section>
