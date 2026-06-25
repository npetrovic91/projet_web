<?php $row = $row ?? null; $listes = $listes ?? []; $isEdit = !empty($row); ?>
<section class="container py-3">
    <h1 class="h3 mb-3"><?= $isEdit ? 'Modifier le menu' : 'Créer un menu' ?></h1>
    <form method="post" action="<?= url($isEdit ? '/menus/' . (int)$row['men_id'] . '/update' : '/menus/store') ?>" class="card card-body">
        <?= $csrfField ?? csrf_field() ?>
        <div class="mb-3"><label class="form-label">Code</label><input class="form-control" name="men_code" value="<?= htmlspecialchars((string)($row['men_code'] ?? '')) ?>" required></div>
        <div class="mb-3"><label class="form-label">Nom</label><input class="form-control" name="men_nom" value="<?= htmlspecialchars((string)($row['men_nom'] ?? '')) ?>" required></div>
        <div class="mb-3"><label class="form-label">Module</label><select class="form-select" name="men_module_id"><option value="">Global</option><?php foreach (($listes['modules'] ?? []) as $m): ?><option value="<?= (int)$m['mod_id'] ?>" <?= (string)($row['men_module_id'] ?? '') === (string)$m['mod_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['mod_nom']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Statut</label><select class="form-select" name="men_statut_id"><option value="">Sans statut</option><?php foreach (($listes['statuts'] ?? []) as $s): ?><option value="<?= (int)$s['sta_id'] ?>" <?= (string)($row['men_statut_id'] ?? '') === (string)$s['sta_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['sta_libelle'] ?? $s['sta_code']) ?></option><?php endforeach; ?></select></div>
        <div class="d-flex gap-2"><button class="btn btn-primary">Enregistrer</button><a class="btn btn-outline-secondary" href="<?= url('/menus') ?>">Retour</a></div>
    </form>
    <?php if ($isEdit): ?><form class="mt-3" method="post" action="<?= url('/menus/' . (int)$row['men_id'] . '/delete') ?>" data-confirm="Supprimer logiquement ce menu et ses éléments ?"><?= $csrfField ?? csrf_field() ?><button class="btn btn-outline-danger">Supprimer logiquement</button></form><?php endif; ?>
</section>
