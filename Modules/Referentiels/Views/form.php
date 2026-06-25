<?php defined('AUTOSAV_ROOT') or die;
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$isEdit = !empty($row);
$id = match ($type) {
  'pays' => (int)($row['pay_id'] ?? 0), 'devises' => (int)($row['dev_id'] ?? 0), 'fuseaux' => (int)($row['fuh_id'] ?? 0),
  'tva' => (int)($row['tva_id'] ?? 0), 'statuts' => (int)($row['sta_id'] ?? 0), 'transitions' => (int)($row['tst_id'] ?? 0), default => 0,
};
$action = $isEdit ? "/referentiels/{$type}/{$id}/update" : "/referentiels/{$type}/store";
$title = [
  'pays' => 'Pays', 'devises' => 'Devise', 'fuseaux' => 'Fuseau horaire', 'tva' => 'Taux de TVA', 'statuts' => 'Statut', 'transitions' => 'Transition de statut'
][$type] ?? 'Référentiel';
$checked = static fn(mixed $v): string => !empty($v) ? 'checked' : '';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-0"><?= $isEdit ? 'Modifier' : 'Créer' ?> : <?= $e($title) ?></h1>
      <p class="text-muted mb-0">Formulaire aligné sur le schéma SQL actuel.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/referentiels/<?= $e($type) ?>">Retour</a>
  </div>

  <form method="post" action="<?= $e($action) ?>" class="card">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrf_token ?? '') ?>">
    <div class="card-body">
      <?php if ($type === 'pays'): ?>
        <div class="row">
          <div class="col-md-3 form-group"><label>Code ISO2</label><input class="form-control" name="pay_code_iso2" maxlength="2" value="<?= $e($row['pay_code_iso2'] ?? '') ?>" required></div>
          <div class="col-md-3 form-group"><label>Code ISO3</label><input class="form-control" name="pay_code_iso3" maxlength="3" value="<?= $e($row['pay_code_iso3'] ?? '') ?>" required></div>
          <div class="col-md-6 form-group"><label>Nom</label><input class="form-control" name="pay_nom" value="<?= $e($row['pay_nom'] ?? '') ?>" required></div>
        </div>
        <label><input type="checkbox" name="pay_est_actif" value="1" <?= $checked($row['pay_est_actif'] ?? 1) ?>> Pays actif</label>
      <?php elseif ($type === 'devises'): ?>
        <div class="row">
          <div class="col-md-3 form-group"><label>Code ISO</label><input class="form-control" name="dev_code_iso" maxlength="3" value="<?= $e($row['dev_code_iso'] ?? '') ?>" required></div>
          <div class="col-md-4 form-group"><label>Nom</label><input class="form-control" name="dev_nom" value="<?= $e($row['dev_nom'] ?? '') ?>" required></div>
          <div class="col-md-2 form-group"><label>Symbole</label><input class="form-control" name="dev_symbole" value="<?= $e($row['dev_symbole'] ?? '') ?>"></div>
          <div class="col-md-3 form-group"><label>Nombre de décimales</label><input type="number" min="0" max="9" class="form-control" name="dev_nombre_decimales" value="<?= $e($row['dev_nombre_decimales'] ?? 2) ?>"></div>
        </div>
        <label><input type="checkbox" name="dev_est_active" value="1" <?= $checked($row['dev_est_active'] ?? 1) ?>> Devise active</label>
      <?php elseif ($type === 'fuseaux'): ?>
        <div class="row">
          <div class="col-md-6 form-group"><label>Nom IANA</label><input class="form-control" name="fuh_nom_iana" placeholder="Europe/Paris" value="<?= $e($row['fuh_nom_iana'] ?? '') ?>" required></div>
          <div class="col-md-6 form-group"><label>Libellé</label><input class="form-control" name="fuh_libelle" value="<?= $e($row['fuh_libelle'] ?? '') ?>"></div>
        </div>
        <label><input type="checkbox" name="fuh_est_actif" value="1" <?= $checked($row['fuh_est_actif'] ?? 1) ?>> Fuseau actif</label>
      <?php elseif ($type === 'tva'): ?>
        <div class="row">
          <div class="col-md-4 form-group"><label>Pays</label><select class="form-control" name="tva_pays_id" required><option value="">Sélectionner</option><?php foreach (($pays ?? []) as $p): ?><option value="<?= (int)$p['pay_id'] ?>" <?= ((int)($row['tva_pays_id'] ?? 0) === (int)$p['pay_id']) ? 'selected' : '' ?>><?= $e($p['pay_nom'] ?? '') ?></option><?php endforeach; ?></select></div>
          <div class="col-md-2 form-group"><label>Code</label><input class="form-control" name="tva_code" value="<?= $e($row['tva_code'] ?? '') ?>" required></div>
          <div class="col-md-3 form-group"><label>Nom</label><input class="form-control" name="tva_nom" value="<?= $e($row['tva_nom'] ?? '') ?>" required></div>
          <div class="col-md-3 form-group"><label>Taux %</label><input class="form-control" name="tva_taux" value="<?= $e($row['tva_taux'] ?? '20.00') ?>" required></div>
        </div>
        <div class="row">
          <div class="col-md-4 form-group"><label>Débute le</label><input type="date" class="form-control" name="tva_debute_le" value="<?= $e($row['tva_debute_le'] ?? date('Y-m-d')) ?>" required></div>
          <div class="col-md-4 form-group"><label>Termine le</label><input type="date" class="form-control" name="tva_termine_le" value="<?= $e($row['tva_termine_le'] ?? '') ?>"></div>
          <div class="col-md-4 form-group"><label>Statut</label><select class="form-control" name="tva_statut_id"><option value="">Aucun</option><?php foreach (($statuts ?? []) as $s): ?><option value="<?= (int)$s['sta_id'] ?>" <?= ((int)($row['tva_statut_id'] ?? 0) === (int)$s['sta_id']) ? 'selected' : '' ?>><?= $e(($s['sta_domaine'] ?? '') . ' / ' . ($s['sta_libelle'] ?? '')) ?></option><?php endforeach; ?></select></div>
        </div>
      <?php elseif ($type === 'statuts'): ?>
        <div class="row">
          <div class="col-md-3 form-group"><label>Domaine</label><input class="form-control" name="sta_domaine" value="<?= $e($row['sta_domaine'] ?? '') ?>" required></div>
          <div class="col-md-3 form-group"><label>Code</label><input class="form-control" name="sta_code" value="<?= $e($row['sta_code'] ?? '') ?>" required></div>
          <div class="col-md-4 form-group"><label>Libellé</label><input class="form-control" name="sta_libelle" value="<?= $e($row['sta_libelle'] ?? '') ?>" required></div>
          <div class="col-md-2 form-group"><label>Ordre</label><input type="number" class="form-control" name="sta_ordre" value="<?= $e($row['sta_ordre'] ?? 100) ?>"></div>
        </div>
        <div class="row">
          <div class="col-md-4 form-group"><label>Table entité</label><input class="form-control" name="sta_entite_table" value="<?= $e($row['sta_entite_table'] ?? '') ?>"></div>
          <div class="col-md-4 form-group"><label>Couleur</label><input class="form-control" name="sta_couleur" value="<?= $e($row['sta_couleur'] ?? '') ?>"></div>
          <div class="col-md-4 form-group"><label>Icône</label><input class="form-control" name="sta_icone" value="<?= $e($row['sta_icone'] ?? '') ?>"></div>
        </div>
        <div class="form-group"><label>Description</label><textarea class="form-control" name="sta_description" rows="3"><?= $e($row['sta_description'] ?? '') ?></textarea></div>
        <label class="mr-3"><input type="checkbox" name="sta_est_initial" value="1" <?= $checked($row['sta_est_initial'] ?? 0) ?>> Initial</label>
        <label class="mr-3"><input type="checkbox" name="sta_est_final" value="1" <?= $checked($row['sta_est_final'] ?? 0) ?>> Final</label>
        <label class="mr-3"><input type="checkbox" name="sta_est_systeme" value="1" <?= $checked($row['sta_est_systeme'] ?? 1) ?>> Système</label>
        <label><input type="checkbox" name="sta_est_actif" value="1" <?= $checked($row['sta_est_actif'] ?? 1) ?>> Actif</label>
      <?php elseif ($type === 'transitions'): ?>
        <div class="row">
          <div class="col-md-3 form-group"><label>Domaine</label><input class="form-control" name="tst_domaine" value="<?= $e($row['tst_domaine'] ?? '') ?>" required></div>
          <div class="col-md-4 form-group"><label>Statut source</label><select class="form-control" name="tst_statut_source_id" required><option value="">Sélectionner</option><?php foreach (($statuts ?? []) as $s): ?><option value="<?= (int)$s['sta_id'] ?>" <?= ((int)($row['tst_statut_source_id'] ?? 0) === (int)$s['sta_id']) ? 'selected' : '' ?>><?= $e(($s['sta_domaine'] ?? '') . ' / ' . ($s['sta_libelle'] ?? '')) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4 form-group"><label>Statut cible</label><select class="form-control" name="tst_statut_cible_id" required><option value="">Sélectionner</option><?php foreach (($statuts ?? []) as $s): ?><option value="<?= (int)$s['sta_id'] ?>" <?= ((int)($row['tst_statut_cible_id'] ?? 0) === (int)$s['sta_id']) ? 'selected' : '' ?>><?= $e(($s['sta_domaine'] ?? '') . ' / ' . ($s['sta_libelle'] ?? '')) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-group"><label>Permission requise</label><input class="form-control" name="tst_permission_code" value="<?= $e($row['tst_permission_code'] ?? '') ?>" placeholder="exemple : validation.gerer"></div>
        <label class="mr-3"><input type="checkbox" name="tst_motif_obligatoire" value="1" <?= $checked($row['tst_motif_obligatoire'] ?? 0) ?>> Motif obligatoire</label>
        <label class="mr-3"><input type="checkbox" name="tst_validation_requise" value="1" <?= $checked($row['tst_validation_requise'] ?? 0) ?>> Validation requise</label>
        <label class="mr-3"><input type="checkbox" name="tst_notification_requise" value="1" <?= $checked($row['tst_notification_requise'] ?? 0) ?>> Notification requise</label>
        <label><input type="checkbox" name="tst_est_active" value="1" <?= $checked($row['tst_est_active'] ?? 1) ?>> Transition active</label>
      <?php endif; ?>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Enregistrer</button></div>
  </form>
</div>
