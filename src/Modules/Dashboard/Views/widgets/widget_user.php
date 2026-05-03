<?php
/**
 * Widget Utilisateur standard : Liens rapides
 * Visible : tous les rôles
 * Sync
 *
 * @var array $widget_context
 */
?>
<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-star mr-2"></i>Accès rapide
        </h3>
    </div>
    <div class="card-body p-2">
        <div class="row no-gutters">
            <div class="col-6 p-1">
                <a href="/profile" class="btn btn-block btn-outline-secondary btn-sm">
                    <i class="fas fa-user-edit d-block mb-1" style="font-size:1.4rem;"></i>
                    Mon profil
                </a>
            </div>
            <div class="col-6 p-1">
                <a href="/profile#password" class="btn btn-block btn-outline-secondary btn-sm">
                    <i class="fas fa-key d-block mb-1" style="font-size:1.4rem;"></i>
                    Mot de passe
                </a>
            </div>
            <div class="col-6 p-1">
                <a href="/profile/gdpr" class="btn btn-block btn-outline-secondary btn-sm">
                    <i class="fas fa-user-shield d-block mb-1" style="font-size:1.4rem;"></i>
                    Mes données
                </a>
            </div>
            <div class="col-6 p-1">
                <a href="/auth/logout" class="btn btn-block btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt d-block mb-1" style="font-size:1.4rem;"></i>
                    Déconnexion
                </a>
            </div>
        </div>
    </div>
</div>
