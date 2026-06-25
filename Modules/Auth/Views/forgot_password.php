<?php
$errors = $errors ?? [];
$csrf = htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8');
$sent = (bool) ($sent ?? false);
?>
<div class="login-box mx-auto autosav-form-box">
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header text-center"><a href="/auth/login" class="h1"><b>AUTO</b>SAV</a></div>
        <div class="card-body">
            <p class="login-box-msg">Réinitialisation du mot de passe</p>

            <?php if ($sent): ?>
                <div class="alert alert-success">Si le compte existe, un lien de réinitialisation sera envoyé.</div>
                <?php if (!empty($dev_token) && defined('APP_DEBUG') && APP_DEBUG): ?>
                    <div class="alert alert-warning small">Mode debug : <a href="/auth/reset-password/<?= htmlspecialchars((string) $dev_token, ENT_QUOTES, 'UTF-8') ?>">lien de test</a></div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ((array) $errors as $error): ?><div><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/auth/forgot-password">
                <?= $csrfField ?? csrf_field() ?>
                <div class="input-group mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Demander un lien</button>
            </form>

            <p class="mb-0 mt-3 text-center"><a href="/auth/login">Retour à la connexion</a></p>
        </div>
    </div>
</div>
