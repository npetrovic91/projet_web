<?php
declare(strict_types=1);
$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$typesLabels = ['role' => 'Rôle', 'fonction' => 'Fonction', 'competence' => 'Compétence', 'certification' => 'Certification'];
$statutLabels = ['preview' => ['En attente de confirmation', 'warning'], 'executee' => ['Exécutée', 'success'], 'rollback' => ['Annulée (rollback)', 'secondary'], 'erreur' => ['Erreur', 'danger']];
?>
<section class="content-header"><div class="container-fluid"><h1><i class="fas fa-share-alt mr-2"></i>Propagation groupe → concessions</h1></div></section>
<section class="content"><div class="container-fluid">
  <div class="alert alert-info">
    Diffusez un rôle, une fonction, une compétence ou une certification que vous détenez au niveau du groupe vers
    les concessions rattachées. Une <strong>prévisualisation</strong> est obligatoire avant toute exécution : les
    conflits (élément déjà existant) sont détectés et jamais écrasés. Toute propagation exécutée peut être annulée
    (rollback) tant qu'elle reste visible dans l'historique ci-dessous.
  </div>

  <div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title">1. Choisir l'élément à propager</h3></div>
    <div class="card-body">
      <form method="get" action="/roles/propagation" class="form-inline mb-3">
        <label class="mr-2">Type</label>
        <select name="type" id="type-select" class="form-control mr-2">
          <?php foreach (($types ?? []) as $t): ?>
            <option value="<?= $h($t) ?>" <?= ($type ?? '') === $t ? 'selected' : '' ?>><?= $h($typesLabels[$t] ?? $t) ?></option>
          <?php endforeach; ?>
        </select>
      </form>

      <div class="row">
        <div class="col-md-6">
          <label>Élément (catalogue du groupe)</label>
          <select id="cible_id" class="form-control">
            <option value="">— Sélectionner —</option>
            <?php foreach (($catalogue ?? []) as $item): ?>
              <?php $valeurs = array_values($item); ?>
              <option value="<?= (int) ($valeurs[0] ?? 0) ?>"><?= $h(($valeurs[1] ?? '') . ' — ' . ($valeurs[2] ?? '')) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($catalogue)): ?><small class="text-muted">Aucun élément de ce type n'est encore détenu par votre groupe.</small><?php endif; ?>
        </div>
        <div class="col-md-6">
          <label>Concessions cibles rattachées</label>
          <select id="concession_ids" class="form-control" multiple size="5">
            <?php foreach (($concessions ?? []) as $c): ?>
              <option value="<?= (int) $c['soc_id'] ?>"><?= $h($c['soc_nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button id="btn-previsualiser" class="btn btn-warning mt-3" type="button"><i class="fas fa-eye mr-1"></i>Prévisualiser</button>
    </div>
  </div>

  <div class="card card-outline card-warning" id="card-preview" style="display:none">
    <div class="card-header"><h3 class="card-title">2. Prévisualisation</h3></div>
    <div class="card-body" id="preview-body"></div>
    <div class="card-footer">
      <form method="post" id="form-confirmer">
        <?= csrf_field() ?>
        <button class="btn btn-success" type="submit"><i class="fas fa-check mr-1"></i>Confirmer et exécuter</button>
      </form>
    </div>
  </div>

  <div class="card card-outline card-secondary">
    <div class="card-header"><h3 class="card-title">Historique des propagations de ce groupe</h3></div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead><tr><th>Type</th><th>Statut</th><th>Créée le</th><th>Exécutée le</th><th></th></tr></thead>
        <tbody>
          <?php foreach (($historique ?? []) as $entree): ?>
            <?php [$label, $badge] = $statutLabels[$entree['bac_statut']] ?? [$entree['bac_statut'], 'secondary']; ?>
            <tr>
              <td><?= $h($typesLabels[$entree['bac_type_cible']] ?? $entree['bac_type_cible']) ?></td>
              <td><span class="badge badge-<?= $h($badge) ?>"><?= $h($label) ?></span></td>
              <td><?= $h($entree['bac_cree_le']) ?></td>
              <td><?= $h($entree['bac_execute_le'] ?? '—') ?></td>
              <td class="text-right">
                <?php if ($entree['bac_statut'] === 'executee'): ?>
                  <form method="post" action="/roles/propagation/<?= (int) $entree['bac_id'] ?>/annuler" class="d-inline" data-confirm="Annuler cette propagation (rollback) ?">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit">Annuler</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($historique)): ?><tr><td colspan="5" class="text-center text-muted py-3">Aucune propagation enregistrée.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></section>
<script<?= $nonceAttr ?? '' ?>>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('type-select').addEventListener('change', function (e) { e.target.form.submit(); });

    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?= $h($csrf_token ?? '') ?>';
    var btnPreview = document.getElementById('btn-previsualiser');
    var cardPreview = document.getElementById('card-preview');
    var previewBody = document.getElementById('preview-body');
    var formConfirmer = document.getElementById('form-confirmer');

    btnPreview.addEventListener('click', function () {
        var cibleId = document.getElementById('cible_id').value;
        var concessionIds = Array.from(document.getElementById('concession_ids').selectedOptions).map(function (o) { return o.value; });
        if (!cibleId || concessionIds.length === 0) {
            if (window.Swal) window.Swal.fire({ icon: 'warning', title: 'Sélectionnez un élément et au moins une concession.' });
            return;
        }

        var body = new URLSearchParams();
        body.set('type', '<?= $h($type ?? '') ?>');
        body.set('cible_id', cibleId);
        concessionIds.forEach(function (id) { body.append('concession_ids[]', id); });
        body.set('_csrf_token', csrfToken);

        fetch('/roles/propagation/previsualiser', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json.success) {
                    if (window.Swal) window.Swal.fire({ icon: 'error', title: json.message || 'Erreur de prévisualisation.' });
                    return;
                }
                var data = json.data;
                var html = '<p><strong>' + data.preview.a_creer + '</strong> création(s) prévue(s), <strong>' + data.preview.conflits + '</strong> conflit(s) ignoré(s).</p>';
                html += '<ul>' + data.preview.detail.map(function (d) {
                    return '<li>Concession #' + d.concession_id + ' — ' + (d.conflit ? '<span class="text-danger">conflit, ignorée</span>' : '<span class="text-success">sera créée</span>') + '</li>';
                }).join('') + '</ul>';
                previewBody.innerHTML = html;
                formConfirmer.action = '/roles/propagation/' + data.id + '/confirmer';
                cardPreview.style.display = '';
            });
    });
});
</script>
