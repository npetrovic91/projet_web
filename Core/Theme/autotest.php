<?php
declare(strict_types=1);

use Nenad\Autosav\Core\Theme\Components\Sidebar;

include dirname(__DIR__, 2) . '/Modules/AutoTests/Views/module.php';

$sidebarPreview = (new Sidebar())->render();
$activeConcession = $_SESSION['active_concession_id'] ?? $_SESSION['active_company_id'] ?? null;
$activeBrand = $_SESSION['active_brand_id'] ?? null;
?>
<div class="card mt-3" id="autotest-sidebar-context">
    <div class="card-header">
        <h3 class="card-title">Test visuel - selecteurs de contexte sidebar</h3>
    </div>
    <div class="card-body">
        <p class="text-muted">
            Ce test verifie que les champs <code>Societe / concession active</code> et
            <code>Marque active</code> relisent les valeurs de <code>$_SESSION</code>.
        </p>
        <dl class="row">
            <dt class="col-sm-3">active_concession_id</dt>
            <dd class="col-sm-9"><code><?= htmlspecialchars((string)($activeConcession ?? 'NULL'), ENT_QUOTES, 'UTF-8') ?></code></dd>
            <dt class="col-sm-3">active_company_id</dt>
            <dd class="col-sm-9"><code><?= htmlspecialchars((string)($_SESSION['active_company_id'] ?? 'NULL'), ENT_QUOTES, 'UTF-8') ?></code></dd>
            <dt class="col-sm-3">active_brand_id</dt>
            <dd class="col-sm-9"><code><?= htmlspecialchars((string)($activeBrand ?? 'NULL'), ENT_QUOTES, 'UTF-8') ?></code></dd>
        </dl>
        <div class="border rounded p-3 bg-dark" style="max-width:320px;overflow:auto">
            <?= $sidebarPreview ?>
        </div>
    </div>
</div>
