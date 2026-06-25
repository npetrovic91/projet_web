<?php $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Versions de standards</h1><a class="btn btn-outline-secondary" href="/standards">Standards</a></div>
  <form class="card card-body mb-3" method="post" action="/standards/versions/store">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-2 align-items-end">
      <div class="col-md-4"><label class="form-label">Standard</label><select class="form-select" name="vst_standard_id" required><?php foreach (($refs['standards'] ?? []) as $s): ?><option value="<?= (int)$s['std_id'] ?>"><?= $h($s['std_code'] . ' — ' . $s['std_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label">Version</label><input class="form-control" name="vst_version" required></div>
      <div class="col-md-2"><label class="form-label">Valide du</label><input class="form-control" type="date" name="vst_valide_du" required></div>
      <div class="col-md-2"><label class="form-label">Valide au</label><input class="form-control" type="date" name="vst_valide_au"></div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Ajouter</button></div>
    </div>
  </form>
  <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Standard</th><th>Version</th><th>Valide du</th><th>Valide au</th><th>Exigences</th><th>Obligatoires</th><th>État</th><th></th></tr></thead><tbody>
    <?php $etatLabels = ['draft' => ['Brouillon', 'secondary'], 'pending_validation' => ['En attente', 'warning'], 'approved' => ['Validé', 'success'], 'rejected' => ['Rejeté', 'danger'], 'archived' => ['Archivé', 'dark']]; ?>
    <?php $userIdCourant = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0); ?>
    <?php foreach (($versions ?? []) as $v): ?>
      <?php
        $etat = (string) ($v['vst_etat_validation'] ?? 'approved');
        [$etatLabel, $etatBadge] = $etatLabels[$etat] ?? [$etat, 'secondary'];
        $estAuteur = (int) ($v['vst_cree_par_utilisateur_id'] ?? 0) === $userIdCourant;
      ?>
      <tr>
        <td><code><?= $h($v['std_code']) ?></code> <?= $h($v['std_nom']) ?></td>
        <td><?= $h($v['vst_version']) ?></td>
        <td><?= $h($v['vst_valide_du']) ?></td>
        <td><?= $h($v['vst_valide_au'] ?? '-') ?></td>
        <td><?= (int)($v['total_exigences'] ?? 0) ?></td>
        <td><?= (int)($v['total_exigences_obligatoires'] ?? 0) ?></td>
        <td><span class="badge bg-<?= $h($etatBadge) ?>"><?= $h($etatLabel) ?></span></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="/standards/exigences?version_id=<?= (int)$v['vst_id'] ?>">Exigences</a>
          <a class="btn btn-sm btn-outline-secondary" href="/standards/versions/<?= (int)$v['vst_id'] ?>/evaluation">Évaluer</a>
          <?php if ($etat === 'draft' && $estAuteur): ?>
            <form method="post" action="/standards/versions/<?= (int)$v['vst_id'] ?>/soumettre" class="d-inline">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-warning" type="submit">Soumettre pour validation</button>
            </form>
          <?php elseif ($etat === 'pending_validation' && !$estAuteur): ?>
            <form method="post" action="/standards/versions/<?= (int)$v['vst_id'] ?>/valider" class="d-inline" data-confirm="Valider cette version de standard ?">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-success" type="submit">Valider</button>
            </form>
            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-target="#rejet-<?= (int)$v['vst_id'] ?>">Rejeter</button>
            <div class="collapse mt-2" id="rejet-<?= (int)$v['vst_id'] ?>">
              <form method="post" action="/standards/versions/<?= (int)$v['vst_id'] ?>/rejeter">
                <?= csrf_field() ?>
                <input class="form-control form-control-sm mb-1" type="text" name="motif" placeholder="Motif du rejet (obligatoire)" required>
                <button class="btn btn-sm btn-danger" type="submit">Confirmer le rejet</button>
              </form>
            </div>
          <?php elseif ($etat === 'pending_validation' && $estAuteur): ?>
            <span class="text-muted small">En attente d’un niveau supérieur</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($versions)): ?><tr><td colspan="8" class="text-center text-muted">Aucune version.</td></tr><?php endif; ?>
  </tbody></table></div></div>
</div>
