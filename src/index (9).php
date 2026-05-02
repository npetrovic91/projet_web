<?php
declare(strict_types=1);
/**
 * Vue : Page de connexion Autosav
 * Layout : AdminLTE login page (classe body login-page)
 * Variables injectÃ©es : $pageTitle, $csrfToken, $flash
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= htmlspecialchars($pageTitle ?? 'Connexion â€” Autosav', ENT_QUOTES, 'UTF-8') ?></title>

    <!-- AdminLTE CSS (local) -->
    <link rel="stylesheet" href="/assets/vendor/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/assets/vendor/adminlte/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="/assets/vendor/adminlte/css/adminlte.min.css">
    <!-- SweetAlert2 (local) -->
    <link rel="stylesheet" href="/assets/vendor/sweetalert2/sweetalert2.min.css">
    <!-- CSS Application -->
    <link rel="stylesheet" href="/assets/css/app.css">

    <style>
        .login-page { background-color: #343a40; }
        .login-box { width: 380px; }
        .login-logo a { color: #fff; font-size: 1.8rem; font-weight: 700; }
        .login-card-body { border-radius: 4px; }
        .btn-login { background-color: #007bff; border-color: #007bff; }
        .btn-login:hover { background-color: #0056b3; border-color: #004e8e; }
        .card-outline.card-primary { border-top: 3px solid #007bff; }
    </style>
</head>
<body class="hold-transition login-page">

<div class="login-box">

    <!-- Logo -->
    <div class="login-logo">
        <a href="/"><strong>AUTO</strong>SAV</a>
    </div>

    <!-- Carte de connexion -->
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <p class="login-box-msg mb-0">Connectez-vous a votre espace</p>
        </div>

        <div class="card-body login-card-body">

            <?php // Messages flash (succÃ¨s / erreur / warning) ?>
            <?php if (!empty($flash)): ?>
                <?php foreach ($flash as $type => $messages): ?>
                    <?php foreach ((array) $messages as $msg): ?>
                        <?php
                        $alertClass = match($type) {
                            'success' => 'alert-success',
                            'error'   => 'alert-danger',
                            'warning' => 'alert-warning',
                            default   => 'alert-info',
                        };
                        ?>
                        <div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Fermer">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Formulaire de connexion -->
            <form method="POST" action="/auth/login" novalidate id="loginForm">
                <!-- Token CSRF (R46) -->
                <input type="hidden" name="<?= CSRF_FORM_FIELD ?>"
                       value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <!-- Email -->
                <div class="input-group mb-3">
                    <input type="email"
                           name="email"
                           id="email"
                           class="form-control"
                           placeholder="Adresse email"
                           autocomplete="email"
                           required
                           maxlength="255">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="input-group mb-3">
                    <input type="password"
                           name="password"
                           id="password"
                           class="form-control"
                           placeholder="Mot de passe"
                           autocomplete="current-password"
                           required
                           maxlength="<?= PASSWORD_MAX_LENGTH ?>">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="row">
                    <div class="col-8">
                        <a href="/auth/forgot-password" class="text-muted">
                            <i class="fas fa-key mr-1"></i>Mot de passe oubliÃ© ?
                        </a>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-login btn-block text-white" id="loginBtn">
                            <i class="fas fa-sign-in-alt mr-1"></i>Connexion
                        </button>
                    </div>
                </div>
            </form>

        </div>
        <!-- /.card-body -->

        <div class="card-footer text-center text-muted" style="font-size: 0.8rem;">
            &copy; <?= date('Y') ?> Autosav â€” Tous droits rÃ©servÃ©s
        </div>
    </div>
    <!-- /.card -->

</div>
<!-- /.login-box -->

<!-- jQuery (local) -->
<script src="/assets/vendor/adminlte/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap (local) -->
<script src="/assets/vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE (local) -->
<script src="/assets/vendor/adminlte/js/adminlte.min.js"></script>
<!-- SweetAlert2 (local) -->
<script src="/assets/vendor/sweetalert2/sweetalert2.min.js"></script>

<script>
(function () {
    'use strict';

    // DÃ©sactiver le bouton submit pendant l'envoi du formulaire
    document.getElementById('loginForm').addEventListener('submit', function (e) {
        var btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Connexion...';
    });

    // Focus automatique sur le champ email
    document.getElementById('email').focus();
})();
</script>

</body>
</html>

