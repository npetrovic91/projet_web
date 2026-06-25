<?php declare(strict_types=1);
$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$filters = $filters ?? [];
?>
<section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center"><h1><i class="fas fa-project-diagram mr-2"></i>Politiques ABAC</h1><a href="/roles" class="btn btn-outline-secondary btn-sm">Rôles</a></div></section>
<section class="content"><div class="container-fluid">
  <div class="alert alert-info">Lecture alignée sur <code>sav_politiques_acces</code> et <code>sav_conditions_politiques_acces</code>. La création guidée des règles métier sera à traiter avec les écrans ABAC avancés.</div>
  <div class="card card-outline card-primary">
    <div class="card-header"><form method="get" class="form-inline"><input name="q" value="<?= $h($filters['q'] ?? '') ?>" class="form-control form-control-sm mr-2" placeholder="Code ou nom"><button class="btn btn-sm btn-outline-primary">Filtrer</button></form></div>
    <div class="card-body p-0 table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Code</th><th>Nom</th><th>Permission</th><th>Effet</th><th>Priorité</th><th>Conditions</th><th></th></tr></thead><tbody>
      <?php if (empty($policies)): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucune politique ABAC trouvée.</td></tr><?php endif; ?>
      <?php foreach (($policies ?? []) as $p): ?><tr><td><code><?= $h($p['pac_code'] ?? '') ?></code></td><td><?= $h($p['pac_nom'] ?? '') ?></td><td><code><?= $h($p['per_code'] ?? '') ?></code></td><td><?= $h($p['pac_effet'] ?? '') ?></td><td><?= (int)($p['pac_priorite'] ?? 0) ?></td><td><span class="badge badge-info"><?= (int)($p['conditions_count'] ?? 0) ?></span></td><td class="text-right"><a class="btn btn-xs btn-outline-primary" href="/access-policies/<?= (int)$p['pac_id'] ?>">Voir</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
</div></section>
