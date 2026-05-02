<?php $function = $function ?? []; ?>
<section class="content-header">
  <div class="container-fluid"><h1><?= htmlspecialchars($page_title ?? 'Fonction') ?></h1></div>
</section>
<section class="content">
  <div class="container-fluid">
    <?php if (($errors ?? []) !== []): ?>
      <div class="alert alert-danger"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= htmlspecialchars($action ?? '/functions/store') ?>">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
      <div class="card">
        <div class="card-body">
          <div class="form-group">
            <label>Code</label>
            <input class="form-control" name="fnc_code" required value="<?= htmlspecialchars($function['fnc_code'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Libelle</label>
            <input class="form-control" name="fnc_label" required value="<?= htmlspecialchars($function['fnc_label'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" name="fnc_description"><?= htmlspecialchars($function['fnc_description'] ?? '') ?></textarea>
          </div>
          <?php if (!empty($isSuperAdmin) && empty($function)): ?>
            <div class="custom-control custom-checkbox">
              <input class="custom-control-input" id="fnc_is_global" type="checkbox" name="fnc_is_global" value="1">
              <label class="custom-control-label" for="fnc_is_global">Fonction globale</label>
            </div>
          <?php endif; ?>
        </div>
        <div class="card-footer">
          <button class="btn btn-primary">Enregistrer</button>
          <a class="btn btn-secondary" href="/functions">Annuler</a>
        </div>
      </div>
    </form>
  </div>
</section>
