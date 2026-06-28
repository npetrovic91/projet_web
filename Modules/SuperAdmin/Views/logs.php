<?php
declare(strict_types=1);
/** @var array $canaux */
/** @var string $canalActif */
/** @var string $recherche */
/** @var string $niveau */
/** @var int $limite */
/** @var array $resultat */

$badgeNiveau = static function (?string $niveau): string {
    $classe = match (mb_strtolower((string) $niveau)) {
        'emergency', 'alert', 'critical', 'error' => 'badge-danger',
        'warning', 'notice' => 'badge-warning',
        'info' => 'badge-info',
        'debug' => 'badge-secondary',
        default => 'badge-light',
    };
    return '<span class="badge ' . $classe . '">' . htmlspecialchars((string) ($niveau ?: '?'), ENT_QUOTES, 'UTF-8') . '</span>';
};

$formaterTaille = static function (int $octets): string {
    if ($octets < 1024) return $octets . ' o';
    if ($octets < 1024 * 1024) return round($octets / 1024, 1) . ' Ko';
    return round($octets / (1024 * 1024), 1) . ' Mo';
};
?>
<section class="content-header"><div class="container-fluid"><h1>Journaux applicatifs</h1></div></section>
<section class="content"><div class="container-fluid">
    <div class="alert alert-info">Lecture seule — réservé au super-administrateur. Les fichiers de <code>storage/logs/</code> ne sont jamais modifiés par cette page.</div>

    <ul class="nav nav-tabs mb-3">
        <?php foreach ($canaux as $canal): ?>
            <li class="nav-item">
                <a class="nav-link <?= $canal['nom'] === $canalActif ? 'active' : '' ?>"
                   href="<?= url('/super-admin/logs?canal=' . rawurlencode($canal['nom'])) ?>">
                    <?= htmlspecialchars($canal['nom'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge badge-light ml-1"><?= $formaterTaille((int) $canal['taille_octets']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <form method="get" action="<?= url('/super-admin/logs') ?>" class="form-inline mb-3">
        <input type="hidden" name="canal" value="<?= htmlspecialchars($canalActif, ENT_QUOTES, 'UTF-8') ?>">
        <input type="text" name="q" class="form-control mr-2 mb-1" placeholder="Recherche dans les lignes…" value="<?= htmlspecialchars($recherche, ENT_QUOTES, 'UTF-8') ?>">
        <select name="niveau" class="form-control mr-2 mb-1">
            <option value="">Tous niveaux</option>
            <?php foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $n): ?>
                <option value="<?= $n ?>" <?= $niveau === $n ? 'selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
        <select name="limite" class="form-control mr-2 mb-1">
            <?php foreach ([50, 100, 200, 500, 1000] as $l): ?>
                <option value="<?= $l ?>" <?= $limite === $l ? 'selected' : '' ?>><?= $l ?> lignes</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary mb-1">Filtrer</button>
        <a href="<?= url('/super-admin/logs?canal=' . rawurlencode($canalActif)) ?>" class="btn btn-outline-secondary mb-1 ml-2">Réinitialiser</a>
    </form>

    <?php if (!$resultat['fichier_existe']): ?>
        <div class="alert alert-warning">Ce canal n'a encore aucun fichier de log (<code><?= htmlspecialchars($canalActif, ENT_QUOTES, 'UTF-8') ?>.log</code> absent — normal s'il n'y a encore rien eu à journaliser).</div>
    <?php else: ?>
        <p class="text-muted">
            <?= (int) $resultat['total_lignes'] ?> ligne(s) correspondante(s)
            <?php if ($resultat['tronque']): ?>
                — <span class="text-warning">fichier volumineux, seule la fin a été lue</span>
            <?php endif; ?>
        </p>
        <div class="card">
            <div class="card-body p-0" style="max-height:70vh;overflow:auto">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th style="width:170px">Date</th><th style="width:90px">Niveau</th><th>Message</th></tr></thead>
                    <tbody>
                    <?php foreach ($resultat['lignes'] as $ligne): ?>
                        <tr>
                            <td class="text-nowrap"><?= htmlspecialchars((string) ($ligne['date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $ligne['date'] !== null ? $badgeNiveau($ligne['niveau']) : '' ?></td>
                            <td style="white-space:pre-wrap;word-break:break-word">
                                <?= htmlspecialchars((string) ($ligne['message'] ?? $ligne['brut']), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($resultat['lignes'] === []): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Aucune ligne ne correspond à ce filtre.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div></section>
