<?php declare(strict_types=1);
$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="content-header"><div class="container-fluid d-flex justify-content-between"><h1><?= $h($role['rol_nom'] ?? 'Rôle') ?></h1><a href="/roles" class="btn btn-outline-secondary btn-sm">Retour</a></div></section>
<section class="content"><div class="container-fluid">
  <div class="card card-outline card-primary"><div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">Code</dt><dd class="col-sm-9"><code><?= $h($role['rol_code'] ?? '') ?></code></dd>
      <dt class="col-sm-3">Module</dt><dd class="col-sm-9"><?= $h($role['mod_nom'] ?? 'Global / noyau') ?></dd>
      <dt class="col-sm-3">Société propriétaire</dt><dd class="col-sm-9"><?= $h($role['societe_proprietaire_nom'] ?? 'Global') ?></dd>
      <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?= nl2br($h($role['rol_description'] ?? '')) ?></dd>
    </dl>
  </div><div class="card-footer text-right"><a class="btn btn-primary" href="/roles/<?= (int)$role['rol_id'] ?>/edit">Modifier</a></div></div>
  <div class="card"><div class="card-header"><h3 class="card-title">Permissions</h3></div><div class="card-body p-0 table-responsive">
    <table class="table table-sm mb-0"><thead><tr><th>Code</th><th>Module</th><th>Effet</th><th>Description</th></tr></thead><tbody>
      <?php foreach (($permissions ?? []) as $p): if (empty($p['rpe_effet'])) continue; ?>
        <tr><td><code><?= $h($p['per_code']) ?></code></td><td><?= $h($p['mod_nom'] ?? 'Noyau') ?></td><td><?= $p['rpe_effet'] === 'refuser' ? '<span class="badge badge-danger">Refuser</span>' : '<span class="badge badge-success">Autoriser</span>' ?></td><td><?= $h($p['per_description'] ?? '') ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div></div>
</div></section>
