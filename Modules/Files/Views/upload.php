<?php defined('AUTOSAV_ROOT') or die; $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <h1 class="h3">Ajouter un fichier</h1>
  <div class="card"><div class="card-body">
    <form method="post" action="/files/store" enctype="multipart/form-data">
      <input type="hidden" name="_csrf_token" value="<?= $e($csrf_token ?? '') ?>">
      <div class="form-group"><label>Fichier</label><input class="form-control" type="file" name="file" required></div>
      <div class="row">
        <div class="col-md-4"><label>Type cible optionnel</label><input class="form-control" name="lfi_cible_type" placeholder="utilisateur, societe, document_juridique..."></div>
        <div class="col-md-4"><label>ID cible optionnel</label><input class="form-control" type="number" name="lfi_cible_id" value="0"></div>
        <div class="col-md-4"><label>Type de liaison</label><input class="form-control" name="lfi_type_liaison" value="piece_jointe"></div>
      </div>
      <div class="form-check my-3"><input class="form-check-input" type="checkbox" name="fic_est_chiffre" value="1" id="encrypted"><label class="form-check-label" for="encrypted">Chiffrer le contenu applicativement</label></div>
      <button class="btn btn-primary">Enregistrer</button> <a class="btn btn-link" href="/files">Annuler</a>
    </form>
  </div></div>
</div>
