<?php
declare(strict_types=1);
?>
<section class="content-header"><div class="container-fluid"><h1>Demande de débogage</h1></div></section>
<section class="content"><div class="container-fluid">
    <div class="alert alert-warning"><?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?></div>
    <dl class="row">
        <dt class="col-sm-2">Module</dt><dd class="col-sm-10"><?= htmlspecialchars((string)$module, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt class="col-sm-2">Classe</dt><dd class="col-sm-10"><?= htmlspecialchars((string)$classe, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt class="col-sm-2">Fonction</dt><dd class="col-sm-10"><?= htmlspecialchars((string)$fonction, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>
    <a class="btn btn-primary" href="<?= url('/autotests/module/' . rawurlencode((string)$module)) ?>">Retour au module</a>
</div></section>
