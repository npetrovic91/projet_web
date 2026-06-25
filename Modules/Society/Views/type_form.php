<?php
declare(strict_types=1);
$type = $type ?? [];
$isEdit = !empty($type['tso_id']);
$action = $isEdit
    ? url('/companies/types/' . (int) $type['tso_id'] . '/update')
    : url('/companies/types/store');
$value = static fn(string $key): string => htmlspecialchars((string) ($type[$key] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Modifier le type de société' : 'Créer un type de société' ?></h1>
    <a href="<?= url('/companies/types') ?>" class="btn btn-outline-secondary">Retour</a>
</div>

<form method="post" action="<?= $e($action) ?>" class="card card-outline card-primary">
    <?= csrf_field() ?>
    <div class="card-body">
        <div class="form-group">
            <label for="tso_code">Code *</label>
            <input type="text" name="tso_code" id="tso_code" class="form-control" value="<?= $value('tso_code') ?>" pattern="[a-z0-9_]+" required>
            <small class="form-text text-muted">Lettres minuscules, chiffres et tirets bas uniquement.</small>
        </div>
        <div class="form-group">
            <label for="tso_nom">Nom *</label>
            <input type="text" name="tso_nom" id="tso_nom" class="form-control" value="<?= $value('tso_nom') ?>" required>
        </div>
        <div class="form-group">
            <label for="tso_description">Description</label>
            <textarea name="tso_description" id="tso_description" class="form-control" rows="3"><?= $value('tso_description') ?></textarea>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <?php if ($isEdit): ?>
            <button type="submit" class="btn btn-outline-danger" formaction="<?= url('/companies/types/' . (int) $type['tso_id'] . '/delete') ?>" data-confirm="Supprimer ce type de société ?">Supprimer</button>
        <?php endif; ?>
    </div>
</form>
