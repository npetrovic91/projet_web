<?php defined('AUTOSAV_ROOT') or die; ?>
<div class="container py-5">
  <div class="card mx-auto" style="max-width:640px"><div class="card-body text-center">
    <?php if (!empty($success)): ?>
      <h1 class="h4">Désabonnement confirmé</h1>
      <p>Vos préférences email ont été mises à jour.</p>
    <?php else: ?>
      <h1 class="h4">Lien invalide</h1>
      <p>Le lien de désabonnement est invalide ou expiré.</p>
    <?php endif; ?>
  </div></div>
</div>
