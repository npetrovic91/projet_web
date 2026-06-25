<?php
declare(strict_types=1);
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$libelles = [
    'support' => 'Support client',
    'audit' => 'Audit',
    'securite' => 'Sécurité',
    'incident' => 'Incident technique',
    'maintenance' => 'Maintenance exceptionnelle',
];
?>
<section class="content-header"><div class="container-fluid"><h1>Justification d’accès — données métier</h1></div></section>
<section class="content"><div class="container-fluid">
  <div class="alert alert-warning">
    <strong>Accès exceptionnel.</strong> En tant que super_admin (rôle technique de plateforme), vous n’avez normalement
    pas vocation à consulter les données métier d’une société cliente. Cet accès n’est autorisé qu’en mode
    support, audit, sécurité, incident ou maintenance, et sera intégralement journalisé : utilisateur, motif,
    justification, société, date/heure et durée de consultation.
  </div>
  <div class="card card-outline card-warning">
    <div class="card-header"><h3 class="card-title">Société concernée : <?= $e($societeNom ?? '') ?></h3></div>
    <form method="post" action="<?= url('/super-admin/justification') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="societe_id" value="<?= (int) ($societeId ?? 0) ?>">
      <input type="hidden" name="retour" value="<?= $e($retour ?? '') ?>">
      <div class="card-body">
        <div class="form-group">
          <label>Motif <span class="text-danger">*</span></label>
          <select name="motif" class="form-control" required>
            <option value="">— Sélectionner —</option>
            <?php foreach (($motifs ?? []) as $motif): ?>
              <option value="<?= $e($motif) ?>"><?= $e($libelles[$motif] ?? $motif) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Justification <span class="text-danger">*</span></label>
          <textarea name="justification" class="form-control" rows="4" minlength="10" required
                    placeholder="Décrivez précisément la raison de cet accès (ex. ticket support #1234, incident en cours, audit planifié...)."></textarea>
          <small class="form-text text-muted">10 caractères minimum. Cette justification est journalisée de façon permanente.</small>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between">
        <a href="<?= url('/super-admin') ?>" class="btn btn-secondary">Annuler</a>
        <button type="submit" class="btn btn-warning"><i class="fas fa-shield-alt mr-1"></i>Confirmer et accéder</button>
      </div>
    </form>
  </div>
</div></section>
