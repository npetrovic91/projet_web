<?php
$societe = $societe ?? $company ?? [];
$marques = $marques ?? $brands ?? [];
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><?= htmlspecialchars($societe['com_name'] ?? 'Société', ENT_QUOTES, 'UTF-8') ?></h3>
                <a href="<?= url('/companies/' . (int) ($societe['com_id'] ?? 0) . '/edit') ?>" class="btn btn-sm btn-primary">Modifier</a>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Raison sociale</dt><dd class="col-sm-8"><?= htmlspecialchars($societe['com_legal_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4">Type</dt><dd class="col-sm-8"><?= htmlspecialchars($societe['type_label'] ?? $societe['type_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4">SIRET</dt><dd class="col-sm-8"><?= htmlspecialchars($societe['com_siret'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4">Adresse</dt><dd class="col-sm-8"><?= htmlspecialchars(trim(($societe['soc_adresse'] ?? '') . ' ' . ($societe['soc_code_postal'] ?? '') . ' ' . ($societe['soc_ville'] ?? '')) ?: '-', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4">Téléphone</dt><dd class="col-sm-8"><?= htmlspecialchars($societe['com_phone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= htmlspecialchars($societe['com_email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4">Holding</dt><dd class="col-sm-8"><?= htmlspecialchars($societe['holding_nom'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4">Groupe parent</dt><dd class="col-sm-8"><?= htmlspecialchars($societe['parent_nom'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4">Statut</dt><dd class="col-sm-8"><?= !empty($societe['com_is_active']) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></dd>
                </dl>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title mb-0">Relations groupe / holding</h3></div>
            <div class="card-body">
                <?php if (empty($relations)): ?>
                    <p class="text-muted mb-0">Aucune relation active.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($relations as $relation): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= htmlspecialchars(($relation['parent_nom'] ?? '-') . ' → ' . ($relation['enfant_nom'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($relation['cor_relation_type'] ?? $relation['crl_type'] ?? 'relation', ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title mb-0">Marques de la société</h3></div>
            <div class="card-body">
                <?php if (empty($marques)): ?>
                    <p class="text-muted">Aucune marque rattachée.</p>
                <?php else: ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($marques as $marque): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($marque['brd_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($marque['cbr_is_primary'])): ?><span class="badge bg-primary">Principale</span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form method="post" action="<?= url('/companies/brand/attach') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="company_id" value="<?= (int) ($societe['com_id'] ?? 0) ?>">
                    <div class="form-group">
                        <label>Ajouter une marque</label>
                        <select name="brand_id" class="form-control">
                            <?php foreach (($marques_disponibles ?? []) as $marque): ?>
                                <option value="<?= (int) $marque['brd_id'] ?>"><?= htmlspecialchars($marque['brd_name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check my-2"><input type="checkbox" name="is_primary" value="1" class="form-check-input" id="is_primary"><label for="is_primary" class="form-check-label">Marque principale</label></div>
                    <button class="btn btn-success btn-sm" type="submit">Attacher</button>
                </form>
            </div>
        </div>

        <div class="card card-outline card-danger">
            <div class="card-header"><h3 class="card-title mb-0">Actions</h3></div>
            <div class="card-body">
                <form method="post" action="<?= url('/companies/' . (int) ($societe['com_id'] ?? 0) . '/delete') ?>" data-confirm="Supprimer cette société ?">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-danger btn-sm" type="submit">Supprimer logiquement</button>
                </form>
            </div>
        </div>
    </div>
</div>
