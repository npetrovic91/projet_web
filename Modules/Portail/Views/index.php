<?php
$data = $data ?? [];
$contexte = $data['contexte'] ?? [];
$utilisateur = $data['utilisateur'] ?? [];
?>
<section class="page-header">
    <h1>Portail société</h1>
    <p>Point d’entrée utilisateur selon le contexte actif.</p>
</section>

<div class="grid grid-3">
    <article class="card">
        <h2>Utilisateur</h2>
        <p><?= htmlspecialchars(trim(($utilisateur['pui_prenom'] ?? '') . ' ' . ($utilisateur['pui_nom'] ?? '')) ?: ($utilisateur['uti_email'] ?? 'Utilisateur'), ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="card">
        <h2>Société active</h2>
        <p>#<?= (int)($contexte['societe_id'] ?? 0) ?></p>
    </article>
    <article class="card">
        <h2>Concession / marque</h2>
        <p>Concession #<?= (int)($contexte['concession_id'] ?? 0) ?> — Marque #<?= (int)($contexte['marque_id'] ?? 0) ?></p>
    </article>
</div>

<section class="card">
    <h2>Actions rapides</h2>
    <p>Le portail applique les filtres par société, concession, marque, service et équipe avant d’ouvrir les modules métier.</p>
    <div class="actions">
        <a class="btn btn-primary" href="<?= url('/portail/contexte') ?>">Modifier le contexte actif</a>
        <a class="btn" href="<?= url('/dashboard') ?>">Ouvrir le tableau de bord</a>
    </div>
</section>
