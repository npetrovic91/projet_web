<?php $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Nouvelle demande de validation</h1><a class="btn btn-outline-secondary" href="/validations">Retour</a></div>
  <form class="card card-body" method="post" action="/validations/store">
    <?= $csrfField ?? csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Type de demande</label><select class="form-select" name="dva_type_demande" required><?php foreach (($refs['types_demande'] ?? []) as $t): ?><option value="<?= $h($t) ?>"><?= $h($t) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Société</label><select class="form-select" name="dva_societe_id"><option value="">Contexte global</option><?php foreach (($refs['societes'] ?? []) as $s): ?><option value="<?= (int)$s['soc_id'] ?>"><?= $h($s['soc_nom']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Demandeur</label><select class="form-select" name="dva_demandeur_utilisateur_id"><option value="">Utilisateur connecté</option><?php foreach (($refs['utilisateurs'] ?? []) as $u): ?><option value="<?= (int)$u['use_id'] ?>"><?= $h(trim((string)$u['nom_complet']) ?: $u['use_email']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Table cible</label><input class="form-control" name="dva_table_cible" placeholder="ex: sav_demandes_validation, sav_societes, sav_utilisateurs"></div>
      <div class="col-md-2"><label class="form-label">ID cible</label><input class="form-control" type="number" name="dva_id_cible"></div>
      <div class="col-md-12"><label class="form-label">Motif</label><textarea class="form-control" name="dva_motif" rows="4" placeholder="Justification de la demande"></textarea></div>
      <div class="col-md-12"><label class="form-label">Données JSON optionnelles</label><textarea class="form-control font-monospace" name="dva_donnees_json" rows="6" placeholder='{"montant": 1200, "devise": "EUR"}'></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Créer la demande</button></div>
  </form>
</div>
