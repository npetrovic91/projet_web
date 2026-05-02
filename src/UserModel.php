<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Mot de passe oubliÃ©', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/vendor/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/assets/vendor/adminlte/css/adminlte.min.css">
    <link rel="stylesheet" href="/assets/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .login-page { background-color: #343a40; }
        .login-box { width: 380px; }
        .login-logo a { color: #fff; font-size: 1.8rem; font-weight: 700; }
        .card-outline.card-primary { border-top: 3px solid #007bff; }
    </style>
</head>
<body class="hold-transition login-page">

<div class="login-box">
    <div class="login-logo">
        <a href="/"><strong>AUTO</strong>SAV</a>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <p class="login-box-msg mb-0">RÃ©initialisation du mot de passe</p>
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

            <p class="login-box-msg text-muted" style="font-size:0.9rem;">
                Saisissez votre adresse email. Si elle est associÃ©e Ã  un compte, vous recevrez un lien de rÃ©initialisation.
            </p>

            <form method="POST" action="/auth/forgot-password" novalidate id="forgotForm">
                <input type="hidden" name="<?= CSRF_FORM_FIELD ?>"
                       value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="input-group mb-3">
                    <input type="email"
                           name="email"
                           class="form-control"
                           placeholder="Adresse email"
                           autocomplete="email"
                           required
                           maxlength="255"
                           autofocus>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <a href="/" class="btn btn-secondary btn-block">
                            <i class="fas fa-arrow-left mr-1"></i>Retour
                        </a>
                    </div>
                    <div class="col-6">
                        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                            <i class="fas fa-paper-plane mr-1"></i>Envoyer
                        </button>
                    </div>
                </div>
            </form>
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
document.getElementById('forgotForm').addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Envoi...';
});
</script>
</body>
</html>

