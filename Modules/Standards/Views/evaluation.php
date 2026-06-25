<?php $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Évaluation : <?= $h(($version['std_code'] ?? '') . ' — ' . ($version['vst_version'] ?? '')) ?></h1><a class="btn btn-outline-secondary" href="/standards/versions">Versions</a></div>
  <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Société</th><th>Utilisateur</th><th>Email</th><th>Exigences</th><th>Obligatoires</th><th>Couvertes</th><th>Taux</th></tr></thead><tbody>
    <?php foreach (($rows ?? []) as $r): $total=(int)($r['exigences_total'] ?? 0); $ok=(int)($r['exigences_couvertes'] ?? 0); $rate=$total>0?round($ok*100/$total):0; ?>
      <tr><td><?= $h($r['soc_nom'] ?? '-') ?></td><td><?= $h(trim(($r['pui_prenom'] ?? '') . ' ' . ($r['pui_nom'] ?? '')) ?: ('#' . ($r['uti_id'] ?? ''))) ?></td><td><?= $h($r['uti_email'] ?? '') ?></td><td><?= $total ?></td><td><?= (int)($r['exigences_obligatoires'] ?? 0) ?></td><td><?= $ok ?></td><td><?= $rate ?>%</td></tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted">Aucune donnée d’évaluation.</td></tr><?php endif; ?>
  </tbody></table></div></div>
</div>
