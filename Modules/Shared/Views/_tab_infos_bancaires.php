<?php
declare(strict_types=1);
/**
 * LOT40 — Onglet "Infos bancaires"
 * Inclus dans Society/Views/profil.php ET Users/Views/show.php
 *
 * Variables attendues :
 *   $comptes_bancaires : array   — liste des comptes (scb_* ou ucb_*)
 *   $mandats           : array   — liste des mandats (smp_* ou ump_*)
 *   $prefixe           : 'scb'|'ucb' pour les comptes, 'smp'|'ump' pour les mandats
 *   $prefixe_mandat    : 'smp' ou 'ump'
 *   $baseUrl           : '/companies/42' ou '/users/42'
 *   $canEdit           : bool
 *   $csrf_token        : string
 */

$e  = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$dt = fn($v) => $v && $v !== '0000-00-00' ? date('d/m/Y', strtotime((string)$v)) : '—';
$pc = $prefixe         ?? 'scb';   // préfixe colonnes compte bancaire
$pm = $prefixe_mandat  ?? 'smp';   // préfixe colonnes mandat
$comptes = $comptes_bancaires ?? [];
$mandats = $mandats ?? [];
?>

<!-- ──────────── Comptes bancaires ──────────── -->
<div class="card card-outline card-primary mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">Comptes bancaires</h3>
    <?php if ($canEdit): ?>
      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#modal-add-compte">
        <i class="fas fa-plus mr-1"></i>Nouveau compte
      </button>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <?php if (empty($comptes)): ?>
      <p class="text-muted p-3 mb-0">Aucun compte bancaire enregistré.</p>
    <?php else: ?>
      <table class="table table-sm table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th>Code banque</th><th>Code guichet</th><th>N° compte / Clé</th>
            <th>IBAN</th><th>BIC</th><th>Principal</th>
            <?php if ($canEdit): ?><th class="text-right">Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($comptes as $c):
          $cid = (int)($c["{$pc}_id"] ?? 0);
        ?>
          <tr>
            <td><?= $e($c["{$pc}_code_banque"] ?? '') ?></td>
            <td><?= $e($c["{$pc}_code_guichet"] ?? '') ?></td>
            <td><?= $e($c["{$pc}_numero_compte"] ?? '') ?> – <?= $e($c["{$pc}_cle_rib"] ?? '') ?></td>
            <td><small><?= $e($c["{$pc}_iban"] ?? '') ?></small></td>
            <td><?= $e($c["{$pc}_bic"] ?? '') ?></td>
            <td>
              <?php if (!empty($c["{$pc}_est_principal"])): ?>
                <span class="badge badge-success">Principal</span>
              <?php endif; ?>
            </td>
            <?php if ($canEdit): ?>
            <td class="text-right">
              <form method="POST" action="<?= $e($baseUrl) ?>/comptes-bancaires/<?= $cid ?>/delete"
                    onsubmit="return confirm('Supprimer ce compte bancaire ?');">
                <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger">
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

<!-- ──────────── Mandats SEPA ──────────── -->
<div class="card card-outline card-info mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">Mandats de prélèvement</h3>
    <?php if ($canEdit): ?>
      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#modal-add-mandat">
        <i class="fas fa-plus mr-1"></i>Nouveau mandat
      </button>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <?php if (empty($mandats)): ?>
      <p class="text-muted p-3 mb-0">Aucun mandat enregistré.</p>
    <?php else: ?>
      <table class="table table-sm table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th>Référence unique</th><th>Type</th><th>Signature</th>
            <th>1er prélèvement</th><th>Fin validité</th>
            <th>Défaut</th><th>Actif</th>
            <?php if ($canEdit): ?><th class="text-right">Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($mandats as $m):
          $mid = (int)($m["{$pm}_id"] ?? 0);
        ?>
          <tr>
            <td><code><?= $e($m["{$pm}_reference_unique"] ?? '') ?></code></td>
            <td><?= $e($m["{$pm}_type"] ?? '') ?></td>
            <td><?= $dt($m["{$pm}_date_signature"] ?? null) ?></td>
            <td><?= $dt($m["{$pm}_date_premier_prelevement"] ?? null) ?></td>
            <td><?= $dt($m["{$pm}_date_fin_validite"] ?? null) ?></td>
            <td><?= !empty($m["{$pm}_est_defaut"]) ? '<span class="badge badge-primary">Défaut</span>' : '' ?></td>
            <td>
              <?php if (!empty($m["{$pm}_est_actif"])): ?>
                <span class="badge badge-success">Actif</span>
              <?php else: ?>
                <span class="badge badge-secondary">Inactif</span>
              <?php endif; ?>
            </td>
            <?php if ($canEdit): ?>
            <td class="text-right">
              <form method="POST" action="<?= $e($baseUrl) ?>/mandats/<?= $mid ?>/delete"
                    onsubmit="return confirm('Supprimer ce mandat ?');" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
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
<!-- Modal : Nouveau compte bancaire -->
<div class="modal fade" id="modal-add-compte" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= $e($baseUrl) ?>/comptes-bancaires">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
        <div class="modal-header"><h5 class="modal-title">Nouveau compte bancaire</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
          <div class="row">
            <div class="col-6"><div class="form-group"><label>Code banque</label>
              <input type="text" name="<?= $pc ?>_code_banque" class="form-control form-control-sm" maxlength="10"></div></div>
            <div class="col-6"><div class="form-group"><label>Code guichet</label>
              <input type="text" name="<?= $pc ?>_code_guichet" class="form-control form-control-sm" maxlength="10"></div></div>
          </div>
          <div class="row">
            <div class="col-8"><div class="form-group"><label>N° de compte</label>
              <input type="text" name="<?= $pc ?>_numero_compte" class="form-control form-control-sm" maxlength="20"></div></div>
            <div class="col-4"><div class="form-group"><label>Clé RIB</label>
              <input type="text" name="<?= $pc ?>_cle_rib" class="form-control form-control-sm" maxlength="5"></div></div>
          </div>
          <div class="form-group"><label>IBAN</label>
            <input type="text" name="<?= $pc ?>_iban" class="form-control form-control-sm" maxlength="34" placeholder="FR76 ..."></div>
          <div class="form-group"><label>BIC</label>
            <input type="text" name="<?= $pc ?>_bic" class="form-control form-control-sm" maxlength="11"></div>
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="compte_principal" name="<?= $pc ?>_est_principal" value="1">
            <label class="custom-control-label" for="compte_principal">Compte principal</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal : Nouveau mandat SEPA -->
<div class="modal fade" id="modal-add-mandat" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= $e($baseUrl) ?>/mandats">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
        <div class="modal-header"><h5 class="modal-title">Nouveau mandat de prélèvement</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
          <div class="form-group"><label>Référence unique <span class="text-danger">*</span></label>
            <input type="text" name="<?= $pm ?>_reference_unique" class="form-control form-control-sm" required maxlength="35"></div>
          <div class="form-group"><label>Type</label>
            <select name="<?= $pm ?>_type" class="form-control form-control-sm">
              <option value="RECURRENT">RECURRENT</option>
              <option value="PONCTUEL">PONCTUEL</option>
            </select>
          </div>
          <?php if (!empty($comptes)): ?>
          <div class="form-group"><label>Compte bancaire lié</label>
            <select name="<?= $pm ?>_compte_bancaire_id" class="form-control form-control-sm">
              <option value="">— Choisir —</option>
              <?php foreach ($comptes as $c): ?>
                <option value="<?= (int)($c["{$pc}_id"] ?? 0) ?>">
                  <?= $e($c["{$pc}_iban"] ?? $c["{$pc}_numero_compte"] ?? '???') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="row">
            <div class="col-6"><div class="form-group"><label>Date de signature</label>
              <input type="date" name="<?= $pm ?>_date_signature" class="form-control form-control-sm"></div></div>
            <div class="col-6"><div class="form-group"><label>1er prélèvement</label>
              <input type="date" name="<?= $pm ?>_date_premier_prelevement" class="form-control form-control-sm"></div></div>
          </div>
          <div class="row">
            <div class="col-6"><div class="form-group"><label>Fin de validité</label>
              <input type="date" name="<?= $pm ?>_date_fin_validite" class="form-control form-control-sm"></div></div>
            <div class="col-6 d-flex align-items-end pb-3">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="mandat_defaut" name="<?= $pm ?>_est_defaut" value="1">
                <label class="custom-control-label" for="mandat_defaut">Mandat par défaut</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
