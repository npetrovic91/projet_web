<?php
declare(strict_types=1);
/**
 * Profil complet Société / Marque
 * Route : /companies/{id}  ou  /brands/{id}
 * Variables : $societe (ficheComplete()), $canEdit (bool)
 */
$e  = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$dt = fn($v) => $v ? date('d/m/Y', strtotime((string)$v)) : '—';
$nb = fn($v, string $fmt = '%s') => $v !== null && $v !== '' ? sprintf($fmt, number_format((float)$v, 2, ',', ' ')) : '—';

$soc      = $societe ?? [];
$socId    = (int)($soc['soc_id'] ?? 0);
$initials = strtoupper(mb_substr(trim((string)($soc['soc_nom'] ?? '?')), 0, 2));
$types    = $soc['type_label'] ?? '';
$canEdit  = (bool)($canEdit ?? false);
$baseUrl  = '/companies/' . $socId;
?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-sm-8 d-flex align-items-center" style="gap:12px;">
        <?php if (!empty($soc['soc_logo_url'])): ?>
          <img src="<?= $e($soc['soc_logo_url']) ?>" alt="Logo"
               style="width:56px;height:56px;object-fit:contain;border-radius:50%;background:#f4f4f4;padding:4px;border:1px solid #dee2e6;">
        <?php else: ?>
          <div style="width:56px;height:56px;font-size:1.25rem;font-weight:600;border-radius:50%;background:#6c757d;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <?= $e($initials) ?>
          </div>
        <?php endif; ?>
        <div>
          <h1 style="font-size:1.35rem;margin:0;"><?= $e($soc['soc_nom'] ?? 'Société') ?></h1>
          <small class="text-muted"><?= $e($soc['soc_nom_legal'] ?? '') ?></small>
          <?php if ($types): ?>
            <span class="badge badge-info ml-2"><?= $e($types) ?></span>
          <?php endif; ?>
          <?php if (!empty($soc['soc_statut_id']) && ($soc['soc_statut_id'] != 1)): ?>
            <span class="badge badge-danger ml-1">Inactif</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-sm-4 text-right">
        <?php if ($canEdit): ?>
          <a href="<?= $e($baseUrl) ?>/edit" class="btn btn-sm btn-outline-warning">
            <i class="fas fa-edit mr-1"></i>Modifier
          </a>
        <?php endif; ?>
        <a href="/companies" class="btn btn-sm btn-outline-secondary ml-1">
          <i class="fas fa-arrow-left mr-1"></i>Retour
        </a>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">

      <!-- ══════════ COLONNE GAUCHE ══════════════════════════════════════ -->
      <div class="col-md-4">

        <!-- Identité légale -->
        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-balance-scale mr-1"></i>Identité légale</h3>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted small" style="width:46%;padding-left:12px;">SIREN</td>
                  <td class="font-weight-bold"><?= $e($soc['soc_siren'] ?? '') ?: '—' ?></td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">SIRET siège</td>
                  <td><?= $e($soc['soc_siret_siege'] ?? $soc['soc_siret'] ?? '') ?: '—' ?></td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">N° TVA intrac.</td>
                  <td><?= $e($soc['soc_numero_tva'] ?? '') ?: '—' ?></td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">Forme juridique</td>
                  <td><?= $e($soc['soc_forme_juridique'] ?? '') ?: '—' ?></td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">Date de création</td>
                  <td><?= $dt($soc['soc_date_creation'] ?? null) ?></td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">Capital social</td>
                  <td><?= $nb($soc['soc_capital_social'] ?? null, '%s €') ?></td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">Code NAF / APE</td>
                  <td>
                    <?= $e($soc['soc_code_naf'] ?? '') ?: '—' ?>
                    <?php if (!empty($soc['soc_libelle_naf'])): ?>
                      <br><span class="text-muted" style="font-size:11px;"><?= $e($soc['soc_libelle_naf']) ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">RCS</td>
                  <td><?= $e($soc['soc_rcs'] ?? '') ?: '—' ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Coordonnées -->
        <div class="card card-outline card-secondary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-map-marker-alt mr-1"></i>Coordonnées</h3>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted small" style="width:35%;padding-left:12px;">Adresse</td>
                  <td><?= $e($soc['soc_adresse'] ?? '') ?: '—' ?></td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">CP / Ville</td>
                  <td><?= $e(trim(($soc['soc_code_postal'] ?? '') . ' ' . ($soc['soc_ville'] ?? ''))) ?: '—' ?></td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">Pays</td>
                  <td><?= $e($soc['soc_pays_nom'] ?? $soc['com_country'] ?? '') ?: '—' ?></td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">Téléphone</td>
                  <td>
                    <?php if (!empty($soc['soc_telephone'])): ?>
                      <a href="tel:<?= $e($soc['soc_telephone']) ?>"><?= $e($soc['soc_telephone']) ?></a>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">E-mail</td>
                  <td>
                    <?php if (!empty($soc['soc_email'])): ?>
                      <a href="mailto:<?= $e($soc['soc_email']) ?>"><?= $e($soc['soc_email']) ?></a>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted small pl-3">Site web</td>
                  <td>
                    <?php if (!empty($soc['soc_site_web'])): ?>
                      <a href="<?= $e($soc['soc_site_web']) ?>" target="_blank" rel="noopener noreferrer">
                        <?= $e(preg_replace('#^https?://#', '', rtrim((string)$soc['soc_site_web'], '/'))) ?>
                        <i class="fas fa-external-link-alt fa-xs ml-1"></i>
                      </a>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /col gauche -->

      <!-- ══════════ COLONNE DROITE ══════════════════════════════════════ -->
      <div class="col-md-8">

        <!-- Dirigeants -->
        <div class="card card-outline card-warning">
          <div class="card-header d-flex align-items-center">
            <h3 class="card-title flex-grow-1">
              <i class="fas fa-user-tie mr-1"></i>Dirigeants &amp; mandataires sociaux
            </h3>
            <?php if ($canEdit): ?>
              <!-- Ajout dirigeant : route non présente dans config/urls.php -->
            <?php endif; ?>
          </div>
          <div class="card-body p-0">
            <?php $dirigeants = $soc['dirigeants'] ?? []; ?>
            <?php if (empty($dirigeants)): ?>
              <p class="text-muted small p-3 mb-0">
                <i class="fas fa-info-circle mr-1"></i>Aucun dirigeant renseigné.
              </p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($dirigeants as $d): ?>
                <li class="list-group-item d-flex align-items-center py-2">
                  <div class="d-flex align-items-center justify-content-center bg-warning text-dark rounded-circle mr-3"
                       style="width:36px;height:36px;font-size:.85rem;flex-shrink:0;font-weight:700;">
                    <?= $e(strtoupper(mb_substr(trim(($d['dso_prenom'] ?? '') . ($d['dso_nom'] ?? '')), 0, 2))) ?>
                  </div>
                  <div class="flex-grow-1">
                    <div>
                      <strong><?= $e(trim(($d['dso_civilite'] ?? '') . ' ' . ($d['dso_prenom'] ?? '') . ' ' . ($d['dso_nom'] ?? ''))) ?></strong>
                      <?php if (!empty($d['dso_est_dirigeant_principal'])): ?>
                        <span class="badge badge-warning ml-1" style="font-size:10px;">Principal</span>
                      <?php endif; ?>
                    </div>
                    <small class="text-muted">
                      <?= $e($d['dso_fonction'] ?? '') ?>
                      <?php if (!empty($d['dso_date_prise_de_poste'])): ?>
                        · depuis <?= $dt($d['dso_date_prise_de_poste']) ?>
                      <?php endif; ?>
                    </small>
                  </div>
                  <div class="text-right" style="min-width:60px;">
                    <?php if (!empty($d['dso_email'])): ?>
                      <a href="mailto:<?= $e($d['dso_email']) ?>" class="btn btn-xs btn-outline-secondary"
                         title="<?= $e($d['dso_email']) ?>"><i class="fas fa-envelope"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($d['dso_telephone'])): ?>
                      <a href="tel:<?= $e($d['dso_telephone']) ?>" class="btn btn-xs btn-outline-secondary ml-1"
                         title="<?= $e($d['dso_telephone']) ?>"><i class="fas fa-phone"></i></a>
                    <?php endif; ?>
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- Établissements -->
        <div class="card card-outline card-info">
          <div class="card-header d-flex align-items-center">
            <h3 class="card-title flex-grow-1">
              <i class="fas fa-building mr-1"></i>Établissements
            </h3>
            <?php if ($canEdit): ?>
              <!-- Ajout établissement : route non présente dans config/urls.php -->
            <?php endif; ?>
          </div>
          <div class="card-body p-0">
            <?php $etabs = $soc['etablissements'] ?? []; ?>
            <?php if (empty($etabs)): ?>
              <p class="text-muted small p-3 mb-0">
                <i class="fas fa-info-circle mr-1"></i>Aucun établissement renseigné.
              </p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($etabs as $et): ?>
                <li class="list-group-item py-2">
                  <div class="d-flex align-items-start">
                    <i class="fas fa-map-pin text-info mt-1 mr-2"></i>
                    <div class="flex-grow-1">
                      <div>
                        <strong><?= $e($et['ets_nom'] ?? 'Établissement') ?></strong>
                        <?php if (!empty($et['ets_est_siege'])): ?>
                          <span class="badge badge-info ml-1" style="font-size:10px;">Siège social</span>
                        <?php endif; ?>
                        <?php if (empty($et['ets_est_actif'])): ?>
                          <span class="badge badge-secondary ml-1" style="font-size:10px;">Fermé</span>
                        <?php endif; ?>
                      </div>
                      <?php if (!empty($et['ets_siret'])): ?>
                        <small class="text-muted">SIRET : <code><?= $e($et['ets_siret']) ?></code></small>
                      <?php endif; ?>
                      <div class="text-muted" style="font-size:12px;margin-top:2px;">
                        <?php $adr = trim(($et['ets_adresse'] ?? '') . ', ' . ($et['ets_code_postal'] ?? '') . ' ' . ($et['ets_ville'] ?? '')); ?>
                        <?= $e(ltrim($adr, ', ')) ?: '—' ?>
                        <?php if (!empty($et['pays_nom']) && $et['pays_nom'] !== 'France'): ?>
                          · <?= $e($et['pays_nom']) ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php if (!empty($et['ets_telephone'])): ?>
                      <a href="tel:<?= $e($et['ets_telephone']) ?>" class="btn btn-xs btn-outline-secondary ml-2"
                         title="<?= $e($et['ets_telephone']) ?>"><i class="fas fa-phone"></i></a>
                    <?php endif; ?>
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- Contacts -->
        <div class="card card-outline card-success">
          <div class="card-header d-flex align-items-center">
            <h3 class="card-title flex-grow-1">
              <i class="fas fa-address-book mr-1"></i>Contacts
            </h3>
            <?php if ($canEdit): ?>
              <!-- Ajout contact : route non présente dans config/urls.php -->
            <?php endif; ?>
          </div>
          <div class="card-body p-0">
            <?php $contacts = $soc['contacts'] ?? []; ?>
            <?php if (empty($contacts)): ?>
              <p class="text-muted small p-3 mb-0">
                <i class="fas fa-info-circle mr-1"></i>Aucun contact renseigné.
              </p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($contacts as $c): ?>
                <li class="list-group-item d-flex align-items-center py-2">
                  <div class="d-flex align-items-center justify-content-center bg-success text-white rounded-circle mr-3"
                       style="width:36px;height:36px;font-size:.85rem;flex-shrink:0;font-weight:700;">
                    <?= $e(strtoupper(mb_substr(trim(($c['cts_prenom'] ?? '') . ($c['cts_nom'] ?? '')), 0, 2))) ?>
                  </div>
                  <div class="flex-grow-1">
                    <div>
                      <strong><?= $e(trim(($c['cts_civilite'] ?? '') . ' ' . ($c['cts_prenom'] ?? '') . ' ' . ($c['cts_nom'] ?? ''))) ?></strong>
                      <?php if (!empty($c['cts_est_principal'])): ?>
                        <span class="badge badge-success ml-1" style="font-size:10px;">Principal</span>
                      <?php endif; ?>
                    </div>
                    <small class="text-muted">
                      <?= $e($c['cts_fonction'] ?? '') ?>
                      <?php if (!empty($c['cts_service'])): ?>&nbsp;—&nbsp;<?= $e($c['cts_service']) ?><?php endif; ?>
                      <?php if (!empty($c['etablissement_nom'])): ?>
                        · <em><?= $e($c['etablissement_nom']) ?></em>
                      <?php endif; ?>
                    </small>
                  </div>
                  <div class="text-right ml-2">
                    <?php if (!empty($c['cts_email_externe'])): ?>
                      <a href="mailto:<?= $e($c['cts_email_externe']) ?>" class="btn btn-xs btn-outline-secondary"
                         title="<?= $e($c['cts_email_externe']) ?>"><i class="fas fa-envelope"></i></a>
                    <?php endif; ?>
                    <?php
                      $tel = $c['cts_telephone'] ?? $c['cts_mobile'] ?? '';
                    ?>
                    <?php if ($tel): ?>
                      <a href="tel:<?= $e($tel) ?>" class="btn btn-xs btn-outline-secondary ml-1"
                         title="<?= $e($tel) ?>"><i class="fas fa-phone"></i></a>
                    <?php endif; ?>
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

      </div><!-- /col droite -->
    </div><!-- /row -->
  </div>
</section>

<?php
// ════════════════════════════════════════════════════════════════════
// LOT40 — Onglets CLIENTS (Infos complémentaires / Bancaires / Parc SAV)
// Variables injectées par SocieteController::show() via ServiceSocietes::ficheComplete()
// ════════════════════════════════════════════════════════════════════
if (!empty($infos_complementaires) || !empty($comptes_bancaires) || !empty($mandats) || !empty($vehicules) || $canEdit):
?>
<section class="content">
  <div class="container-fluid">

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="tabs-client" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="tab-infos-complementaires" data-toggle="tab"
           href="#infos-complementaires" role="tab">
          <i class="fas fa-id-card mr-1"></i>Infos générales
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-infos-bancaires" data-toggle="tab"
           href="#infos-bancaires" role="tab">
          <i class="fas fa-university mr-1"></i>Infos bancaires
          <?php if (!empty($comptes_bancaires)): ?>
            <span class="badge badge-info ml-1"><?= count($comptes_bancaires) ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-parc-sav" data-toggle="tab"
           href="#parc-sav" role="tab">
          <i class="fas fa-car mr-1"></i>Parc SAV
          <?php if (!empty($vehicules)): ?>
            <span class="badge badge-secondary ml-1"><?= count($vehicules) ?></span>
          <?php endif; ?>
        </a>
      </li>
    </ul>

    <div class="tab-content border border-top-0 p-3 bg-white mb-4" id="tabs-client-content">

      <!-- Onglet 1 : Infos générales complémentaires -->
      <div class="tab-pane fade show active" id="infos-complementaires" role="tabpanel">
        <?php
        $prefixe  = 'sic';
        $ic       = $infos_complementaires ?? [];
        $natures  = $natures_clients ?? [];
        $modes    = $modes_reglement ?? [];
        $remises  = $codes_remise ?? [];
        require dirname(__FILE__, 3) . '/Shared/Views/_tab_infos_complementaires.php';
        ?>
      </div>

      <!-- Onglet 2 : Infos bancaires -->
      <div class="tab-pane fade" id="infos-bancaires" role="tabpanel">
        <?php
        $prefixe          = 'scb';
        $prefixe_mandat   = 'smp';
        $comptes_bancaires = $comptes_bancaires ?? [];
        $mandats          = $mandats ?? [];
        require dirname(__FILE__, 3) . '/Shared/Views/_tab_infos_bancaires.php';
        ?>
      </div>

      <!-- Onglet 3 : Parc SAV -->
      <div class="tab-pane fade" id="parc-sav" role="tabpanel">
        <?php
        $prefixe = 'sve';
        $vehicules = $vehicules ?? [];
        require dirname(__FILE__, 3) . '/Shared/Views/_tab_parc_sav.php';
        ?>
      </div>

    </div>
  </div>
</section>

<script>
// Restaurer l'onglet actif depuis l'ancre d'URL (ex. #infos-bancaires)
document.addEventListener('DOMContentLoaded', function () {
    var hash = window.location.hash;
    if (hash) {
        var tab = document.querySelector('#tabs-client a[href="' + hash + '"]');
        if (tab) { tab.click(); }
    }
    // Mettre à jour l'ancre quand on change d'onglet
    document.querySelectorAll('#tabs-client a[data-toggle="tab"]').forEach(function (el) {
        el.addEventListener('shown.bs.tab', function (e) {
            history.replaceState(null, null, e.target.getAttribute('href'));
        });
    });
});
</script>
<?php endif; ?>