<?php
declare(strict_types=1);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Types de sociétés</h1>
    <div>
        <a href="<?= url('/companies/types/create') ?>" class="btn btn-primary">Nouveau type</a>
        <a href="<?= url('/companies') ?>" class="btn btn-outline-secondary">Sociétés</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Sociétés actives</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($types ?? []) as $type): ?>
                        <tr>
                            <td><code><?= $e($type['tso_code']) ?></code></td>
                            <td><?= $e($type['tso_nom']) ?></td>
                            <td><?= $e($type['tso_description'] ?? '') ?></td>
                            <td><?= (int) ($type['societes_total'] ?? 0) ?></td>
                            <td class="text-right">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= url('/companies/types/' . (int) $type['tso_id'] . '/edit') ?>">Modifier</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($types)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun type de société.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
