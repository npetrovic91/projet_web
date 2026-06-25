<?php $data = $data ?? []; $stats = $data['stats'] ?? []; ?>
<section class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Menus dynamiques</h1>
            <p class="text-muted mb-0">Navigation filtrée par société active, modules activés et permissions utilisateur.</p>
        </div>
        <div class="btn-group">
            <a class="btn btn-primary" href="<?= url('/menus/create') ?>">Créer un menu</a>
            <a class="btn btn-outline-primary" href="<?= url('/menus/elements/create') ?>">Créer un élément</a>
            <a class="btn btn-outline-secondary" href="<?= url('/menus/export.json') ?>">Export JSON</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ([
            'menus' => 'Menus',
            'elements' => 'Éléments',
            'modules_actifs_societe' => 'Modules actifs société',
            'permissions_liees' => 'Permissions liées',
        ] as $key => $label): ?>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small"><?= htmlspecialchars($label) ?></div><div class="h3 mb-0"><?= (int)($stats[$key] ?? 0) ?></div></div></div></div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100"><div class="card-header">Navigation active</div><div class="card-body">
                <?php foreach (($data['navigation'] ?? []) as $menu): ?>
                    <h2 class="h5 mt-2"><?= htmlspecialchars($menu['nom'] ?? '') ?> <small class="text-muted">/ <?= htmlspecialchars($menu['code'] ?? '') ?></small></h2>
                    <?php if (empty($menu['items'])): ?><p class="text-muted">Aucun élément visible pour le contexte actuel.</p><?php endif; ?>
                    <ul class="list-group mb-3">
                        <?php foreach (($menu['items'] ?? []) as $item): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($item['libelle'] ?? '') ?></strong>
                                <?php if (!empty($item['route'])): ?><span class="text-muted"> — <?= htmlspecialchars($item['route']) ?></span><?php endif; ?>
                                <?php if (!empty($item['permission_code'])): ?><span class="badge bg-secondary"><?= htmlspecialchars($item['permission_code']) ?></span><?php endif; ?>
                                <?php if (!empty($item['enfants'])): ?>
                                    <ul class="mt-2">
                                        <?php foreach ($item['enfants'] as $enfant): ?><li><?= htmlspecialchars($enfant['libelle']) ?> <span class="text-muted"><?= htmlspecialchars((string)($enfant['route'] ?? '')) ?></span></li><?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-header">Accès rapides</div><div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="<?= url('/menus') ?>">Gérer les menus</a>
                <a class="list-group-item list-group-item-action" href="<?= url('/menus/elements') ?>">Gérer les éléments</a>
                <a class="list-group-item list-group-item-action" href="<?= url('/menus/navigation.json') ?>">Voir la navigation JSON active</a>
            </div></div>
        </div>
    </div>
</section>
