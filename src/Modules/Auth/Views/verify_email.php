<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Validation Email', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/vendor/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/assets/vendor/adminlte/css/adminlte.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .login-page { background-color: #343a40; }
        .login-box { width: 400px; }
        .login-logo a { color: #fff; font-size: 1.8rem; font-weight: 700; }
    </style>
</head>
<body class="hold-transition login-page">

<div class="login-box">
    <div class="login-logo">
        <a href="/"><strong>AUTO</strong>SAV</a>
    </div>

    <div class="card">
        <div class="card-body text-center py-5">

            <?php $status = $status ?? 'error'; ?>

            <?php if ($status === 'success'): ?>
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4 class="text-success">Email validÃ© !</h4>
                <p class="text-muted"><?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <a href="/" class="btn btn-primary mt-3">
                    <i class="fas fa-sign-in-alt mr-1"></i>Se connecter
                </a>

            <?php elseif ($status === 'already_verified'): ?>
                <i class="fas fa-info-circle fa-4x text-info mb-3"></i>
                <h4 class="text-info">DÃ©jÃ  validÃ©</h4>
                <p class="text-muted"><?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <a href="/" class="btn btn-primary mt-3">
                    <i class="fas fa-sign-in-alt mr-1"></i>Se connecter
                </a>

            <?php else: ?>
                <i class="fas fa-exclamation-triangle fa-4x text-danger mb-3"></i>
                <h4 class="text-danger">Lien invalide</h4>
                <p class="text-muted"><?= htmlspecialchars($message ?? 'Ce lien est invalide ou a expirÃ©.', ENT_QUOTES, 'UTF-8') ?></p>
                <a href="/" class="btn btn-secondary mt-3 mr-2">
                    <i class="fas fa-home mr-1"></i>Accueil
                </a>
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
</body>
</html>

