<?php defined('AUTOSAV_ROOT') or die; ?>
<?php $editing = !empty($modele); ?>
<div class="container-fluid py-3">
  <h1 class="h3 mb-3"><?= $editing ? 'Modifier le modèle email' : 'Nouveau modèle email' ?></h1>
  <form method="post" action="<?= $editing ? '/emails/templates/' . (int)$modele['mel_id'] . '/update' : '/emails/templates/store' ?>">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
    <div class="card"><div class="card-body">
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Code</label>
          <input class="form-control" name="mel_code" required value="<?= htmlspecialchars((string)($modele['mel_code'] ?? '')) ?>" placeholder="ex: invitation_utilisateur">
        </div>
        <div class="form-group col-md-4">
          <label>Statut</label>
          <select class="form-control" name="mel_statut_id">
            <?php foreach (($statuts ?? []) as $s): ?>
              <option value="<?= (int)$s['sta_id'] ?>" <?= ((int)($modele['mel_statut_id'] ?? 0) === (int)$s['sta_id']) ? 'selected' : '' ?>><?= htmlspecialchars((string)$s['sta_libelle']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Sujet</label>
        <input class="form-control" name="mel_sujet" required value="<?= htmlspecialchars((string)($modele['mel_sujet'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label>Corps</label>
        <textarea class="form-control" name="mel_corps" rows="14" required><?= htmlspecialchars((string)($modele['mel_corps'] ?? '')) ?></textarea>
        <small class="form-text text-muted">Variables disponibles selon contexte : <code>{{firstname}}</code>, <code>{{lastname}}</code>, <code>{{fullname}}</code>, <code>{{email}}</code>, <code>{{societe}}</code>, <code>{{unsubscribe_url}}</code>.</small>
      </div>
      <button class="btn btn-primary">Enregistrer</button>
      <a class="btn btn-secondary" href="/emails/templates">Annuler</a>
    </div></div>
  </form>
</div>
