<?php
/**
 * AUTOSAV — Table de routage complète
 * Livrables L2 → L6
 *
 * Format : 'METHOD /path' => ['Controller\ClassName', 'action', ['middleware1', ...]]
 *
 * Namespaces contrôleurs (relatifs à Nenad\Autosav\Modules\) :
 *   Auth\Controllers\LoginController       → Auth\\Controllers\\LoginController
 *   Users\Controllers\UserController       → Users\\Controllers\\UserController
 *   Companies\Controllers\CompanyController→ Companies\\Controllers\\CompanyController
 *   Brands\Controllers\BrandController     → Brands\\Controllers\\BrandController
 *   Ajax\Controllers\ContextController     → Ajax\\Controllers\\ContextController
 */

declare(strict_types=1);

return [

    // ========================================================
    // PAGES PUBLIQUES — Aucun AJAX (R45)
    // ========================================================

    // Page d'accueil → login
    'GET /'                                => ['Auth\\Controllers\\LoginController',    'showLogin',        []],

    // Authentification
    'GET /login'                           => ['Auth\\Controllers\\LoginController',    'showLogin',        []],
    'POST /login'                          => ['Auth\\Controllers\\LoginController',    'processLogin',     ['csrf']],
    'GET /auth/login'                      => ['Auth\\Controllers\\LoginController',    'showLogin',        []],
    'POST /auth/login'                     => ['Auth\\Controllers\\LoginController',    'processLogin',     ['csrf']],
    'GET /logout'                          => ['Auth\\Controllers\\LogoutController',   'logout',           ['auth']],
    'GET /auth/logout'                     => ['Auth\\Controllers\\LogoutController',   'logout',           ['auth']],

    // Validation email
    'GET /auth/verify-email/{token}'       => ['Auth\\Controllers\\EmailController',   'verify',           []],
    'POST /auth/resend-verification'       => ['Auth\\Controllers\\EmailController',   'resend',           ['csrf']],

    // Mot de passe oublié / reset
    'GET /auth/forgot-password'            => ['Auth\\Controllers\\PasswordController','showForgot',       []],
    'POST /auth/forgot-password'           => ['Auth\\Controllers\\PasswordController','processForgot',    ['csrf']],
    'GET /auth/reset-password/{token}'     => ['Auth\\Controllers\\PasswordController','showReset',        []],
    'POST /auth/reset-password'            => ['Auth\\Controllers\\PasswordController','processReset',     ['csrf']],

    // ========================================================
    // CGU — Zone semi-authentifiée (session active requise)
    // ========================================================
    'POST /auth/terms/accept'              => ['Auth\\Controllers\\TermsController',   'accept',           ['auth', 'csrf']],
    'POST /auth/terms/refuse'              => ['Auth\\Controllers\\TermsController',   'refuse',           ['auth', 'csrf']],

    // ========================================================
    // DASHBOARD — Zone authentifiée
    // ========================================================
    'GET /dashboard'                       => ['Dashboard\\Controllers\\DashboardController', 'index', ['auth', 'maintenance']],

    // ========================================================
    // PROFIL UTILISATEUR
    // ========================================================
    'GET /profile'                         => ['Profile\\Controllers\\ProfileController',  'show',           ['auth', 'maintenance']],
    'POST /profile/update'                 => ['Profile\\Controllers\\ProfileController',  'update',         ['auth', 'csrf', 'maintenance']],
    'POST /profile/password'               => ['Profile\\Controllers\\ProfileController',  'changePassword', ['auth', 'csrf', 'maintenance']],
    'GET /profile/gdpr'                    => ['Profile\\Controllers\\ProfileController',  'gdpr',           ['auth', 'maintenance']],
    'POST /profile/gdpr/request'           => ['Profile\\Controllers\\ProfileController',  'gdprRequest',    ['auth', 'csrf', 'maintenance']],
    'GET /profile/gdpr/export'             => ['Profile\\Controllers\\ProfileController',  'gdprExport',     ['auth', 'maintenance']],

    // ========================================================
    // RGPD ADMINISTRATION - Module L9
    // ========================================================
    'GET /gdpr'                            => ['GDPR\\Controllers\\GdprController', 'index',     ['auth', 'maintenance']],
    'GET /admin/gdpr'                      => ['GDPR\\Controllers\\GdprController', 'index',     ['auth', 'maintenance']],
    'GET /gdpr/{id}'                       => ['GDPR\\Controllers\\GdprController', 'show',      ['auth', 'maintenance']],
    'GET /admin/gdpr/{id}'                 => ['GDPR\\Controllers\\GdprController', 'show',      ['auth', 'maintenance']],
    'POST /gdpr/{id}/accept'               => ['GDPR\\Controllers\\GdprController', 'accept',    ['auth', 'csrf', 'maintenance']],
    'POST /gdpr/{id}/reject'               => ['GDPR\\Controllers\\GdprController', 'reject',    ['auth', 'csrf', 'maintenance']],
    'GET /gdpr/{id}/export'                => ['GDPR\\Controllers\\GdprController', 'export',    ['auth', 'maintenance']],
    'POST /gdpr/{id}/anonymize'            => ['GDPR\\Controllers\\GdprController', 'anonymize', ['auth', 'csrf', 'maintenance']],

    // ========================================================
    // UTILISATEURS — Module L6
    // ========================================================
    'GET /users'                           => ['Users\\Controllers\\UserController',   'index',      ['auth', 'maintenance']],
    'GET /users/create'                    => ['Users\\Controllers\\UserController',   'create',     ['auth', 'maintenance']],
    'POST /users/store'                    => ['Users\\Controllers\\UserController',   'store',      ['auth', 'csrf', 'maintenance']],
    'GET /users/{id}'                      => ['Users\\Controllers\\UserController',   'show',       ['auth', 'maintenance']],
    'GET /users/{id}/edit'                 => ['Users\\Controllers\\UserController',   'edit',       ['auth', 'maintenance']],
    'POST /users/{id}/update'              => ['Users\\Controllers\\UserController',   'update',     ['auth', 'csrf', 'maintenance']],
    'POST /users/{id}/deactivate'          => ['Users\\Controllers\\UserController',   'deactivate', ['auth', 'csrf', 'maintenance']],
    'POST /users/{id}/reactivate'          => ['Users\\Controllers\\UserController',   'reactivate', ['auth', 'csrf', 'maintenance']],
    'GET /users/{id}/history'              => ['Users\\Controllers\\UserController',   'history',    ['auth', 'maintenance']],

    // ========================================================
    // ENTREPRISES — Module L5
    // ========================================================
    'GET /companies'                       => ['Companies\\Controllers\\CompanyController', 'index',         ['auth', 'maintenance']],
    'GET /companies/create'                => ['Companies\\Controllers\\CompanyController', 'create',        ['auth', 'maintenance']],
    'POST /companies/store'                => ['Companies\\Controllers\\CompanyController', 'store',         ['auth', 'csrf', 'maintenance']],
    'GET /companies/{id}'                  => ['Companies\\Controllers\\CompanyController', 'show',          ['auth', 'maintenance']],
    'GET /companies/{id}/edit'             => ['Companies\\Controllers\\CompanyController', 'edit',          ['auth', 'maintenance']],
    'POST /companies/{id}/update'          => ['Companies\\Controllers\\CompanyController', 'update',        ['auth', 'csrf', 'maintenance']],
    'POST /companies/{id}/delete'          => ['Companies\\Controllers\\CompanyController', 'delete',        ['auth', 'csrf', 'maintenance']],
    'POST /companies/{id}/restore'         => ['Companies\\Controllers\\CompanyController', 'restore',       ['auth', 'csrf', 'maintenance']],
    'POST /companies/relation/add'         => ['Companies\\Controllers\\CompanyController', 'addRelation',   ['auth', 'csrf', 'maintenance']],
    'POST /companies/relation/{id}/remove' => ['Companies\\Controllers\\CompanyController', 'removeRelation',['auth', 'csrf', 'maintenance']],
    'POST /companies/brand/attach'         => ['Companies\\Controllers\\CompanyController', 'attachBrand',   ['auth', 'csrf', 'maintenance']],
    'POST /companies/brand/detach'         => ['Companies\\Controllers\\CompanyController', 'detachBrand',   ['auth', 'csrf', 'maintenance']],

    // ========================================================
    // MARQUES — Module L5
    // ========================================================
    'GET /brands'                          => ['Brands\\Controllers\\BrandController', 'index',      ['auth', 'maintenance']],
    'GET /brands/create'                   => ['Brands\\Controllers\\BrandController', 'create',     ['auth', 'maintenance']],
    'POST /brands/store'                   => ['Brands\\Controllers\\BrandController', 'store',      ['auth', 'csrf', 'maintenance']],
    'GET /brands/{id}/edit'                => ['Brands\\Controllers\\BrandController', 'edit',       ['auth', 'maintenance']],
    'POST /brands/{id}/update'             => ['Brands\\Controllers\\BrandController', 'update',     ['auth', 'csrf', 'maintenance']],
    'POST /brands/{id}/deactivate'         => ['Brands\\Controllers\\BrandController', 'deactivate', ['auth', 'csrf', 'maintenance']],

    // ========================================================
    // ADMINISTRATION — Sécurité, déblocages (L3)
    // ========================================================
    'GET /admin'                           => ['Administration\\Controllers\\SecurityController', 'index',       ['auth', 'maintenance']],
    'GET /admin/security'                  => ['Administration\\Controllers\\SecurityController', 'index',       ['auth', 'maintenance']],
    'GET /admin/security/report.pdf'       => ['Administration\\Controllers\\SecurityReportController', 'pdf',   ['auth', 'maintenance']],
    'GET /admin/security/attempts'         => ['Administration\\Controllers\\SecurityController', 'attempts',    ['auth', 'maintenance']],
    'POST /admin/security/unblock-ip/{id}' => ['Administration\\Controllers\\SecurityController', 'unblockIp',   ['auth', 'csrf', 'maintenance']],
    'POST /admin/security/unblock-email/{id}' => ['Administration\\Controllers\\SecurityController', 'unblockEmail', ['auth', 'csrf', 'maintenance']],

    // ========================================================
    // MAINTENANCE — Module L2/L10 (placeholder)
    // ========================================================
    'GET /maintenance'                     => ['Maintenance\\Controllers\\MaintenanceController', 'show',   []],
    'GET /admin/maintenance'               => ['Maintenance\\Controllers\\MaintenanceController', 'admin',  ['auth', 'maintenance']],
    'POST /admin/maintenance/toggle'       => ['Maintenance\\Controllers\\MaintenanceController', 'toggle', ['auth', 'csrf']],

    // ========================================================
    // AJAX — Zone authentifiée uniquement (R45, R46)
    // CSRF vérifié dans AjaxController::__construct()
    // ========================================================

    // Contexte entreprise/marque (L4)
    'POST /ajax/context/company'           => ['Ajax\\Controllers\\ContextController',             'setCompany',           ['auth', 'ajax']],
    'POST /ajax/context/brand'             => ['Ajax\\Controllers\\ContextController',             'setBrand',             ['auth', 'ajax']],
    'GET /ajax/context/brands-for-company' => ['Ajax\\Controllers\\ContextController',             'brandsForCompany',     ['auth', 'ajax']],

    // Dashboard (L4)
    'GET /ajax/dashboard/widgets'          => ['Ajax\\Controllers\\DashboardAjaxController',       'widgets',              ['auth', 'ajax']],
    'GET /ajax/dashboard/widget/{code}'    => ['Ajax\\Controllers\\DashboardAjaxController',       'singleWidget',         ['auth', 'ajax']],

    // Notifications (L4)
    'GET /ajax/notifications/unread'       => ['Ajax\\Controllers\\NotificationsAjaxController',   'unreadCount',          ['auth', 'ajax']],
    'GET /ajax/notifications/unread-count' => ['Ajax\\Controllers\\NotificationsAjaxController',   'unreadCount',          ['auth', 'ajax']],
    'POST /ajax/notifications/{id}/read'   => ['Ajax\\Controllers\\NotificationsAjaxController',   'markRead',             ['auth', 'ajax']],
    'POST /ajax/notifications/mark-read/{id}' => ['Ajax\\Controllers\\NotificationsAjaxController','markRead',             ['auth', 'ajax']],
    'POST /ajax/notifications/mark-all-read' => ['Ajax\\Controllers\\NotificationsAjaxController', 'markAllRead',          ['auth', 'ajax']],

    // ========================================================
    // CONTACTS ET REGLES DE NOTIFICATION - Module L11
    // ========================================================
    'GET /notifications'                   => ['Notifications\\Controllers\\NotificationRuleController', 'index',         ['auth', 'maintenance']],
    'GET /notifications/rules'             => ['Notifications\\Controllers\\NotificationRuleController', 'index',         ['auth', 'maintenance']],
    'POST /notifications/contacts/store'   => ['Notifications\\Controllers\\NotificationRuleController', 'storeContact',  ['auth', 'csrf', 'maintenance']],
    'POST /notifications/rules/store'      => ['Notifications\\Controllers\\NotificationRuleController', 'storeRule',     ['auth', 'csrf', 'maintenance']],
    'POST /notifications/contacts/{id}/toggle' => ['Notifications\\Controllers\\NotificationRuleController', 'toggleContact', ['auth', 'csrf', 'maintenance']],
    'POST /notifications/rules/{id}/toggle' => ['Notifications\\Controllers\\NotificationRuleController', 'toggleRule',   ['auth', 'csrf', 'maintenance']],

    // CGU (L3)
    'POST /ajax/terms/status'              => ['Ajax\\Controllers\\TermsAjaxController',            'status',               ['auth', 'ajax']],

    // Entreprises (L5)
    'GET /ajax/companies/list'             => ['Ajax\\Controllers\\CompaniesAjaxController',        'list',                 ['auth', 'ajax']],
    'GET /ajax/companies/search'           => ['Ajax\\Controllers\\CompaniesAjaxController',        'search',               ['auth', 'ajax']],
    'GET /ajax/companies/by-type'          => ['Ajax\\Controllers\\CompaniesAjaxController',        'byType',               ['auth', 'ajax']],
    'GET /ajax/companies/{id}/brands'      => ['Ajax\\Controllers\\CompaniesAjaxController',        'brandsForCompany',     ['auth', 'ajax']],
    'GET /ajax/brands/list'                => ['Ajax\\Controllers\\CompaniesAjaxController',        'listBrands',           ['auth', 'ajax']],
    'GET /ajax/brands/search'              => ['Ajax\\Controllers\\CompaniesAjaxController',        'searchBrands',         ['auth', 'ajax']],

    // Utilisateurs (L6)
    'GET /ajax/users/search'               => ['Ajax\\Controllers\\UsersAjaxController',            'search',               ['auth', 'ajax']],
    'GET /ajax/users/{id}/companies'       => ['Ajax\\Controllers\\UsersAjaxController',            'getUserCompanies',     ['auth', 'ajax']],
    'GET /ajax/users/{id}/managers'        => ['Ajax\\Controllers\\UsersAjaxController',            'getUserManagers',      ['auth', 'ajax']],
    'GET /ajax/users/{id}/subordinates'    => ['Ajax\\Controllers\\UsersAjaxController',            'getSubordinates',      ['auth', 'ajax']],
    'GET /ajax/users/creatable-roles'      => ['Ajax\\Controllers\\UsersAjaxController',            'getCreatableRoles',    ['auth', 'ajax']],
    'GET /ajax/users/manageable-companies' => ['Ajax\\Controllers\\UsersAjaxController',            'getManageableCompanies', ['auth', 'ajax']],
    'POST /ajax/users/{id}/attach-company' => ['Ajax\\Controllers\\UsersAjaxController',            'attachCompany',        ['auth', 'ajax']],
    'POST /ajax/users/{id}/detach-company' => ['Ajax\\Controllers\\UsersAjaxController',            'detachCompany',        ['auth', 'ajax']],
    'POST /ajax/users/{id}/add-manager'    => ['Ajax\\Controllers\\UsersAjaxController',            'addManager',           ['auth', 'ajax']],
    'POST /ajax/users/{id}/remove-manager' => ['Ajax\\Controllers\\UsersAjaxController',            'removeManager',        ['auth', 'ajax']],
    'POST /ajax/users/{id}/set-primary-company' => ['Ajax\\Controllers\\UsersAjaxController',       'setPrimaryCompany',    ['auth', 'ajax']],

    // Admin sécurité (L3)
    'GET /ajax/admin/stats'                => ['Ajax\\Controllers\\AdminAjaxController',            'stats',                ['auth', 'ajax']],
    'GET /ajax/admin/login-attempts'       => ['Ajax\\Controllers\\AdminAjaxController',            'loginAttempts',        ['auth', 'ajax']],
    'GET /ajax/admin/blocked-ips'          => ['Ajax\\Controllers\\AdminAjaxController',            'blockedIps',           ['auth', 'ajax']],
    'GET /ajax/admin/blocked-emails'       => ['Ajax\\Controllers\\AdminAjaxController',            'blockedEmails',        ['auth', 'ajax']],

    // ========================================================
    // FONCTIONS — Module L7
    // ========================================================
    'GET /functions'                        => ['Functions\\Controllers\\FunctionController',  'index',   ['auth', 'maintenance']],
    'GET /functions/create'                 => ['Functions\\Controllers\\FunctionController',  'create',  ['auth', 'maintenance']],
    'POST /functions/store'                 => ['Functions\\Controllers\\FunctionController',  'store',   ['auth', 'csrf', 'maintenance']],
    'GET /functions/{id}/edit'              => ['Functions\\Controllers\\FunctionController',  'edit',    ['auth', 'maintenance']],
    'POST /functions/{id}/update'           => ['Functions\\Controllers\\FunctionController',  'update',  ['auth', 'csrf', 'maintenance']],
    'POST /functions/{id}/toggle'           => ['Functions\\Controllers\\FunctionController',  'toggle',  ['auth', 'csrf', 'maintenance']],

    // ========================================================
    // MÉTIERS — Module L7
    // ========================================================
    'GET /jobs'                             => ['Jobs\\Controllers\\JobController',            'index',   ['auth', 'maintenance']],
    'GET /jobs/create'                      => ['Jobs\\Controllers\\JobController',            'create',  ['auth', 'maintenance']],
    'POST /jobs/store'                      => ['Jobs\\Controllers\\JobController',            'store',   ['auth', 'csrf', 'maintenance']],
    'GET /jobs/{id}/edit'                   => ['Jobs\\Controllers\\JobController',            'edit',    ['auth', 'maintenance']],
    'POST /jobs/{id}/update'                => ['Jobs\\Controllers\\JobController',            'update',  ['auth', 'csrf', 'maintenance']],
    'POST /jobs/{id}/toggle'                => ['Jobs\\Controllers\\JobController',            'toggle',  ['auth', 'csrf', 'maintenance']],

    // ========================================================
    // AJAX FONCTIONS — Module L7
    // ========================================================
    'GET /ajax/functions/list'              => ['Ajax\\Controllers\\FunctionsAjaxController',  'list',           ['auth', 'ajax']],
    'GET /ajax/functions/search'            => ['Ajax\\Controllers\\FunctionsAjaxController',  'search',         ['auth', 'ajax']],
    'GET /ajax/users/{id}/functions'        => ['Ajax\\Controllers\\FunctionsAjaxController',  'forUser',        ['auth', 'ajax']],
    'POST /ajax/users/{id}/assign-function' => ['Ajax\\Controllers\\FunctionsAjaxController',  'assignToUser',   ['auth', 'ajax']],
    'POST /ajax/users/{id}/unassign-function' => ['Ajax\\Controllers\\FunctionsAjaxController','unassignFromUser',['auth', 'ajax']],
    'POST /ajax/users/{id}/sync-functions'  => ['Ajax\\Controllers\\FunctionsAjaxController',  'syncForUser',    ['auth', 'ajax']],

    // ========================================================
    // AJAX MÉTIERS — Module L7
    // ========================================================
    'GET /ajax/jobs/list'                   => ['Ajax\\Controllers\\JobsAjaxController',        'list',           ['auth', 'ajax']],
    'GET /ajax/jobs/by-company-type'        => ['Ajax\\Controllers\\JobsAjaxController',        'byCompanyType',  ['auth', 'ajax']],
    'GET /ajax/jobs/search'                 => ['Ajax\\Controllers\\JobsAjaxController',        'search',         ['auth', 'ajax']],
    'GET /ajax/users/{id}/jobs'             => ['Ajax\\Controllers\\JobsAjaxController',        'forUser',        ['auth', 'ajax']],
    'POST /ajax/users/{id}/assign-job'      => ['Ajax\\Controllers\\JobsAjaxController',        'assignToUser',   ['auth', 'ajax']],
    'POST /ajax/users/{id}/unassign-job'    => ['Ajax\\Controllers\\JobsAjaxController',        'unassignFromUser',['auth', 'ajax']],
    'POST /ajax/users/{id}/sync-jobs'       => ['Ajax\\Controllers\\JobsAjaxController',        'syncForUser',    ['auth', 'ajax']],

];
