<?php $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Règles de validation</h1>
    <div class="btn-group"><a class="btn btn-primary" href="/validation-rules/create">Nouvelle règle</a><a class="btn btn-outline-secondary" href="/validations">Demandes</a></div>
  </div>
  <form class="card card-body mb-3" method="get" action="/validation-rules">
    <div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label">Recherche</label><input class="form-control" name="q" value="<?= $h($filters['q'] ?? '') ?>"></div><div class="col-md-3"><label class="form-label">Société</label><select class="form-select" name="societe_id"><option value="">Toutes</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>" <?= ((int)($filters['societe_id'] ?? 0) === (int)$s['soc_id']) ? 'selected' : '' ?>><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-secondary w-100">Filtrer</button></div><div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="/validation-rules">Réinitialiser</a></div></div>
  </form>
  <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Code</th><th>Nom</th><th>Type opération</th><th>Module</th><th>Société</th><th>Validateurs</th><th>Permission validateur</th><th>Statut</th><th></th></tr></thead><tbody>
    <?php foreach (($regles ?? []) as $r): ?><tr><td><code><?= $h($r['rva_code'] ?? '') ?></code></td><td><?= $h($r['rva_nom'] ?? '') ?></td><td><?= $h($r['rva_type_operation'] ?? '') ?></td><td><?= $h($r['module_nom'] ?? '-') ?></td><td><?= $h($r['societe_nom'] ?? 'Global') ?></td><td><?= (int)($r['rva_nombre_validateurs_requis'] ?? 1) ?></td><td><code><?= $h($r['rva_permission_validateur_code'] ?? '-') ?></code></td><td><?= $h($r['statut_libelle'] ?? '-') ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/validation-rules/<?= (int)$r['rva_id'] ?>/edit">Modifier</a></td></tr><?php endforeach; ?>
    <?php if (empty($regles)): ?><tr><td colspan="9" class="text-center text-muted">Aucune règle.</td></tr><?php endif; ?>
  </tbody></table></div></div>
</div>
