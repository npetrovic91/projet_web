<?php
$errors = $errors ?? [];
$email = htmlspecialchars((string) ($email ?? ''), ENT_QUOTES, 'UTF-8');
$csrf = htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8');
$redirect = htmlspecialchars((string) ($redirect ?? '/dashboard'), ENT_QUOTES, 'UTF-8');
?>
<div class="login-box mx-auto autosav-login-box">
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header text-center">
            <a href="/" class="h1"><b>AUTO</b>SAV</a>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Connexion à la plateforme</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ((array) $errors as $error): ?>
                        <div><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/auth/login" autocomplete="on">
                <?= $csrfField ?? csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= $redirect ?>">

                <div class="input-group mb-3">
                    <input type="text" name="email" class="form-control" placeholder="Email ou identifiant" value="<?= $email ?>" required autofocus>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>
                </div>

                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
            </form>

            <p class="mb-0 mt-3 text-center">
                <a href="/auth/forgot-password">Mot de passe oublié ?</a>
            </p>
        </div>
    </div>
</div>
