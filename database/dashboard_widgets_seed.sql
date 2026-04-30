-- ============================================================
-- SEED : sav_dashboard_widgets — Widgets initiaux du Dashboard
-- Convention : préfixe dwg_
-- IMPORTANT : Modifier ce fichier = ajouter un widget
--             Aucun déploiement de code PHP requis
-- ============================================================

INSERT INTO `sav_dashboard_widgets`
    (`dwg_code`, `dwg_label`, `dwg_view_file`, `dwg_roles`, `dwg_default_order`,
     `dwg_is_active`, `dwg_ajax_endpoint`, `dwg_description`)
VALUES

-- ---- Widgets synchrones (pas d'ajax_endpoint) ----

('profil',
 'Mon profil',
 'widget_profil.php',
 '["SUPERADMIN","CONSTRUCTEUR","IMPORTATEUR","CONCESSIONNAIRE","REPARATEUR","MANAGER","USER"]',
 10, 1, NULL,
 'Résumé du profil utilisateur — visible par tous les rôles'),

('acces_rapide',
 'Accès rapide',
 'widget_user.php',
 '["SUPERADMIN","CONSTRUCTEUR","IMPORTATEUR","CONCESSIONNAIRE","REPARATEUR","MANAGER","USER"]',
 90, 1, NULL,
 'Liens rapides vers profil, mot de passe, RGPD, déconnexion'),

('supervision',
 'Supervision globale',
 'widget_superadmin.php',
 '["SUPERADMIN"]',
 5, 1, NULL,
 'Statistiques globales : utilisateurs, entreprises, blocages, RGPD — SuperAdmin uniquement'),

('equipe',
 'Équipe de la structure active',
 'widget_concessionnaire.php',
 '["SUPERADMIN","CONSTRUCTEUR","IMPORTATEUR","CONCESSIONNAIRE","MANAGER"]',
 20, 1, NULL,
 'Membres de la structure active (concession/entreprise)'),

-- ---- Widgets asynchrones (ajax_endpoint défini) ----

('reseau_constructeur',
 'Mon réseau',
 'widget_constructeur.php',
 '["SUPERADMIN","CONSTRUCTEUR"]',
 15, 1, '/ajax/dashboard/widget/reseau_constructeur',
 'Vue réseau : nombre d\'importateurs et concessions rattachés'),

('securite',
 'Événements de sécurité',
 'widget_securite.php',
 '["SUPERADMIN"]',
 30, 1, '/ajax/dashboard/widget/securite',
 'Dernières tentatives de connexion — SuperAdmin uniquement'),

('notifications',
 'Mes notifications',
 'widget_notifications.php',
 '["SUPERADMIN","CONSTRUCTEUR","IMPORTATEUR","CONCESSIONNAIRE","REPARATEUR","MANAGER","USER"]',
 40, 1, '/ajax/dashboard/widget/notifications',
 'Notifications non lues — visible par tous les rôles');
