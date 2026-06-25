<?php
$type = htmlspecialchars((string) ($type ?? 'info'), ENT_QUOTES, 'UTF-8');
$title = htmlspecialchars((string) ($title ?? 'Information'), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars((string) ($message ?? ''), ENT_QUOTES, 'UTF-8');
$actionUrl = htmlspecialchars((string) ($action_url ?? '/auth/login'), ENT_QUOTES, 'UTF-8');
$actionLabel = htmlspecialchars((string) ($action_label ?? 'Continuer'), ENT_QUOTES, 'UTF-8');
?>
<div class="card shadow-sm mx-auto autosav-message-box">
    <div class="card-body text-center">
        <div class="alert alert-<?= $type ?> mb-4">
            <h4 class="alert-heading"><?= $title ?></h4>
            <p class="mb-0"><?= $message ?></p>
        </div>
        <a class="btn btn-primary" href="<?= $actionUrl ?>"><?= $actionLabel ?></a>
    </div>
</div>
