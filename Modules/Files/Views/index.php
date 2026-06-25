<?php defined('AUTOSAV_ROOT') or die; $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="h3 mb-0">Fichiers</h1><p class="text-muted mb-0">Stockage aligné sur <code>sav_fichiers</code>, <code>sav_contenus_fichiers</code> et <code>sav_liaisons_fichiers</code>.</p></div>
    <a class="btn btn-primary" href="/files/create">Ajouter un fichier</a>
  </div>
  <div class="row mb-3">
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($stats['total'] ?? 0) ?></h3><p>Fichiers</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= number_format(((int)($stats['taille_octets'] ?? 0))/1048576, 2, ',', ' ') ?></h3><p>Mo stockés</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($stats['chiffres'] ?? 0) ?></h3><p>Chiffrés</p></div></div></div>
    <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($stats['archives'] ?? 0) ?></h3><p>Archivés</p></div></div></div>
  </div>
  <div class="card mb-3"><div class="card-body"><form method="get" action="/files" class="row g-2">
    <div class="col-md-5"><label>Recherche</label><input class="form-control" name="q" value="<?= $e($filters['q'] ?? '') ?>" placeholder="Nom, MIME, checksum"></div>
    <div class="col-md-3"><label>Type cible</label><input class="form-control" name="target_type" value="<?= $e($filters['target_type'] ?? '') ?>" placeholder="utilisateur, societe..."></div>
    <div class="col-md-2"><label>ID cible</label><input class="form-control" type="number" name="target_id" value="<?= (int)($filters['target_id'] ?? 0) ?>"></div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Filtrer</button></div>
  </form></div></div>
  <div class="card"><div class="card-body p-0 table-responsive"><table class="table table-sm table-hover mb-0">
    <thead><tr><th>Nom</th><th>MIME</th><th>Taille</th><th>Checksum</th><th>Liaisons</th><th>Créé</th><th class="text-right">Actions</th></tr></thead>
    <tbody>
    <?php foreach (($files ?? []) as $file): ?>
      <tr>
        <td><a href="/files/<?= (int)$file['fic_id'] ?>"><?= $e($file['fic_nom_original'] ?? '') ?></a><?= !empty($file['fic_est_chiffre']) ? ' <span class="badge badge-dark">chiffré</span>' : '' ?></td>
        <td><code><?= $e($file['fic_mime_type'] ?? '') ?></code></td>
        <td><?= number_format(((int)($file['fic_taille_octets'] ?? 0))/1024, 1, ',', ' ') ?> Ko</td>
        <td><small><code><?= $e(substr((string)($file['fic_checksum_sha256'] ?? ''), 0, 16)) ?>…</code></small></td>
        <td><?= (int)($file['liaisons_total'] ?? 0) ?><br><small class="text-muted"><?= $e($file['cibles'] ?? '') ?></small></td>
        <td><?= $e($file['fic_cree_le'] ?? '') ?></td>
        <td class="text-right"><a class="btn btn-sm btn-outline-primary" href="/files/<?= (int)$file['fic_id'] ?>">Voir</a> <a class="btn btn-sm btn-outline-secondary" href="/files/<?= (int)$file['fic_id'] ?>/download">Télécharger</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($files)): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucun fichier.</td></tr><?php endif; ?>
    </tbody>
  </table></div></div>
</div>
