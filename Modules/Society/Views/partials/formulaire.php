<?php
$societe = $societe ?? $company ?? [];
$erreurs = $erreurs ?? $errors ?? [];
$mode = $mode ?? 'creation';
$action = $mode === 'modification'
    ? url('/companies/' . (int) ($societe['soc_id'] ?? 0) . '/update')
    : url('/companies/store');
$val = static fn(string $cle, mixed $defaut = '') => htmlspecialchars((string) ($societe[$cle] ?? $defaut), ENT_QUOTES, 'UTF-8');
$typesSelectionnes = $societe['soc_types_ids'] ?? $societe['com_types_ids'] ?? $societe['types_ids'] ?? $societe['types_ids_csv'] ?? [];
if (!is_array($typesSelectionnes)) {
    $typesSelectionnes = preg_split('/\s*,\s*/', (string) $typesSelectionnes, -1, PREG_SPLIT_NO_EMPTY) ?: [];
}
if ($typesSelectionnes === []) {
    $typeHistorique = $societe['soc_type_id'] ?? $societe['com_type_id'] ?? $societe['type_id'] ?? null;
    $typesSelectionnes = $typeHistorique ? [(int) $typeHistorique] : [];
}
$typesSelectionnes = array_values(array_unique(array_map('intval', $typesSelectionnes)));
?>
<form method="post" action="<?= $e($action) ?>" class="card card-outline card-primary">
    <?= csrf_field() ?>
    <div class="card-header">
        <h3 class="card-title"><?= $mode === 'modification' ? 'Modifier la société' : 'Créer une société' ?></h3>
    </div>
    <div class="card-body">
        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <strong>Le formulaire contient des erreurs.</strong>
                <ul class="mb-0">
                    <?php foreach ($erreurs as $message): ?>
                        <li><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="soc_types_ids">Types *</label>
                    <select name="soc_types_ids[]" id="soc_types_ids" class="form-control" multiple size="8" required>
                        <?php foreach (($types ?? []) as $type): ?>
                            <option value="<?= (int) $type['cty_id'] ?>" <?= in_array((int) $type['cty_id'], $typesSelectionnes, true) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['cty_label'] ?? $type['cty_code'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Une société peut cumuler plusieurs types. Maintenez Ctrl ou Cmd pour sélectionner plusieurs lignes.</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="soc_nom">Nom *</label>
                    <input type="text" name="soc_nom" id="soc_nom" class="form-control" value="<?= $val('soc_nom') ?>" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="soc_nom_court">Nom court</label>
                    <input type="text" name="soc_nom_court" id="soc_nom_court" class="form-control" value="<?= $val('soc_nom_court') ?>">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4"><div class="form-group"><label>Raison sociale</label><input type="text" name="soc_nom_legal" class="form-control" value="<?= $val('soc_nom_legal') ?>"></div></div>
            <div class="col-md-4"><div class="form-group"><label>SIRET</label><input type="text" name="soc_siret" class="form-control" value="<?= $val('soc_siret') ?>"></div></div>
            <div class="col-md-4"><div class="form-group"><label>TVA intracommunautaire</label><input type="text" name="soc_numero_tva" class="form-control" value="<?= $val('soc_numero_tva') ?>"></div></div>
        </div>

        <div class="row">
            <div class="col-md-6"><div class="form-group"><label>Adresse</label><input type="text" name="soc_adresse" class="form-control" value="<?= $val('soc_adresse') ?>"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Code postal</label><input type="text" name="soc_code_postal" class="form-control" value="<?= $val('soc_code_postal') ?>"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Ville</label><input type="text" name="soc_ville" class="form-control" value="<?= $val('soc_ville') ?>"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Pays</label><input type="text" name="soc_pays" class="form-control" value="<?= $val('soc_pays', 'France') ?>"></div></div>
        </div>

        <div class="row">
            <div class="col-md-4"><div class="form-group"><label>Téléphone</label><input type="text" name="soc_telephone" class="form-control" value="<?= $val('soc_telephone') ?>"></div></div>
            <div class="col-md-4"><div class="form-group"><label>Email</label><input type="email" name="soc_email" class="form-control" value="<?= $val('soc_email') ?>"></div></div>
            <div class="col-md-4"><div class="form-group"><label>Site web</label><input type="url" name="soc_site_web" class="form-control" value="<?= $val('soc_site_web') ?>"></div></div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Holding</label>
                    <select name="soc_holding_id" class="form-control">
                        <option value="">Aucune</option>
                        <?php foreach (($holdings ?? []) as $holding): ?>
                            <option value="<?= (int) $holding['soc_id'] ?>" <?= (int) ($societe['soc_holding_id'] ?? 0) === (int) $holding['soc_id'] ? 'selected' : '' ?>><?= htmlspecialchars($holding['soc_nom'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Société parente / groupe</label>
                    <select name="soc_societe_parente_id" class="form-control">
                        <option value="">Aucune</option>
                        <?php foreach (($parents ?? []) as $parent): ?>
                            <?php if ((int) ($parent['soc_id'] ?? 0) === (int) ($societe['soc_id'] ?? 0)) continue; ?>
                            <option value="<?= (int) $parent['soc_id'] ?>" <?= (int) ($societe['soc_societe_parente_id'] ?? 0) === (int) $parent['soc_id'] ? 'selected' : '' ?>><?= htmlspecialchars($parent['soc_nom'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Statut</label>
                    <select name="soc_statut_code" class="form-control">
                        <?php foreach (['active' => 'Actif', 'inactive' => 'Inactif', 'pending' => 'En attente'] as $k => $label): ?>
                            <option value="<?= $k ?>" <?= ($societe['soc_statut_code'] ?? 'active') === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Active</label>
                    <select name="soc_est_active" class="form-control">
                        <option value="1" <?= (int) ($societe['soc_est_active'] ?? 1) === 1 ? 'selected' : '' ?>>Oui</option>
                        <option value="0" <?= (int) ($societe['soc_est_active'] ?? 1) === 0 ? 'selected' : '' ?>>Non</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-check">
            <input type="checkbox" name="soc_est_holding" value="1" id="soc_est_holding" class="form-check-input" <?= !empty($societe['soc_est_holding']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="soc_est_holding">Cette société est une holding</label>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="<?= url('/companies') ?>" class="btn btn-secondary">Retour</a>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </div>
</form>
