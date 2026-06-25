<?php declare(strict_types=1);
$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="content-header"><div class="container-fluid d-flex justify-content-between"><h1><?= $h($policy['pac_nom'] ?? 'Politique ABAC') ?></h1><a href="/access-policies" class="btn btn-outline-secondary btn-sm">Retour</a></div></section>
<section class="content"><div class="container-fluid">
  <div class="card card-outline card-primary"><div class="card-body"><dl class="row mb-0"><dt class="col-sm-3">Code</dt><dd class="col-sm-9"><code><?= $h($policy['pac_code'] ?? '') ?></code></dd><dt class="col-sm-3">Permission</dt><dd class="col-sm-9"><code><?= $h($policy['per_code'] ?? '') ?></code></dd><dt class="col-sm-3">Effet</dt><dd class="col-sm-9"><?= $h($policy['pac_effet'] ?? '') ?></dd><dt class="col-sm-3">Priorité</dt><dd class="col-sm-9"><?= (int)($policy['pac_priorite'] ?? 0) ?></dd></dl></div></div>
  <div class="card"><div class="card-header"><h3 class="card-title">Conditions</h3></div><div class="card-body p-0"><table class="table table-sm mb-0"><thead><tr><th>Attribut</th><th>Opérateur</th><th>Valeur JSON</th></tr></thead><tbody><?php foreach (($conditions ?? []) as $c): ?><tr><td><?= $h($c['cpa_attribut'] ?? '') ?></td><td><?= $h($c['cpa_operateur'] ?? '') ?></td><td><code><?= $h($c['cpa_valeur_json'] ?? '') ?></code></td></tr><?php endforeach; ?></tbody></table></div></div>
</div></section>
