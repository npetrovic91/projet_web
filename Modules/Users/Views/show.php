<?php
$profile = $profile ?? [];
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$formatDate = static function ($date) use ($e): string {
    if (empty($date)) {
        return '—';
    }
    $timestamp = strtotime((string) $date);
    return $timestamp ? date('d/m/Y', $timestamp) : $e($date);
};
?>
<section class="content-header">
  <div class="container-fluid">
    <h1><?= $e($page_title ?? 'Mon profil') ?></h1>
  </div>
</section>

<?php if (!empty($_SESSION['security']['must_change_password'])): ?>
<section class="content">
  <div class="container-fluid">
    <div class="alert alert-warning">
      <strong>Action requise :</strong> pour des raisons de sécurité, vous devez changer votre mot de passe temporaire avant de pouvoir accéder au reste de l'application.
    </div>
  </div>
</section>
<?php endif; ?>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Identité</h3></div>
          <div class="card-body">
            <p><strong><?= $e(trim(($profile['use_firstname'] ?? $profile['pui_prenom'] ?? '') . ' ' . ($profile['use_lastname'] ?? $profile['pui_nom'] ?? ''))) ?></strong></p>
            <p><?= $e($profile['use_email'] ?? $profile['uti_email'] ?? '') ?></p>
            <p>Entreprise active : <?= $e($profile['active_company_name'] ?? '-') ?></p>
            <p>Marque active : <?= $e($profile['active_brand_name'] ?? '-') ?></p>
          </div>
        </div>

        <div class="card" id="security">
          <div class="card-header"><h3 class="card-title">Mot de passe</h3></div>
          <form method="post" action="/profile/password">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrf_token ?? '') ?>">
            <div class="card-body">
              <div class="form-group"><label>Mot de passe actuel</label><input class="form-control" type="password" name="current_password"></div>
              <div class="form-group"><label>Nouveau mot de passe</label><input class="form-control" type="password" name="password"></div>
              <div class="form-group"><label>Confirmation</label><input class="form-control" type="password" name="password_confirm"></div>
            </div>
            <div class="card-footer"><button class="btn btn-secondary">Modifier</button></div>
          </form>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Données modifiables</h3></div>
          <form method="post" action="/profile/update">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrf_token ?? '') ?>">
            <div class="card-body">
              <div class="row">
                <div class="col-md-2 form-group"><label>Civilité</label><input class="form-control" name="civility" value="<?= $e($profile['use_civility'] ?? $profile['pui_civilite'] ?? '') ?>"></div>
                <div class="col-md-5 form-group"><label>Nom</label><input class="form-control" name="lastname" required value="<?= $e($profile['use_lastname'] ?? $profile['pui_nom'] ?? '') ?>"></div>
                <div class="col-md-5 form-group"><label>Prénom</label><input class="form-control" name="firstname" required value="<?= $e($profile['use_firstname'] ?? $profile['pui_prenom'] ?? '') ?>"></div>
              </div>
              <div class="row">
                <div class="col-md-6 form-group"><label>Email</label><input class="form-control" type="email" name="email" required value="<?= $e($profile['use_email'] ?? $profile['uti_email'] ?? '') ?>"></div>
                <div class="col-md-3 form-group"><label>Téléphone</label><input class="form-control" name="phone" value="<?= $e($profile['use_phone'] ?? $profile['pui_telephone'] ?? '') ?>"></div>
                <div class="col-md-3 form-group"><label>Mobile</label><input class="form-control" name="mobile" value="<?= $e($profile['use_mobile'] ?? $profile['pui_mobile'] ?? '') ?>"></div>
              </div>
              <div class="row">
                <div class="col-md-6 form-group"><label>Langue</label><input class="form-control" name="locale" value="<?= $e($profile['use_locale'] ?? $profile['uti_langue'] ?? 'fr') ?>"></div>
                <div class="col-md-6 form-group"><label>Fuseau horaire</label><input class="form-control" name="timezone" value="<?= $e($profile['use_timezone'] ?? 'Europe/Paris') ?>"></div>
              </div>
              <div class="alert alert-light border mb-0">
                Les rôles, sociétés, fonctions, compétences, certifications, services et équipes sont des affectations administratives. Elles se modifient depuis la fiche utilisateur par un administrateur habilité.
              </div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Enregistrer</button></div>
          </form>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="card"><div class="card-header"><h3 class="card-title">Sociétés</h3></div><div class="card-body p-0"><ul class="list-group list-group-flush">
          <?php foreach (($profile['societes'] ?? $profile['companies'] ?? []) as $societe): ?><li class="list-group-item"><?= $e($societe['soc_nom'] ?? '') ?></li><?php endforeach; ?>
          <?php if (empty($profile['societes'] ?? $profile['companies'] ?? [])): ?><li class="list-group-item text-muted">Aucune société.</li><?php endif; ?>
        </ul></div></div>
      </div>
      <div class="col-md-4">
        <div class="card"><div class="card-header"><h3 class="card-title">Rôles contextuels</h3></div><div class="card-body p-0"><ul class="list-group list-group-flush">
          <?php foreach (($profile['roles'] ?? []) as $role): ?><li class="list-group-item"><?= $e($role['rol_nom'] ?? '') ?><br><small class="text-muted"><?= $e($role['societe_nom'] ?? 'Global') ?></small></li><?php endforeach; ?>
          <?php if (empty($profile['roles'] ?? [])): ?><li class="list-group-item text-muted">Aucun rôle.</li><?php endif; ?>
        </ul></div></div>
      </div>
      <div class="col-md-4">
        <div class="card"><div class="card-header"><h3 class="card-title">Fonctions métier</h3></div><div class="card-body p-0"><ul class="list-group list-group-flush">
          <?php foreach (($profile['fonctions'] ?? $profile['functions'] ?? []) as $fonction): ?><li class="list-group-item"><?= $e($fonction['fon_nom'] ?? '') ?></li><?php endforeach; ?>
          <?php if (empty($profile['fonctions'] ?? $profile['functions'] ?? [])): ?><li class="list-group-item text-muted">Aucune fonction.</li><?php endif; ?>
        </ul></div></div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4"><div class="card"><div class="card-header"><h3 class="card-title">Départements</h3></div><div class="card-body p-0"><ul class="list-group list-group-flush">
        <?php foreach (($profile['departements'] ?? []) as $departement): ?><li class="list-group-item"><?= $e($departement['dep_nom'] ?? '') ?></li><?php endforeach; ?>
        <?php if (empty($profile['departements'] ?? [])): ?><li class="list-group-item text-muted">Aucun département.</li><?php endif; ?>
      </ul></div></div></div>
      <div class="col-md-4"><div class="card"><div class="card-header"><h3 class="card-title">Services</h3></div><div class="card-body p-0"><ul class="list-group list-group-flush">
        <?php foreach (($profile['services'] ?? []) as $service): ?><li class="list-group-item"><?= $e($service['srv_nom'] ?? '') ?></li><?php endforeach; ?>
        <?php if (empty($profile['services'] ?? [])): ?><li class="list-group-item text-muted">Aucun service.</li><?php endif; ?>
      </ul></div></div></div>
      <div class="col-md-4"><div class="card"><div class="card-header"><h3 class="card-title">Équipes</h3></div><div class="card-body p-0"><ul class="list-group list-group-flush">
        <?php foreach (($profile['equipes'] ?? $profile['teams'] ?? []) as $equipe): ?><li class="list-group-item"><?= $e($equipe['equ_nom'] ?? '') ?></li><?php endforeach; ?>
        <?php if (empty($profile['equipes'] ?? $profile['teams'] ?? [])): ?><li class="list-group-item text-muted">Aucune équipe.</li><?php endif; ?>
      </ul></div></div></div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="card"><div class="card-header"><h3 class="card-title">Compétences</h3></div><div class="card-body p-0">
          <table class="table table-sm mb-0"><thead><tr><th>Compétence</th><th>Niveau</th></tr></thead><tbody>
          <?php foreach (($profile['competences'] ?? $profile['skills'] ?? []) as $skill): ?>
            <tr><td><?= $e($skill['cmp_nom'] ?? $skill['skl_label'] ?? '') ?></td><td><?= $e($skill['nco_nom'] ?? $skill['usk_level'] ?? '—') ?></td></tr>
          <?php endforeach; ?>
          <?php if (empty($profile['competences'] ?? $profile['skills'] ?? [])): ?><tr><td colspan="2" class="text-muted p-3">Aucune compétence renseignée.</td></tr><?php endif; ?>
          </tbody></table>
        </div></div>
      </div>
      <div class="col-md-6">
        <div class="card"><div class="card-header"><h3 class="card-title">Certifications</h3></div><div class="card-body p-0">
          <table class="table table-sm mb-0"><thead><tr><th>Certification</th><th>Expiration</th></tr></thead><tbody>
          <?php foreach (($profile['certifications'] ?? $profile['qualifications'] ?? []) as $certification): ?>
            <tr><td><?= $e($certification['cer_nom'] ?? $certification['qua_label'] ?? '') ?></td><td><?= $formatDate($certification['ceu_expire_le'] ?? $certification['uqu_expires_at'] ?? null) ?></td></tr>
          <?php endforeach; ?>
          <?php if (empty($profile['certifications'] ?? $profile['qualifications'] ?? [])): ?><tr><td colspan="2" class="text-muted p-3">Aucune certification renseignée.</td></tr><?php endif; ?>
          </tbody></table>
        </div></div>
      </div>
    </div>

    <?php
    // ── B-01 : Supérieur hiérarchique ─────────────────────────────────────
    // L 'administrateur général et le super-admin n ont pas de supérieur
    // hiérarchique (règle métier §7.2 PROJET.md).
    $sessionRoles   = $_SESSION["user"]["role_codes"] ?? $_SESSION["user"]["roles"] ?? $_SESSION["user_roles"] ?? [];
    $isTopLevel     = array_intersect(
        ["administrateur_general_societe", "super_administrateur", "super_admin"],
        (array) $sessionRoles
    ) !== [];
    $profileManagers = $profile["managers"] ?? [];
    ?>

    <?php if (!$isTopLevel): ?>
    <div class="row mt-3">
      <div class="col-md-6">
        <div class="card card-outline card-secondary">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-sitemap mr-1"></i> Supérieur hiérarchique
            </h3>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <?php if (!empty($profileManagers)): ?>
                <?php foreach ($profileManagers as $mgr): ?>
                  <li class="list-group-item d-flex align-items-center">
                    <span class="d-inline-flex align-items-center justify-content-center bg-secondary rounded-circle mr-3"
                          style="width:36px;height:36px;color:#fff;font-size:.95rem;flex-shrink:0;">
                      <?= $e(strtoupper(substr(trim(($mgr["pui_prenom"] ?? "") . " " . ($mgr["pui_nom"] ?? "")), 0, 1) ?: "?")) ?>
                    </span>
                    <div>
                      <strong><?= $e(trim(($mgr["pui_prenom"] ?? "") . " " . ($mgr["pui_nom"] ?? ""))) ?></strong>
                      <?php if (!empty($mgr["uti_email"])): ?>
                        <br><small class="text-muted"><i class="fas fa-envelope mr-1"></i><?= $e($mgr["uti_email"]) ?></small>
                      <?php endif; ?>
                      <?php if (!empty($mgr["societe_nom"])): ?>
                        <br><small class="text-muted"><i class="fas fa-building mr-1"></i><?= $e($mgr["societe_nom"]) ?></small>
                      <?php endif; ?>
                    </div>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="list-group-item text-muted">
                  <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                  Aucun supérieur hiérarchique affecté.
                  Contactez votre administrateur.
                </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <a class="btn btn-outline-info" href="/profile/gdpr">Mes données personnelles et demandes RGPD</a>
  </div>
</section>
