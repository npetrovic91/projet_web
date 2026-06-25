<?php
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$maintenance = $maintenance ?? [];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Verrous de maintenance</h1><p class="text-muted mb-0">Protection contre les maintenances concurrentes.</p></div><a class="btn btn-outline-secondary" href="<?= url('/verrous') ?>">Verrous d’entités</a></div>
  <div class="card"><div class="table-responsive"><table class="table table-striped table-hover mb-0"><thead><tr><th>Code</th><th>Exécution</th><th>Verrouillé par</th><th>Début</th><th>Expiration</th><th>Libéré</th><th>Message</th></tr></thead><tbody>
    <?php foreach ($maintenance as $v): ?><tr><td><?= $h($v['vma_code']) ?></td><td><?= $h($v['exm_code_politique'] ?? ('#' . (int)($v['vma_execution_maintenance_id'] ?? 0))) ?></td><td><?= $h($v['vma_verrouille_par'] ?? '') ?></td><td><?= $h($v['vma_verrouille_le']) ?></td><td><?= $h($v['vma_expire_le']) ?></td><td><?= $h($v['vma_libere_le'] ?? '—') ?></td><td><?= $h($v['vma_message'] ?? '') ?></td></tr><?php endforeach; ?>
    <?php if (!$maintenance): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucun verrou de maintenance.</td></tr><?php endif; ?>
  </tbody></table></div></div>
</div>
