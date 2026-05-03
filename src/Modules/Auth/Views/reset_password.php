<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Nouveau mot de passe', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/vendor/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/assets/vendor/adminlte/css/adminlte.min.css">
    <link rel="stylesheet" href="/assets/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .login-page { background-color: #343a40; }
        .login-box { width: 400px; }
        .login-logo a { color: #fff; font-size: 1.8rem; font-weight: 700; }
        .card-outline.card-warning { border-top: 3px solid #ffc107; }
        .password-strength { height: 6px; border-radius: 3px; transition: all .3s; }
    </style>
</head>
<body class="hold-transition login-page">

<div class="login-box">
    <div class="login-logo">
        <a href="/"><strong>AUTO</strong>SAV</a>
    </div>

    <div class="card card-outline card-warning">
        <div class="card-header text-center">
            <p class="login-box-msg mb-0">DÃ©finir un nouveau mot de passe</p>
        </div>

        <div class="card-body login-card-body">

            <?php if (!empty($flash)): ?>
                <?php foreach ($flash as $type => $messages): ?>
                    <?php foreach ((array) $messages as $msg): ?>
                        <?php $class = match($type) { 'success' => 'alert-success', 'error' => 'alert-danger', 'warning' => 'alert-warning', default => 'alert-info' }; ?>
                        <div class="alert <?= $class ?> alert-dismissible fade show">
                            <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!($tokenValid ?? false)): ?>

                <!-- Token invalide ou expirÃ© -->
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Ce lien de rÃ©initialisation est invalide ou a expirÃ©.<br>
                    <a href="/auth/forgot-password">Faire une nouvelle demande</a>
                </div>

            <?php else: ?>

                <!-- RÃ¨gles de complexitÃ© -->
                <p class="text-muted mb-3" style="font-size:0.85rem;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Minimum <?= PASSWORD_MIN_LENGTH ?> caractÃ¨res, majuscule, minuscule, chiffre et caractÃ¨re spÃ©cial requis.
                </p>

                <form method="POST" action="/auth/reset-password" novalidate id="resetForm">
                    <input type="hidden" name="<?= CSRF_FORM_FIELD ?>"
                           value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="token"
                           value="<?= $token /* dÃ©jÃ  Ã©chappÃ© dans le contrÃ´leur */ ?>">

                    <!-- Nouveau mot de passe -->
                    <div class="input-group mb-1">
                        <input type="password"
                               name="password"
                               id="password"
                               class="form-control"
                               placeholder="Nouveau mot de passe"
                               autocomplete="new-password"
                               required
                               minlength="<?= PASSWORD_MIN_LENGTH ?>"
                               maxlength="<?= PASSWORD_MAX_LENGTH ?>">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" id="togglePwd">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Indicateur de force -->
                    <div class="mb-3">
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar password-strength" id="strengthBar" role="progressbar"
                                 style="width:0%"></div>
                        </div>
                        <small id="strengthLabel" class="text-muted"></small>
                    </div>

                    <!-- Confirmation -->
                    <div class="input-group mb-4">
                        <input type="password"
                               name="password_confirm"
                               id="passwordConfirm"
                               class="form-control"
                               placeholder="Confirmer le mot de passe"
                               autocomplete="new-password"
                               required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div id="matchError" class="text-danger mb-2" style="display:none;font-size:.85rem;">
                        <i class="fas fa-times mr-1"></i>Les mots de passe ne correspondent pas.
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <a href="/" class="btn btn-secondary btn-block">Annuler</a>
                        </div>
                        <div class="col-6">
                            <button type="submit" class="btn btn-warning btn-block text-white" id="submitBtn">
                                <i class="fas fa-save mr-1"></i>Enregistrer
                            </button>
                        </div>
                    </div>
                </form>

            <?php endif; ?>
        </div>

        <div class="card-footer text-center text-muted" style="font-size:0.8rem;">
            &copy; <?= date('Y') ?> Autosav
        </div>
    </div>
</div>

<script src="/assets/vendor/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/assets/vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/vendor/adminlte/js/adminlte.min.js"></script>

<script>
(function () {
    'use strict';

    var pwd = document.getElementById('password');
    var pwdConfirm = document.getElementById('passwordConfirm');
    var bar = document.getElementById('strengthBar');
    var label = document.getElementById('strengthLabel');
    var matchErr = document.getElementById('matchError');
    var toggleBtn = document.getElementById('togglePwd');
    var toggleIcon = document.getElementById('toggleIcon');

    if (!pwd) return;

    // Toggle visibilitÃ© mot de passe
    toggleBtn.addEventListener('click', function () {
        if (pwd.type === 'password') {
            pwd.type = 'text';
            toggleIcon.className = 'fas fa-eye-slash';
        } else {
            pwd.type = 'password';
            toggleIcon.className = 'fas fa-eye';
        }
    });

    // Indicateur de force
    pwd.addEventListener('input', function () {
        var val = this.value;
        var score = 0;
        if (val.length >= <?= PASSWORD_MIN_LENGTH ?>) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        var levels = [
            { pct: 0,   color: '#dc3545', text: '' },
            { pct: 20,  color: '#dc3545', text: 'TrÃ¨s faible' },
            { pct: 40,  color: '#fd7e14', text: 'Faible' },
            { pct: 60,  color: '#ffc107', text: 'Moyen' },
            { pct: 80,  color: '#28a745', text: 'Fort' },
            { pct: 100, color: '#20c997', text: 'TrÃ¨s fort' },
        ];
        var lvl = levels[score] || levels[0];
        bar.style.width = lvl.pct + '%';
        bar.style.backgroundColor = lvl.color;
        label.textContent = lvl.text;
    });

    // VÃ©rification correspondance
    pwdConfirm.addEventListener('input', function () {
        if (this.value && this.value !== pwd.value) {
            matchErr.style.display = 'block';
        } else {
            matchErr.style.display = 'none';
        }
    });

    // PrÃ©venir la soumission si non-concordance
    document.getElementById('resetForm').addEventListener('submit', function (e) {
        if (pwdConfirm.value !== pwd.value) {
            e.preventDefault();
            matchErr.style.display = 'block';
            return;
        }
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Enregistrement...';
    });
})();
</script>

</body>
</html>

