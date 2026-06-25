<?php
declare(strict_types=1);
/**
 * LOT40 — Onglet "Parc SAV"
 * Inclus dans Society/Views/profil.php ET Users/Views/show.php
 *
 * Variables attendues :
 *   $vehicules  : array   — liste (sve_* ou uve_*)
 *   $prefixe    : 'sve' ou 'uve'
 *   $baseUrl    : '/companies/42' ou '/users/42'
 *   $canEdit    : bool
 *   $csrf_token : string
 *   $marques_disponibles : array [{soc_id, soc_nom}] — pour le select du formulaire
 */

$e  = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$dt = fn($v) => $v && $v !== '0000-00-00' ? date('d/m/Y', strtotime((string)$v)) : '—';
$pv = $prefixe ?? 'sve';
$vehicules = $vehicules ?? [];
?>

<div class="card card-outline card-secondary mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">
      Liste des véhicules
      <?php if (!empty($vehicules)): ?>
        <span class="badge badge-secondary ml-1"><?= count($vehicules) ?></span>
      <?php endif; ?>
    </h3>
    <?php if ($canEdit): ?>
      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#modal-add-vehicule">
        <i class="fas fa-plus mr-1"></i>Ajouter un véhicule
      </button>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <?php if (empty($vehicules)): ?>
      <p class="text-muted p-3 mb-0">Aucun véhicule enregistré dans le parc.</p>
    <?php else: ?>
      <table class="table table-sm table-hover table-bordered mb-0 dataTable-parc-sav">
        <thead class="thead-light">
          <tr>
            <th>Code marque</th>
            <th>Type de modèle</th>
            <th>Châssis (VIN)</th>
            <th>Immatriculation</th>
            <th>N° moteur</th>
            <th>Date M.E.C.</th>
            <?php if ($canEdit): ?><th class="text-right no-sort">Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($vehicules as $v):
          $vid = (int)($v["{$pv}_id"] ?? 0);
        ?>
          <tr>
            <td>
              <?php if (!empty($v['marque_nom'])): ?>
                <span title="<?= $e($v['marque_nom']) ?>"><?= $e($v["{$pv}_code_marque"] ?? '') ?></span>
              <?php else: ?>
                <?= $e($v["{$pv}_code_marque"] ?? '—') ?>
              <?php endif; ?>
            </td>
            <td><?= $e($v["{$pv}_type_modele"] ?? '—') ?></td>
            <td><code class="text-monospace small"><?= $e($v["{$pv}_chassis"] ?? '—') ?></code></td>
            <td><strong><?= $e($v["{$pv}_immatriculation"] ?? '—') ?></strong></td>
            <td><?= $e($v["{$pv}_numero_moteur"] ?? '—') ?></td>
            <td><?= $dt($v["{$pv}_date_mise_en_circulation"] ?? null) ?></td>
            <?php if ($canEdit): ?>
            <td class="text-right">
              <form method="POST" action="<?= $e($baseUrl) ?>/vehicules/<?= $vid ?>/delete"
                    data-confirm="Retirer ce véhicule du parc ?" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-xs btn-outline-danger" title="Retirer">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php if ($canEdit): ?>
<!-- Modal : Ajouter un véhicule -->
<div class="modal fade" id="modal-add-vehicule" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?= $e($baseUrl) ?>/vehicules">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-car mr-2"></i>Ajouter un véhicule au parc</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Code marque</label>
                <input type="text" name="<?= $pv ?>_code_marque" class="form-control form-control-sm"
                       placeholder="ex. 275" maxlength="20">
              </div>
            </div>
            <?php if (!empty($marques_disponibles)): ?>
            <div class="col-md-8">
              <div class="form-group">
                <label>Marque (liaison)</label>
                <select name="<?= $pv ?>_marque_societe_id" class="form-control form-control-sm">
                  <option value="">— Optionnel —</option>
                  <?php foreach ($marques_disponibles as $mq): ?>
                    <option value="<?= (int)($mq['soc_id'] ?? 0) ?>"><?= $e($mq['soc_nom'] ?? '') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <?php endif; ?>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Type de modèle</label>
                <input type="text" name="<?= $pv ?>_type_modele" class="form-control form-control-sm"
                       placeholder="ex. NJ33C4" maxlength="100">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Immatriculation</label>
                <input type="text" name="<?= $pv ?>_immatriculation" class="form-control form-control-sm"
                       placeholder="ex. EV-622-ZA" maxlength="20" style="text-transform:uppercase;">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Châssis / VIN <span class="text-muted">(17 car.)</span></label>
                <input type="text" name="<?= $pv ?>_chassis" class="form-control form-control-sm"
                       placeholder="ex. TMBEA6NJ3JZ130519" maxlength="30"
                       style="text-transform:uppercase; font-family: monospace;">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>N° moteur</label>
                <input type="text" name="<?= $pv ?>_numero_moteur" class="form-control form-control-sm"
                       placeholder="ex. CHYA" maxlength="50">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Date de M.E.C.</label>
                <input type="date" name="<?= $pv ?>_date_mise_en_circulation" class="form-control form-control-sm">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success btn-sm">
            <i class="fas fa-plus mr-1"></i>Ajouter au parc
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($vehicules)): ?>
<script>
// DataTable léger sur le tableau du parc SAV (si DataTables est chargé)
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
        $('.dataTable-parc-sav').DataTable({
            pageLength: 25,
            language: { url: '/assets/vendor/datatables/fr_FR.json' },
            columnDefs: [{ orderable: false, targets: 'no-sort' }]
        });
    }
});
</script>
<?php endif; ?>
