<?php defined('AUTOSAV_ROOT') or die;
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$isEdit = !empty($setting);
$decoded = !$isEdit || !empty($setting['pap_est_secret']) ? null : json_decode((string)($setting['pap_valeur_json'] ?? ''), true);
$value = is_array($decoded) && array_key_exists('valeur', $decoded) ? $decoded['valeur'] : '';
$valueType = is_bool($value) ? 'bool' : (is_int($value) ? 'int' : (is_float($value) ? 'float' : (is_array($value) ? 'json' : 'string')));
$plainValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $value;
$action = $isEdit ? '/settings/' . (int)$setting['pap_id'] . '/update' : '/settings/store';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Modifier paramètre' : 'Nouveau paramètre' ?></h1>
    <a class="btn btn-outline-secondary" href="/settings">Retour</a>
  </div>

  <form method="post" action="<?= $e($action) ?>" class="card card-outline card-primary">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrf_token ?? '') ?>">
    <div class="card-body">
      <div class="row">
        <div class="col-md-4 form-group">
          <label>Domaine</label>
          <input class="form-control" name="pap_domaine" required value="<?= $e($setting['pap_domaine'] ?? '') ?>" placeholder="application, maintenance, securite...">
        </div>
        <div class="col-md-4 form-group">
          <label>Clé</label>
          <input class="form-control" name="pap_cle" required value="<?= $e($setting['pap_cle'] ?? '') ?>" placeholder="mode_maintenance">
        </div>
        <div class="col-md-4 form-group">
          <label>Statut</label>
          <select class="form-control" name="pap_statut_id">
            <option value="">—</option>
            <?php foreach (($statuts ?? []) as $statut): ?>
              <option value="<?= (int)$statut['sta_id'] ?>" <?= ((int)($setting['pap_statut_id'] ?? 0) === (int)$statut['sta_id']) ? 'selected' : '' ?>><?= $e($statut['sta_libelle'] ?? $statut['sta_code']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea class="form-control" name="pap_description" rows="2"><?= $e($setting['pap_description'] ?? '') ?></textarea>
      </div>

      <div class="row">
        <div class="col-md-3 form-group">
          <label>Type de valeur</label>
          <select class="form-control" name="value_type">
            <?php foreach (['string' => 'Texte', 'bool' => 'Booléen', 'int' => 'Entier', 'float' => 'Décimal', 'json' => 'JSON'] as $k => $label): ?>
              <option value="<?= $e($k) ?>" <?= $valueType === $k ? 'selected' : '' ?>><?= $e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-9 form-group">
          <label>Valeur <?= !empty($setting['pap_est_secret']) ? '(laisser vide pour conserver le secret)' : '' ?></label>
          <textarea class="form-control" name="plain_value" rows="6" placeholder="Valeur ou JSON selon le type sélectionné"><?= $e($plainValue) ?></textarea>
        </div>
      </div>

      <div class="row">
        <div class="col-md-4">
          <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="pap_est_secret" name="pap_est_secret" value="1" <?= !empty($setting['pap_est_secret']) ? 'checked' : '' ?>>
            <label class="custom-control-label" for="pap_est_secret">Secret / valeur chiffrée</label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="pap_est_systeme" name="pap_est_systeme" value="1" <?= (!$isEdit || !empty($setting['pap_est_systeme'])) ? 'checked' : '' ?>>
            <label class="custom-control-label" for="pap_est_systeme">Paramètre système</label>
          </div>
        </div>
        <div class="col-md-4 form-group">
          <label>Référence coffre secret</label>
          <input class="form-control" name="pap_reference_coffre_secret" value="<?= $e($setting['pap_reference_coffre_secret'] ?? '') ?>" placeholder="vault://...">
        </div>
      </div>

      <?php if ($isEdit): ?>
        <div class="alert alert-light mb-0">
          <strong>Dernière modification :</strong> <?= $e($setting['pap_modifie_le'] ?? $setting['pap_cree_le'] ?? '—') ?>
          <?php if (!empty($setting['pap_algorithme_chiffrement'])): ?> — <strong>Chiffrement :</strong> <?= $e($setting['pap_algorithme_chiffrement']) ?><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="card-footer d-flex justify-content-between">
      <a class="btn btn-outline-secondary" href="/settings">Annuler</a>
      <button class="btn btn-primary">Enregistrer</button>
    </div>
  </form>
</div>
