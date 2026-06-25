<?php
declare(strict_types=1);
/**
 * LOT40 — Onglet "Infos générales" complémentaires
 * Inclus dans Society/Views/profil.php ET Users/Views/show.php
 *
 * Variables attendues :
 *   $ic       : tableau de sav_societes_infos_complementaires OU sav_utilisateurs_infos_complementaires
 *   $prefixe  : 'sic' (société) ou 'uic' (utilisateur)
 *   $baseUrl  : '/companies/42' ou '/users/42'
 *   $canEdit  : bool
 *   $natures  : tableau sav_natures_clients (code, libellé)
 *   $modes    : tableau sav_modes_reglement
 *   $remises  : tableau sav_codes_remise
 *   $taux_tva : tableau sav_taux_tva
 *   $csrf_token : string
 *
 * Ce partiel contient AUSSI les champs propres à l'utilisateur individuel
 * (titre identité, n° pièce, date délivrance, autorité, catégorie socio-pro)
 * qui n'apparaissent que quand $prefixe === 'uic'.
 */

$e   = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$nb  = fn($v) => $v !== null && $v !== '' ? number_format((float)$v, 2, ',', ' ') : '';
$p   = $prefixe ?? 'sic';          // 'sic' ou 'uic'
$ic  = $ic ?? [];
$est = fn(string $k) => !empty($ic["{$p}_{$k}"]);
$val = fn(string $k) => $ic["{$p}_{$k}"] ?? null;
?>

<form method="POST" action="<?= $e($baseUrl) ?>/infos-complementaires" id="form-infos-complementaires">
  <?= csrf_field() ?>

  <div class="card card-outline card-primary mb-3">
    <div class="card-header"><h3 class="card-title">Identification client</h3></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label>Code client</label>
            <input type="text" name="<?= $p ?>_code_client" class="form-control form-control-sm"
                   value="<?= $e($val('code_client')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Complément du nom</label>
            <input type="text" name="<?= $p ?>_complement_nom" class="form-control form-control-sm"
                   value="<?= $e($val('complement_nom')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Nature client</label>
            <select name="<?= $p ?>_nature_client_id" class="form-control form-control-sm" <?= $canEdit ? '' : 'disabled' ?>>
              <option value="">— Choisir —</option>
              <?php foreach ($natures ?? [] as $n): ?>
                <option value="<?= (int)$n['nac_id'] ?>"
                  <?= ((int)($val('nature_client_id') ?? 0) === (int)$n['nac_id']) ? 'selected' : '' ?>>
                  <?= $e($n['nac_code'] . ' – ' . $n['nac_libelle']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Compte collectif</label>
            <input type="text" name="<?= $p ?>_compte_collectif" class="form-control form-control-sm"
                   value="<?= $e($val('compte_collectif')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
      </div>

      <?php if ($p === 'sic'): ?>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label>KVPS MRA CLD</label>
            <input type="text" name="<?= $p ?>_kvps_mra_cld" class="form-control form-control-sm"
                   value="<?= $e($val('kvps_mra_cld')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <div class="col-md-2 d-flex align-items-end pb-3">
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="partenaire_vgf"
                   name="<?= $p ?>_partenaire_vgf" value="1"
                   <?= $est('partenaire_vgf') ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
            <label class="custom-control-label" for="partenaire_vgf">Partenaire VGF</label>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($p === 'uic'): ?>
      <!-- Champs propres à l'utilisateur individuel -->
      <div class="row">
        <div class="col-md-2">
          <div class="form-group">
            <label>Titre identité</label>
            <input type="text" name="uic_titre_identite" class="form-control form-control-sm"
                   value="<?= $e($val('titre_identite')) ?>" placeholder="ex. CNI, Passeport" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>N° de pièce</label>
            <input type="text" name="uic_numero_piece_identite" class="form-control form-control-sm"
                   value="<?= $e($val('numero_piece_identite')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Date de délivrance</label>
            <input type="date" name="uic_date_delivrance_piece" class="form-control form-control-sm"
                   value="<?= $e($val('date_delivrance_piece')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Autorité de délivrance</label>
            <input type="text" name="uic_autorite_delivrance" class="form-control form-control-sm"
                   value="<?= $e($val('autorite_delivrance')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-5">
          <div class="form-group">
            <label>Catégorie socioprofessionnelle</label>
            <input type="text" name="uic_categorie_socioprofessionnelle" class="form-control form-control-sm"
                   value="<?= $e($val('categorie_socioprofessionnelle')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Drapeaux de risque -->
  <div class="card card-outline card-warning mb-3">
    <div class="card-header"><h3 class="card-title">Drapeaux</h3></div>
    <div class="card-body">
      <div class="d-flex flex-wrap" style="gap:1.5rem;">
        <?php
        $flags = [
            'garantie'       => 'Garantie',
            'passager'       => 'Passager',
            'releve'         => 'Relevé',
            'assureur'       => 'Assureur',
            'blocage_facture'=> 'Blocage facture',
            'douteux'        => 'Douteux',
        ];
        foreach ($flags as $key => $label):
        ?>
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="flag_<?= $key ?>"
                   name="<?= $p ?>_flag_<?= $key ?>" value="1"
                   <?= $est("flag_{$key}") ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
            <label class="custom-control-label" for="flag_<?= $key ?>"><?= $label ?></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Conditions commerciales -->
  <div class="card card-outline card-secondary mb-3">
    <div class="card-header"><h3 class="card-title">Conditions commerciales</h3></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label>Mode de règlement</label>
            <select name="<?= $p ?>_mode_reglement_id" class="form-control form-control-sm" <?= $canEdit ? '' : 'disabled' ?>>
              <option value="">— Choisir —</option>
              <?php foreach ($modes ?? [] as $m): ?>
                <option value="<?= (int)$m['mrg_id'] ?>"
                  <?= ((int)($val('mode_reglement_id') ?? 0) === (int)$m['mrg_id']) ? 'selected' : '' ?>>
                  <?= $e($m['mrg_code'] . ' – ' . $m['mrg_libelle']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php if ($p === 'sic'): ?>
        <div class="col-md-4">
          <div class="form-group">
            <label>TVA spécifique</label>
            <select name="sic_tva_specifique_id" class="form-control form-control-sm" <?= $canEdit ? '' : 'disabled' ?>>
              <option value="">— Standard —</option>
              <?php foreach ($taux_tva ?? [] as $t): ?>
                <option value="<?= (int)$t['tva_id'] ?>"
                  <?= ((int)($val('tva_specifique_id') ?? 0) === (int)$t['tva_id']) ? 'selected' : '' ?>>
                  <?= $e($t['tva_code'] . ' – ' . $t['tva_nom'] . ' (' . $t['tva_taux'] . '%)') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Banque de remise</label>
            <input type="text" name="sic_banque_remise" class="form-control form-control-sm"
                   value="<?= $e($val('banque_remise')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label>Plafond crédit (€)</label>
            <input type="text" name="<?= $p ?>_plafond_credit" class="form-control form-control-sm"
                   value="<?= $e($nb($val('plafond_credit'))) ?>" placeholder="0,00" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Délais mois</label>
            <input type="number" name="<?= $p ?>_delai_paiement_mois" class="form-control form-control-sm"
                   min="0" max="24" value="<?= $e($val('delai_paiement_mois')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Délais jours</label>
            <input type="number" name="<?= $p ?>_delai_paiement_jours" class="form-control form-control-sm"
                   min="0" max="90" value="<?= $e($val('delai_paiement_jours')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Code remise</label>
            <select name="<?= $p ?>_code_remise_id" class="form-control form-control-sm" <?= $canEdit ? '' : 'disabled' ?>>
              <option value="">— Aucune —</option>
              <?php foreach ($remises ?? [] as $r): ?>
                <option value="<?= (int)$r['cre_id'] ?>"
                  <?= ((int)($val('code_remise_id') ?? 0) === (int)$r['cre_id']) ? 'selected' : '' ?>>
                  <?= $e($r['cre_code'] . ' – ' . $r['cre_libelle']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php if ($p === 'sic'): ?>
        <div class="col-md-4">
          <div class="form-group">
            <label>Compte débiteur cession interne</label>
            <input type="text" name="sic_compte_debiteur_cession_interne" class="form-control form-control-sm"
                   value="<?= $e($val('compte_debiteur_cession_interne')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label>Zone libre</label>
            <textarea name="<?= $p ?>_zone_libre" class="form-control form-control-sm" rows="2"
                      <?= $canEdit ? '' : 'readonly' ?>><?= $e($val('zone_libre')) ?></textarea>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($canEdit): ?>
    <div class="text-right mb-3">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fas fa-save mr-1"></i>Enregistrer les infos générales
      </button>
    </div>
  <?php endif; ?>
</form>
