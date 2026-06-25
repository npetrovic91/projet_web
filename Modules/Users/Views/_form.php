<?php
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$user = $user ?? $utilisateur ?? [];
$fiche = $fiche ?? null;
$selectedSocietes = array_map('intval', array_column($fiche['societes'] ?? $fiche['companies'] ?? [], 'aus_societe_id'));
$selectedRoles = array_map('intval', array_column($fiche['roles'] ?? [], 'rcu_role_id'));
$selectedFonctions = array_map('intval', array_column($fiche['fonctions'] ?? $fiche['functions'] ?? [], 'fut_fonction_id'));
$selectedDepartements = array_map('intval', array_column($fiche['departements'] ?? $fiche['departments'] ?? [], 'udp_departement_id'));
$selectedServices = array_map('intval', array_column($fiche['services'] ?? [], 'usv_service_id'));
$selectedEquipes = array_map('intval', array_column($fiche['equipes'] ?? $fiche['teams'] ?? [], 'ueq_equipe_id'));
$selectedCompetences = [];
foreach (($fiche['competences'] ?? $fiche['skills'] ?? []) as $row) {
    $selectedCompetences[(int)($row['cut_competence_id'] ?? $row['usk_skill_id'] ?? 0)] = (int)($row['cut_niveau_competence_id'] ?? $row['usk_level_id'] ?? 0);
}
$selectedCertifications = [];
foreach (($fiche['certifications'] ?? $fiche['qualifications'] ?? []) as $row) {
    $selectedCertifications[(int)($row['ceu_certification_id'] ?? $row['uql_qualification_id'] ?? 0)] = [
        'issued_at' => $row['ceu_delivree_le'] ?? $row['uql_issued_at'] ?? '',
        'expires_at' => $row['ceu_expire_le'] ?? $row['uql_expires_at'] ?? '',
    ];
}
$managerId = (int) (($fiche['managers'][0]['hiu_superieur_utilisateur_id'] ?? 0));
$action = $action ?? '/users/store';
$mode = $mode ?? 'create';
$errors = $errors ?? $erreurs ?? [];
$defaultLevelId = (int) (($niveaux_competences[0]['nco_id'] ?? 0));
?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Erreur de validation</strong>
        <ul class="mb-0">
            <?php foreach ($errors as $message): ?>
                <li><?= $e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= url($action) ?>" class="card card-outline card-primary">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrf_token ?? '') ?>">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= $mode === 'edit' ? 'Modifier l’utilisateur et ses affectations' : 'Créer un utilisateur' ?></h3>
    </div>
    <div class="card-body">
        <h5 class="mb-3">Compte et profil</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Prénom *</label>
                <input class="form-control" name="pui_prenom" required value="<?= $e($user['pui_prenom'] ?? $user['use_firstname'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nom *</label>
                <input class="form-control" name="pui_nom" required value="<?= $e($user['pui_nom'] ?? $user['use_lastname'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input class="form-control" type="email" name="uti_email" required value="<?= $e($user['uti_email'] ?? $user['use_email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Identifiant</label>
                <input class="form-control" name="uti_identifiant" value="<?= $e($user['uti_identifiant'] ?? $user['use_username'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Téléphone</label>
                <input class="form-control" name="pui_telephone" value="<?= $e($user['pui_telephone'] ?? $user['use_phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Mobile</label>
                <input class="form-control" name="pui_mobile" value="<?= $e($user['pui_mobile'] ?? $user['use_mobile'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Statut</label>
                <?php $statut = (string) ($user['statut_code'] ?? 'actif'); ?>
                <select class="form-control" name="statut">
                    <option value="actif" <?= $statut === 'actif' ? 'selected' : '' ?>>Actif</option>
                    <option value="inactif" <?= $statut === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                    <option value="suspendu" <?= $statut === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                    <option value="bloque_securite" <?= $statut === 'bloque_securite' ? 'selected' : '' ?>>Bloqué sécurité</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Mot de passe <?= $mode === 'create' ? '' : '(laisser vide pour conserver)' ?></label>
                <input class="form-control" type="password" name="password" autocomplete="new-password">
                <?php if ($mode === 'create'): ?><small class="text-muted">Si vide, un mot de passe temporaire sera généré.</small><?php endif; ?>
            </div>
        </div>

        <hr>
        <h5 class="mb-3">Sociétés, rôles et hiérarchie</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Société principale *</label>
                <?php $activeCompany = (int) ($user['uti_societe_active_id'] ?? $user['use_active_company_id'] ?? ($selectedSocietes[0] ?? 0)); ?>
                <select class="form-control" name="societe_principale_id" required>
                    <option value="">— Choisir —</option>
                    <?php foreach (($societes ?? []) as $societe): ?>
                        <option value="<?= (int) $societe['soc_id'] ?>" <?= $activeCompany === (int) $societe['soc_id'] ? 'selected' : '' ?>><?= $e($societe['soc_nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Sociétés rattachées</label>
                <select class="form-control" name="societe_ids[]" multiple size="5">
                    <?php foreach (($societes ?? []) as $societe): ?>
                        <option value="<?= (int) $societe['soc_id'] ?>" <?= in_array((int) $societe['soc_id'], $selectedSocietes, true) || $activeCompany === (int) $societe['soc_id'] ? 'selected' : '' ?>><?= $e($societe['soc_nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Rôles applicatifs contextuels</label>
                <select class="form-control" name="role_ids[]" multiple size="5">
                    <?php foreach (($roles ?? []) as $role): ?>
                        <option value="<?= (int) $role['rol_id'] ?>" <?= in_array((int) $role['rol_id'], $selectedRoles, true) ? 'selected' : '' ?>><?= $e($role['rol_nom']) ?> (<?= $e($role['rol_code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Affectés au contexte de la société principale dans ce lot.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Fonctions métier</label>
                <select class="form-control" name="fonction_ids[]" multiple size="5">
                    <?php foreach (($fonctions ?? []) as $fonction): ?>
                        <option value="<?= (int) $fonction['fon_id'] ?>" <?= in_array((int) $fonction['fon_id'], $selectedFonctions, true) ? 'selected' : '' ?>><?= $e($fonction['fon_nom']) ?> (<?= $e($fonction['fon_code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Supérieur hiérarchique</label>
                <select class="form-control" name="manager_user_id">
                    <option value="">— Aucun / administrateur général —</option>
                    <?php foreach (($managers ?? []) as $manager): ?>
                        <option value="<?= (int) $manager['uti_id'] ?>" <?= $managerId === (int) $manager['uti_id'] ? 'selected' : '' ?>><?= $e(trim(($manager['pui_prenom'] ?? '') . ' ' . ($manager['pui_nom'] ?? '')) ?: ($manager['uti_email'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <hr>
        <h5 class="mb-3">Structure interne</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Départements</label>
                <select class="form-control" name="departement_ids[]" multiple size="6">
                    <?php foreach (($departements ?? []) as $departement): ?>
                        <option value="<?= (int) $departement['dep_id'] ?>" <?= in_array((int) $departement['dep_id'], $selectedDepartements, true) ? 'selected' : '' ?>><?= $e($departement['dep_nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Services</label>
                <select class="form-control" name="service_ids[]" multiple size="6">
                    <?php foreach (($services ?? []) as $service): ?>
                        <option value="<?= (int) $service['srv_id'] ?>" <?= in_array((int) $service['srv_id'], $selectedServices, true) ? 'selected' : '' ?>><?= $e($service['srv_nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Équipes</label>
                <select class="form-control" name="equipe_ids[]" multiple size="6">
                    <?php foreach (($equipes ?? []) as $equipe): ?>
                        <option value="<?= (int) $equipe['equ_id'] ?>" <?= in_array((int) $equipe['equ_id'], $selectedEquipes, true) ? 'selected' : '' ?>><?= $e($equipe['equ_nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <hr>
        <h5 class="mb-3">Compétences et certifications</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-2 h-100">
                    <h6>Compétences avec niveau</h6>
                    <?php if (empty($competences ?? [])): ?>
                        <p class="text-muted mb-0">Aucune compétence disponible.</p>
                    <?php endif; ?>
                    <?php foreach (($competences ?? []) as $competence): ?>
                        <?php $cid = (int) $competence['cmp_id']; $checked = array_key_exists($cid, $selectedCompetences); ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <label class="mb-0 flex-grow-1">
                                <input type="checkbox" name="competence_ids[]" value="<?= $cid ?>" <?= $checked ? 'checked' : '' ?>>
                                <?= $e($competence['cmp_nom']) ?> <small class="text-muted">(<?= $e($competence['cmp_code']) ?>)</small>
                            </label>
                            <select class="form-control form-control-sm" style="max-width:160px" name="competence_level_ids[<?= $cid ?>]">
                                <?php foreach (($niveaux_competences ?? []) as $niveau): ?>
                                    <?php $levelId = (int) $niveau['nco_id']; ?>
                                    <option value="<?= $levelId ?>" <?= (($selectedCompetences[$cid] ?? $defaultLevelId) === $levelId) ? 'selected' : '' ?>><?= $e($niveau['nco_nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-2 h-100">
                    <h6>Certifications</h6>
                    <?php if (empty($certifications ?? [])): ?>
                        <p class="text-muted mb-0">Aucune certification disponible.</p>
                    <?php endif; ?>
                    <?php foreach (($certifications ?? []) as $certification): ?>
                        <?php $cerId = (int) $certification['cer_id']; $selected = $selectedCertifications[$cerId] ?? null; ?>
                        <div class="border-bottom pb-2 mb-2">
                            <label class="mb-1">
                                <input type="checkbox" name="certification_ids[]" value="<?= $cerId ?>" <?= $selected ? 'checked' : '' ?>>
                                <?= $e($certification['cer_nom']) ?> <small class="text-muted">(<?= $e($certification['cer_code']) ?>)</small>
                            </label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <small class="text-muted">Délivrée le</small>
                                    <input class="form-control form-control-sm" type="date" name="certification_issued_at[<?= $cerId ?>]" value="<?= $e($selected['issued_at'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Expire le</small>
                                    <input class="form-control form-control-sm" type="date" name="certification_expires_at[<?= $cerId ?>]" value="<?= $e($selected['expires_at'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="<?= url('/users') ?>" class="btn btn-outline-secondary">Retour</a>
        <button type="submit" class="btn btn-primary">Enregistrer toutes les affectations</button>
    </div>
</form>
