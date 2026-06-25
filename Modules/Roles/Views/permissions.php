<?php declare(strict_types=1);
$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$filters = $filters ?? [];
?>
<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-key mr-2"></i>Permissions</h1>
    <div>
      <a href="/roles" class="btn btn-outline-secondary btn-sm">Rôles</a>
      <a href="/permissions/create" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Nouvelle permission</a>
    </div>
  </div>
</section>
<section class="content"><div class="container-fluid">
  <div class="card card-outline card-primary">
    <div class="card-header">
      <form method="get" class="form-inline">
        <input type="search" name="q" value="<?= $h($filters['q'] ?? '') ?>" class="form-control form-control-sm mr-2" placeholder="Code ou description">
        <select name="module_id" class="form-control form-control-sm mr-2"><option value="">Tous les modules</option><?php foreach (($modules ?? []) as $m): ?><option value="<?= (int)$m['mod_id'] ?>" <?= ((string)($filters['module_id'] ?? '') === (string)$m['mod_id']) ? 'selected' : '' ?>><?= $h($m['mod_nom']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-sm btn-outline-primary" type="submit">Filtrer</button>
      </form>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead class="thead-light"><tr><th>Code</th><th>Module</th><th>Description</th><th>Rôles</th><th>Statut</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
        <?php if (empty($permissions)): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucune permission trouvée.</td></tr><?php endif; ?>
        <?php foreach (($permissions ?? []) as $p): ?>
          <tr>
            <td><code><?= $h($p['per_code'] ?? '') ?></code></td>
            <td><?= $h($p['mod_nom'] ?? 'Noyau') ?></td>
            <td><?= $h($p['per_description'] ?? '') ?></td>
            <td><span class="badge badge-success">+<?= (int)($p['roles_autorisant_count'] ?? 0) ?></span> <span class="badge badge-danger">-<?= (int)($p['roles_refusant_count'] ?? 0) ?></span></td>
            <td><?= $h($p['statut_libelle'] ?? $p['statut_code'] ?? '—') ?></td>
            <td class="text-right"><a class="btn btn-xs btn-outline-primary" href="/permissions/<?= (int)$p['per_id'] ?>/edit">Modifier</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></section>
