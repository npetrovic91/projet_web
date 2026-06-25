<?php
$societes = $societes ?? $companies ?? [];
$filtres = $filtres ?? $filters ?? [];
?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">Sociétés</h3>
        <?php if (($canCreateSociete ?? false) || (function_exists('has_role') && has_role(['super_administrateur', 'super_admin', 'SUPERADMIN']))): ?>
            <a href="<?= url('/companies/create') ?>" class="btn btn-primary btn-sm">Nouvelle société</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="get" action="<?= url('/companies') ?>" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Recherche" value="<?= htmlspecialchars((string) ($filtres['recherche'] ?? $filtres['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-control">
                    <option value="">Tous les types</option>
                    <?php foreach (($types ?? []) as $type): ?>
                        <option value="<?= htmlspecialchars($type['cty_code'], ENT_QUOTES, 'UTF-8') ?>" <?= (($filtres['type'] ?? '') === $type['cty_code']) ? 'selected' : '' ?>><?= htmlspecialchars($type['cty_label'] ?? $type['cty_code'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="is_active" class="form-control">
                    <option value="">Tous statuts</option>
                    <option value="1" <?= (string) ($filtres['active'] ?? '') === '1' ? 'selected' : '' ?>>Actives</option>
                    <option value="0" <?= (string) ($filtres['active'] ?? '') === '0' ? 'selected' : '' ?>>Inactives</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filtrer</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Ville</th>
                    <th>Marques</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($societes)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Aucune société trouvée.</td></tr>
                <?php endif; ?>
                <?php foreach ($societes as $societe): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($societe['com_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($societe['com_legal_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><?= htmlspecialchars($societe['type_label'] ?? $societe['cty_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($societe['com_city'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) ($societe['nombre_marques'] ?? 0) ?></td>
                        <td>
                            <?php if (!empty($societe['com_is_active'])): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= url('/companies/' . (int) $societe['com_id']) ?>">Voir</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= url('/companies/' . (int) $societe['com_id'] . '/edit') ?>">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
