<?php
$errors = $errors ?? [];
$csrf = htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8');
$token = htmlspecialchars((string) ($token ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="login-box mx-auto autosav-form-box">
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header text-center"><a href="/auth/login" class="h1"><b>AUTO</b>SAV</a></div>
        <div class="card-body">
            <p class="login-box-msg">Choisissez un nouveau mot de passe</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ((array) $errors as $error): ?><div><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/auth/reset-password">
                <?= $csrfField ?? csrf_field() ?>
                <input type="hidden" name="token" value="<?= $token ?>">
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Nouveau mot de passe" required>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirmation" required>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Modifier le mot de passe</button>
            </form>
        </div>
    </div>
</div>
