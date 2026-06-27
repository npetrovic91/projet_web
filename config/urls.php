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
    // INVITATIONS UTILISATEURS / PREMIER ADMINISTRATEUR SOCIÉTÉ
    // ========================================================
    'GET /invitations'                       => ['Invitations\\Controllers\\InvitationController', 'index',                      ['auth', 'maintenance']],
    'GET /admin/invitations'                 => ['Invitations\\Controllers\\InvitationController', 'index',                      ['auth', 'maintenance']],
    'GET /invitations/export.json'           => ['Invitations\\Controllers\\InvitationController', 'exportJson',                 ['auth', 'maintenance']],
    'GET /invitations/create'                => ['Invitations\\Controllers\\InvitationController', 'create',                     ['auth', 'maintenance']],
    'POST /invitations/store'                => ['Invitations\\Controllers\\InvitationController', 'store',                      ['auth', 'maintenance', 'csrf']],
    'GET /invitations/{id}/edit'             => ['Invitations\\Controllers\\InvitationController', 'edit',                       ['auth', 'maintenance']],
    'POST /invitations/{id}/update'          => ['Invitations\\Controllers\\InvitationController', 'update',                     ['auth', 'maintenance', 'csrf']],
    'POST /invitations/{id}/resend'          => ['Invitations\\Controllers\\InvitationController', 'resend',                     ['auth', 'maintenance', 'csrf']],
    'POST /invitations/{id}/cancel'          => ['Invitations\\Controllers\\InvitationController', 'cancel',                     ['auth', 'maintenance', 'csrf']],
    'GET /invitations/accept/{token}'        => ['Invitations\\Controllers\\InvitationController', 'acceptForm',                 []],
    'POST /invitations/accept/{token}'       => ['Invitations\\Controllers\\InvitationController', 'accept',                     ['csrf']],
    'GET /invitations/premier-administrateur'=> ['Invitations\\Controllers\\InvitationController', 'premierAdministrateur',      ['auth', 'maintenance']],
    'POST /invitations/premier-administrateur/store'=> ['Invitations\\Controllers\\InvitationController', 'creerPremierAdministrateur', ['auth', 'maintenance', 'csrf']],


    // ========================================================
    // CONTACTS SOCIETES / CONTACTS EXTERNES / TYPES DE CONTACTS
    // ========================================================
    'GET /contacts-societes'                  => ['Contacts\\Controllers\\ContactSocieteController', 'index',      ['auth', 'maintenance']],
    'GET /contacts-societes/export.json'      => ['Contacts\\Controllers\\ContactSocieteController', 'exportJson', ['auth', 'maintenance']],
    'GET /contacts-societes/create'           => ['Contacts\\Controllers\\ContactSocieteController', 'create',     ['auth', 'maintenance']],
    'POST /contacts-societes/store'           => ['Contacts\\Controllers\\ContactSocieteController', 'store',      ['auth', 'maintenance', 'csrf']],
    'GET /contacts-societes/{id}/edit'        => ['Contacts\\Controllers\\ContactSocieteController', 'edit',       ['auth', 'maintenance']],
    'POST /contacts-societes/{id}/update'     => ['Contacts\\Controllers\\ContactSocieteController', 'update',     ['auth', 'maintenance', 'csrf']],
    'POST /contacts-societes/{id}/delete'     => ['Contacts\\Controllers\\ContactSocieteController', 'delete',     ['auth', 'maintenance', 'csrf']],
    'GET /contacts-societes/types'            => ['Contacts\\Controllers\\ContactSocieteController', 'types',      ['auth', 'maintenance']],
    'GET /contacts-societes/types/create'     => ['Contacts\\Controllers\\ContactSocieteController', 'createType', ['auth', 'maintenance']],
    'POST /contacts-societes/types/store'     => ['Contacts\\Controllers\\ContactSocieteController', 'storeType',  ['auth', 'maintenance', 'csrf']],
    'GET /contacts-societes/types/{id}/edit'  => ['Contacts\\Controllers\\ContactSocieteController', 'editType',   ['auth', 'maintenance']],
    'POST /contacts-societes/types/{id}/update'=> ['Contacts\\Controllers\\ContactSocieteController', 'updateType', ['auth', 'maintenance', 'csrf']],
    'POST /contacts-societes/types/{id}/delete'=> ['Contacts\\Controllers\\ContactSocieteController', 'deleteType', ['auth', 'maintenance', 'csrf']],


    // ========================================================
    // ROLES / PERMISSIONS — Noyau RBAC + ABAC
    // ========================================================
    'GET /roles'                           => ['Roles\\Controllers\\RoleController',             'index',           ['auth', 'maintenance']],
    'GET /roles/create'                    => ['Roles\\Controllers\\RoleController',             'create',          ['auth', 'maintenance']],
    'POST /roles/store'                    => ['Roles\\Controllers\\RoleController',             'store',           ['auth', 'maintenance', 'csrf']],
    'GET /roles/propagation'               => ['Roles\\Controllers\\GroupPropagationController', 'form',            ['auth', 'maintenance']],
    'POST /roles/propagation/previsualiser'=> ['Roles\\Controllers\\GroupPropagationController', 'previsualiser',   ['auth', 'maintenance', 'csrf']],
    'POST /roles/propagation/{id}/confirmer'=> ['Roles\\Controllers\\GroupPropagationController', 'confirmer',      ['auth', 'maintenance', 'csrf']],
    'POST /roles/propagation/{id}/annuler' => ['Roles\\Controllers\\GroupPropagationController', 'annuler',         ['auth', 'maintenance', 'csrf']],
    'GET /roles/{id}'                      => ['Roles\\Controllers\\RoleController',             'show',            ['auth', 'maintenance']],
    'GET /roles/{id}/edit'                 => ['Roles\\Controllers\\RoleController',             'edit',            ['auth', 'maintenance']],
    'POST /roles/{id}/update'              => ['Roles\\Controllers\\RoleController',             'update',          ['auth', 'maintenance', 'csrf']],
    'POST /roles/{id}/delete'              => ['Roles\\Controllers\\RoleController',             'delete',          ['auth', 'maintenance', 'csrf']],
    'POST /roles/{id}/permissions/sync'    => ['Roles\\Controllers\\RoleController',             'syncPermissions', ['auth', 'maintenance', 'csrf']],

    'GET /permissions'                     => ['Roles\\Controllers\\PermissionController',       'index',           ['auth', 'maintenance']],
    'GET /permissions/create'              => ['Roles\\Controllers\\PermissionController',       'create',          ['auth', 'maintenance']],
    'POST /permissions/store'              => ['Roles\\Controllers\\PermissionController',       'store',           ['auth', 'maintenance', 'csrf']],
    'GET /permissions/{id}/edit'           => ['Roles\\Controllers\\PermissionController',       'edit',            ['auth', 'maintenance']],
    'POST /permissions/{id}/update'        => ['Roles\\Controllers\\PermissionController',       'update',          ['auth', 'maintenance', 'csrf']],
    'POST /permissions/{id}/delete'        => ['Roles\\Controllers\\PermissionController',       'delete',          ['auth', 'maintenance', 'csrf']],

    'GET /access-policies'                 => ['Roles\\Controllers\\AccessPolicyController',     'index',           ['auth', 'maintenance']],
    'GET /access-policies/{id}'            => ['Roles\\Controllers\\AccessPolicyController',     'show',            ['auth', 'maintenance']],

    // Alias administration historiques
    'GET /admin/roles'                     => ['Roles\\Controllers\\RoleController',             'index',           ['auth', 'maintenance']],
    'GET /admin/permissions'               => ['Roles\\Controllers\\PermissionController',       'index',           ['auth', 'maintenance']],
    'GET /admin/access-policies'           => ['Roles\\Controllers\\AccessPolicyController',     'index',           ['auth', 'maintenance']],
    
    //=========================================================
    // EMAILS / MODELES EMAIL / BULK MAIL
    //=========================================================
    'GET /emails'                           => ['Emails\\Controllers\\EmailsController',          'index',          ['auth']],
    'GET /emails/templates'                 => ['Emails\\Controllers\\EmailsController',          'templates',      ['auth']],
    'GET /emails/templates/create'          => ['Emails\\Controllers\\EmailsController',          'createTemplate', ['auth']],
    'POST /emails/templates/store'          => ['Emails\\Controllers\\EmailsController',          'storeTemplate',  ['auth', 'csrf']],
    'GET /emails/templates/{id}/edit'       => ['Emails\\Controllers\\EmailsController',          'editTemplate',   ['auth']],
    'POST /emails/templates/{id}/update'    => ['Emails\\Controllers\\EmailsController',          'updateTemplate', ['auth', 'csrf']],
    'POST /emails/templates/{id}/delete'    => ['Emails\\Controllers\\EmailsController',          'deleteTemplate', ['auth', 'csrf']],
    'GET /emails/logs'                      => ['Emails\\Controllers\\EmailsController',          'logs',           ['auth']],

    'GET /bulk-mail'                        => ['BulkMail\\Controllers\\BulkMailController',      'index',          ['auth']],
    'GET /bulk-mail/create'                 => ['BulkMail\\Controllers\\BulkMailController',      'create',         ['auth']],
    'POST /bulk-mail/store'                 => ['BulkMail\\Controllers\\BulkMailController',      'store',          ['auth', 'csrf']],
    'GET /bulk-mail/{id}'                   => ['BulkMail\\Controllers\\BulkMailController',      'show',           ['auth']],
    'POST /bulk-mail/{id}/send-batch'       => ['BulkMail\\Controllers\\BulkMailController',      'sendBatch',      ['auth', 'csrf']],
    'POST /bulk-mail/{id}/refresh'          => ['BulkMail\\Controllers\\BulkMailController',      'refresh',        ['auth', 'csrf']],
    'POST /bulk-mail/{id}/cancel'           => ['BulkMail\\Controllers\\BulkMailController',      'cancel',         ['auth', 'csrf']],
    'GET /email/unsubscribe'                => ['BulkMail\\Controllers\\BulkMailController',      'unsubscribe',    []],


    // ========================================================
    // FICHIERS / DOCUMENTS JURIDIQUES / ACCEPTATIONS
    // ========================================================
    'GET /files'                            => ['Files\\Controllers\\FileController',                 'index',     ['auth', 'maintenance']],
    'GET /files/create'                     => ['Files\\Controllers\\FileController',                 'create',    ['auth', 'maintenance']],
    'POST /files/store'                     => ['Files\\Controllers\\FileController',                 'store',     ['auth', 'maintenance', 'csrf']],
    'GET /files/{id}'                       => ['Files\\Controllers\\FileController',                 'show',      ['auth', 'maintenance']],
    'GET /files/{id}/download'              => ['Files\\Controllers\\FileController',                 'download',  ['auth', 'maintenance']],
    'POST /files/{id}/link'                 => ['Files\\Controllers\\FileController',                 'link',      ['auth', 'maintenance', 'csrf']],
    'POST /files/{id}/delete'               => ['Files\\Controllers\\FileController',                 'delete',    ['auth', 'maintenance', 'csrf']],

    'GET /legal-documents'                  => ['LegalDocuments\\Controllers\\LegalDocumentController','index',     ['auth', 'maintenance']],
    'GET /legal-documents/create'           => ['LegalDocuments\\Controllers\\LegalDocumentController','create',    ['auth', 'maintenance']],
    'POST /legal-documents/store'           => ['LegalDocuments\\Controllers\\LegalDocumentController','store',     ['auth', 'maintenance', 'csrf']],
    'GET /legal-documents/{id}'             => ['LegalDocuments\\Controllers\\LegalDocumentController','show',      ['auth', 'maintenance']],
    'GET /legal-documents/{id}/edit'        => ['LegalDocuments\\Controllers\\LegalDocumentController','edit',      ['auth', 'maintenance']],
    'POST /legal-documents/{id}/update'     => ['LegalDocuments\\Controllers\\LegalDocumentController','update',    ['auth', 'maintenance', 'csrf']],
    'POST /legal-documents/{id}/delete'     => ['LegalDocuments\\Controllers\\LegalDocumentController','delete',    ['auth', 'maintenance', 'csrf']],
    'POST /legal-documents/{id}/link-company'=> ['LegalDocuments\\Controllers\\LegalDocumentController','linkCompany',['auth', 'maintenance', 'csrf']],
    'POST /legal-documents/{id}/accept'     => ['LegalDocuments\\Controllers\\LegalDocumentController','accept',    ['auth', 'maintenance', 'csrf']],


    // ========================================================
    // NOTES / ETIQUETTES — Affectations generiques (Lot 17)
    // ========================================================
    'GET /notes'                           => ['Notes\Controllers\NotesController', 'index',        ['auth', 'maintenance']],
    'GET /notes/export.json'               => ['Notes\Controllers\NotesController', 'exportJson',   ['auth', 'maintenance']],
    'GET /notes/create'                    => ['Notes\Controllers\NotesController', 'create',       ['auth', 'maintenance']],
    'POST /notes/store'                    => ['Notes\Controllers\NotesController', 'store',        ['auth', 'maintenance', 'csrf']],
    'GET /notes/{id}'                      => ['Notes\Controllers\NotesController', 'show',         ['auth', 'maintenance']],
    'GET /notes/{id}/edit'                 => ['Notes\Controllers\NotesController', 'edit',         ['auth', 'maintenance']],
    'POST /notes/{id}/update'              => ['Notes\Controllers\NotesController', 'update',       ['auth', 'maintenance', 'csrf']],
    'POST /notes/{id}/delete'              => ['Notes\Controllers\NotesController', 'delete',       ['auth', 'maintenance', 'csrf']],

    'GET /labels'                          => ['Notes\Controllers\NotesController', 'labels',       ['auth', 'maintenance']],
    'GET /labels/create'                   => ['Notes\Controllers\NotesController', 'createLabel',  ['auth', 'maintenance']],
    'POST /labels/store'                   => ['Notes\Controllers\NotesController', 'storeLabel',   ['auth', 'maintenance', 'csrf']],
    'GET /labels/{id}/edit'                => ['Notes\Controllers\NotesController', 'editLabel',    ['auth', 'maintenance']],
    'POST /labels/{id}/update'             => ['Notes\Controllers\NotesController', 'updateLabel',  ['auth', 'maintenance', 'csrf']],
    'POST /labels/{id}/delete'             => ['Notes\Controllers\NotesController', 'deleteLabel',  ['auth', 'maintenance', 'csrf']],

    'GET /label-assignments'               => ['Notes\Controllers\NotesController', 'assignments',  ['auth', 'maintenance']],
    'POST /label-assignments/attach'       => ['Notes\Controllers\NotesController', 'attach',       ['auth', 'maintenance', 'csrf']],
    'POST /label-assignments/{id}/detach'  => ['Notes\Controllers\NotesController', 'detach',       ['auth', 'maintenance', 'csrf']],
    'GET /labels/target.json'              => ['Notes\Controllers\NotesController', 'targetLabels', ['auth', 'maintenance']],

    // ========================================================
    // PROFIL UTILISATEUR
    // ========================================================
    'GET /profile'                         => ['Profile\\Controllers\\ProfileController',  'show',           ['auth', 'maintenance']],
    'POST /profile/update'                 => ['Profile\\Controllers\\ProfileController',  'update',         ['auth', 'maintenance', 'csrf']],
    'POST /profile/password'               => ['Profile\\Controllers\\ProfileController',  'changePassword', ['auth', 'maintenance', 'csrf']],
    'GET /profile/gdpr'                    => ['Profile\\Controllers\\ProfileController',  'gdpr',           ['auth', 'maintenance']],
    'POST /profile/gdpr/request'           => ['Profile\\Controllers\\ProfileController',  'gdprRequest',    ['auth', 'maintenance', 'csrf']],
    'GET /profile/gdpr/export'             => ['Profile\\Controllers\\ProfileController',  'gdprExport',     ['auth', 'maintenance']],

    // ========================================================
    // RGPD ADMINISTRATION - Module L9
    // ========================================================
    'GET /gdpr'                            => ['GDPR\\Controllers\\GdprController', 'index',     ['auth', 'maintenance']],
    'GET /admin/gdpr'                      => ['GDPR\\Controllers\\GdprController', 'index',     ['auth', 'maintenance']],
    'GET /gdpr/{id}'                       => ['GDPR\\Controllers\\GdprController', 'show',      ['auth', 'maintenance']],
    'GET /admin/gdpr/{id}'                 => ['GDPR\\Controllers\\GdprController', 'show',      ['auth', 'maintenance']],
    'POST /gdpr/{id}/accept'               => ['GDPR\\Controllers\\GdprController', 'accept',    ['auth', 'maintenance', 'csrf']],
    'POST /gdpr/{id}/reject'               => ['GDPR\\Controllers\\GdprController', 'reject',    ['auth', 'maintenance', 'csrf']],
    'GET /gdpr/{id}/export'                => ['GDPR\\Controllers\\GdprController', 'export',    ['auth', 'maintenance']],
    'POST /gdpr/{id}/anonymize'            => ['GDPR\\Controllers\\GdprController', 'anonymize', ['auth', 'maintenance', 'csrf']],

    // ========================================================
    // UTILISATEURS — Module L6
    // ========================================================
    'GET /users'                           => ['Users\\Controllers\\UserController',   'index',      ['auth', 'maintenance']],
    'GET /users/create'                    => ['Users\\Controllers\\UserController',   'create',     ['auth', 'maintenance']],
    'POST /users/store' => ['Users\\Controllers\\UserController', 'store', ['auth', 'maintenance', 'csrf']],
    'GET /users/{id}'                      => ['Users\\Controllers\\UserController',   'show',       ['auth', 'maintenance']],
    'GET /users/{id}/edit'                 => ['Users\\Controllers\\UserController',   'edit',       ['auth', 'maintenance']],
    'POST /users/{id}/update'              => ['Users\\Controllers\\UserController',   'update',     ['auth', 'maintenance', 'csrf']],
    'POST /users/{id}/deactivate'          => ['Users\\Controllers\\UserController',   'deactivate', ['auth', 'maintenance', 'csrf']],
    'POST /users/{id}/reactivate'          => ['Users\\Controllers\\UserController',   'reactivate', ['auth', 'maintenance', 'csrf']],
'POST /users/{id}/inventory/add'        => ['Users\\Controllers\\UserController', 'inventoryAdd',        ['auth', 'csrf']],
    'POST /users/{id}/inventory/{iid}/return'=> ['Users\\Controllers\\UserController', 'inventoryReturn',     ['auth', 'csrf']],
    'POST /users/{id}/leave-balance/update'  => ['Users\\Controllers\\UserController', 'leaveBalanceUpdate',  ['auth', 'csrf']],
    'POST /users/{id}/leave-requests/add'    => ['Users\\Controllers\\UserController', 'leaveRequestAdd',     ['auth', 'csrf']],
    'POST /users/{id}/absences/add'          => ['Users\\Controllers\\UserController', 'absenceAdd',          ['auth', 'csrf']],
    'POST /users/{id}/warnings/add'          => ['Users\\Controllers\\UserController', 'warningAdd',          ['auth', 'csrf']],
    'GET /users/{id}/history'              => ['Users\\Controllers\\UserController',   'history',    ['auth', 'maintenance']],

    // ========================================================
    // ENTREPRISES — Module L5
    // ========================================================
    'GET /companies'                       => ['Companies\\Controllers\\CompanyController', 'index',         ['auth', 'maintenance']],
    'GET /companies/create'                => ['Companies\\Controllers\\CompanyController', 'create',        ['auth', 'maintenance']],
    'POST /companies/store'                => ['Companies\\Controllers\\CompanyController', 'store',         ['auth', 'maintenance', 'csrf']],
    'GET /companies/types'                 => ['Companies\\Controllers\\CompanyController', 'types',         ['auth', 'maintenance']],
    'GET /companies/types/create'          => ['Companies\\Controllers\\CompanyController', 'createType',    ['auth', 'maintenance']],
    'POST /companies/types/store'          => ['Companies\\Controllers\\CompanyController', 'storeType',     ['auth', 'maintenance', 'csrf']],
    'GET /companies/types/{id}/edit'       => ['Companies\\Controllers\\CompanyController', 'editType',      ['auth', 'maintenance']],
    'POST /companies/types/{id}/update'    => ['Companies\\Controllers\\CompanyController', 'updateType',    ['auth', 'maintenance', 'csrf']],
    'POST /companies/types/{id}/delete'    => ['Companies\\Controllers\\CompanyController', 'deleteType',    ['auth', 'maintenance', 'csrf']],
    'GET /companies/{id}'                  => ['Companies\\Controllers\\CompanyController', 'show',          ['auth', 'maintenance']],
    'GET /companies/{id}/edit'             => ['Companies\\Controllers\\CompanyController', 'edit',          ['auth', 'maintenance']],
    'POST /companies/{id}/update'          => ['Companies\\Controllers\\CompanyController', 'update',        ['auth', 'maintenance', 'csrf']],
    'POST /companies/{id}/delete'          => ['Companies\\Controllers\\CompanyController', 'delete',        ['auth', 'maintenance', 'csrf']],
    'POST /companies/{id}/restore'         => ['Companies\\Controllers\\CompanyController', 'restore',       ['auth', 'maintenance', 'csrf']],
    'POST /companies/relation/add'         => ['Companies\\Controllers\\CompanyController', 'addRelation',   ['auth', 'maintenance', 'csrf']],
    'POST /companies/relation/{id}/remove' => ['Companies\\Controllers\\CompanyController', 'removeRelation',['auth', 'maintenance', 'csrf']],
    'POST /companies/brand/attach'         => ['Companies\\Controllers\\CompanyController', 'attachBrand',   ['auth', 'maintenance', 'csrf']],
    'POST /companies/brand/detach'         => ['Companies\\Controllers\\CompanyController', 'detachBrand',   ['auth', 'maintenance', 'csrf']],

    // LOT40 — Extensions Société : infos complémentaires, comptes bancaires, mandats SEPA, parc véhicules
    'POST /companies/{id}/infos-complementaires'          => ['Companies\\Controllers\\CompanyController', 'saveInfosCompl',    ['auth', 'maintenance', 'csrf']],
    'POST /companies/{id}/comptes-bancaires'              => ['Companies\\Controllers\\CompanyController', 'addCompteBancaire', ['auth', 'maintenance', 'csrf']],
    'POST /companies/{id}/comptes-bancaires/{cid}/delete' => ['Companies\\Controllers\\CompanyController', 'deleteCompteBancaire', ['auth', 'maintenance', 'csrf']],
    'POST /companies/{id}/mandats'                        => ['Companies\\Controllers\\CompanyController', 'addMandat',         ['auth', 'maintenance', 'csrf']],
    'POST /companies/{id}/mandats/{mid}/update'           => ['Companies\\Controllers\\CompanyController', 'updateMandat',      ['auth', 'maintenance', 'csrf']],
    'POST /companies/{id}/mandats/{mid}/delete'           => ['Companies\\Controllers\\CompanyController', 'deleteMandat',      ['auth', 'maintenance', 'csrf']],
    'POST /companies/{id}/vehicules'                      => ['Companies\\Controllers\\CompanyController', 'addVehicule',       ['auth', 'maintenance', 'csrf']],
    'POST /companies/{id}/vehicules/{vid}/delete'         => ['Companies\\Controllers\\CompanyController', 'deleteVehicule',    ['auth', 'maintenance', 'csrf']],

    // LOT40 — Extensions Utilisateur : mêmes sections côté compte individuel
    'POST /users/{id}/infos-complementaires'              => ['Users\\Controllers\\UserController', 'saveInfosCompl',    ['auth', 'maintenance', 'csrf']],
    'POST /users/{id}/comptes-bancaires'                  => ['Users\\Controllers\\UserController', 'addCompteBancaire', ['auth', 'maintenance', 'csrf']],
    'POST /users/{id}/comptes-bancaires/{cid}/delete'     => ['Users\\Controllers\\UserController', 'deleteCompteBancaire', ['auth', 'maintenance', 'csrf']],
    'POST /users/{id}/mandats'                            => ['Users\\Controllers\\UserController', 'addMandat',         ['auth', 'maintenance', 'csrf']],
    'POST /users/{id}/mandats/{mid}/delete'               => ['Users\\Controllers\\UserController', 'deleteMandat',      ['auth', 'maintenance', 'csrf']],
    'POST /users/{id}/vehicules'                          => ['Users\\Controllers\\UserController', 'addVehicule',       ['auth', 'maintenance', 'csrf']],
    'POST /users/{id}/vehicules/{vid}/delete'             => ['Users\\Controllers\\UserController', 'deleteVehicule',    ['auth', 'maintenance', 'csrf']],

    // ========================================================
    // MARQUES — Module L5
    // ========================================================
    'GET /brands'                          => ['Brands\\Controllers\\BrandController', 'index',      ['auth', 'maintenance']],
    'GET /brands/create'                   => ['Brands\\Controllers\\BrandController', 'create',     ['auth', 'maintenance']],
    'POST /brands/store'                   => ['Brands\\Controllers\\BrandController', 'store',      ['auth', 'maintenance', 'csrf']],
    'GET /brands/{id}/edit'                => ['Brands\\Controllers\\BrandController', 'edit',       ['auth', 'maintenance']],
    'POST /brands/{id}/update'             => ['Brands\\Controllers\\BrandController', 'update',     ['auth', 'maintenance', 'csrf']],
    'POST /brands/{id}/deactivate'         => ['Brands\\Controllers\\BrandController', 'deactivate', ['auth', 'maintenance', 'csrf']],

    // ========================================================
    // ADMINISTRATION — Sécurité, déblocages (L3)
    // ========================================================
    'GET /admin'                           => ['Administration\\Controllers\\SecurityController', 'index',       ['auth', 'maintenance']],
    'GET /admin/security'                  => ['Administration\\Controllers\\SecurityController', 'index',       ['auth', 'maintenance']],
    'GET /admin/security/report.pdf'       => ['Administration\\Controllers\\SecurityReportController', 'pdf',   ['auth', 'maintenance']],
    'GET /admin/security/attempts'         => ['Administration\\Controllers\\SecurityController', 'attempts',    ['auth', 'maintenance']],
    'POST /admin/security/unblock-ip/{id}' => ['Administration\\Controllers\\SecurityController', 'unblockIp',   ['auth', 'maintenance', 'csrf']],
    'POST /admin/security/unblock-email/{id}' => ['Administration\\Controllers\\SecurityController', 'unblockEmail', ['auth', 'maintenance', 'csrf']],


    // ========================================================
    // SETTINGS — Paramètres application / Configuration système (Lot 12)
    // ========================================================
    'GET /settings'                        => ['Settings\Controllers\SettingsController', 'index',             ['auth', 'maintenance']],
    'GET /admin/settings'                  => ['Settings\Controllers\SettingsController', 'index',             ['auth', 'maintenance']],
    'GET /settings/system'                 => ['Settings\Controllers\SettingsController', 'system',            ['auth', 'maintenance']],
    'GET /settings/export.json'            => ['Settings\Controllers\SettingsController', 'exportJson',        ['auth', 'maintenance']],
    'GET /settings/create'                 => ['Settings\Controllers\SettingsController', 'create',            ['auth', 'maintenance']],
    'POST /settings/store'                 => ['Settings\Controllers\SettingsController', 'store',             ['auth', 'maintenance', 'csrf']],
    'GET /settings/{id}/edit'              => ['Settings\Controllers\SettingsController', 'edit',              ['auth', 'maintenance']],
    'POST /settings/{id}/update'           => ['Settings\Controllers\SettingsController', 'update',            ['auth', 'maintenance', 'csrf']],
    'POST /settings/{id}/delete'           => ['Settings\Controllers\SettingsController', 'delete',            ['auth', 'maintenance', 'csrf']],
    'POST /settings/maintenance'           => ['Settings\Controllers\SettingsController', 'updateMaintenance', ['auth', 'csrf']],

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

    'GET /admin/qualifications' => ['Qualifications\\Controllers\\QualificationController', 'index', ['auth','maintenance']],
    'GET /admin/skills'         => ['Skills\\Controllers\\SkillController', 'index', ['auth','maintenance']],
    'GET /ajax/skills/list'            => ['Ajax\\Controllers\\SkillsAjaxController', 'list',   ['auth','ajax']],
    'GET /ajax/skills/search'          => ['Ajax\\Controllers\\SkillsAjaxController', 'search', ['auth','ajax']],
    'GET /ajax/qualifications/list'  => ['Ajax\\Controllers\\QualificationsAjaxController', 'list', ['auth','ajax']],
    'GET /ajax/roles/creatable'        => ['Ajax\\Controllers\\RolesAjaxController', 'creatable', ['auth','ajax']],
    'POST /admin/qualifications/store' => ['Qualifications\\Controllers\\QualificationController','store',['auth','maintenance', 'csrf']],
    'POST /admin/skills/store'    => ['Skills\\Controllers\\SkillController','store',['auth','maintenance', 'csrf']],
    'GET /admin/qualifications/{id}/edit'    => ['Qualifications\\Controllers\\QualificationController','edit',['auth','maintenance']],
    'POST /admin/qualifications/{id}/update'  => ['Qualifications\\Controllers\\QualificationController','update',['auth','maintenance', 'csrf']],
    'POST /admin/qualifications/{id}/toggle'  => ['Qualifications\\Controllers\\QualificationController','toggle',['auth','maintenance', 'csrf']],
    'GET /admin/skills/{id}/edit'             => ['Skills\\Controllers\\SkillController','edit',['auth','maintenance']],
    'POST /admin/skills/{id}/update'          => ['Skills\\Controllers\\SkillController','update',['auth','maintenance', 'csrf']],
    'POST /admin/skills/{id}/toggle'          => ['Skills\\Controllers\\SkillController','toggle',['auth','maintenance', 'csrf']],
    'GET /ajax/qualifications/search'         => ['Ajax\\Controllers\\QualificationsAjaxController','search',['auth','ajax']],
    // Contexte entreprise/marque (L4)
    'POST /ajax/context/company'           => ['Ajax\\Controllers\\ContextController',             'setCompany',           ['auth', 'ajax', 'csrf']],
    'POST /ajax/context/brand'             => ['Ajax\\Controllers\\ContextController',             'setBrand',             ['auth', 'ajax', 'csrf']],
    'GET /ajax/context/brands-for-company' => ['Ajax\\Controllers\\ContextController',             'brandsForCompany',     ['auth', 'ajax']],

    // Dashboard (L4)
    'GET /ajax/dashboard/widgets'          => ['Ajax\\Controllers\\DashboardAjaxController',       'widgets',              ['auth', 'ajax']],
    'GET /ajax/dashboard/widget/{code}'    => ['Ajax\\Controllers\\DashboardAjaxController',       'singleWidget',         ['auth', 'ajax']],

    // Notifications (L4)
    'GET /ajax/notifications/unread'       => ['Ajax\\Controllers\\NotificationsAjaxController',   'unreadCount',          ['auth', 'ajax']],
    'GET /ajax/notifications/unread-count' => ['Ajax\\Controllers\\NotificationsAjaxController',   'unreadCount',          ['auth', 'ajax']],
    'POST /ajax/notifications/{id}/read'   => ['Ajax\\Controllers\\NotificationsAjaxController',   'markRead',             ['auth', 'ajax', 'csrf']],
    'POST /ajax/notifications/mark-read/{id}' => ['Ajax\\Controllers\\NotificationsAjaxController','markRead',             ['auth', 'ajax', 'csrf']],
    'POST /ajax/notifications/mark-all-read' => ['Ajax\\Controllers\\NotificationsAjaxController', 'markAllRead',          ['auth', 'ajax', 'csrf']],

    // ========================================================
    // EVENEMENTS APPLICATIFS / EVENT TRIGGERS - Module L9
    // ========================================================
    'GET /event-triggers'                  => ['EventTriggers\\Controllers\\EventTriggerController', 'index', ['auth', 'maintenance']],
    'GET /event-triggers/subscriptions'    => ['EventTriggers\\Controllers\\EventTriggerController', 'index', ['auth', 'maintenance']],

    // ========================================================
    // CONTACTS ET REGLES DE NOTIFICATION - Module L11
    // ========================================================
    'GET /notifications'                   => ['Notifications\\Controllers\\NotificationRuleController', 'index',         ['auth', 'maintenance']],
    'GET /notifications/rules'             => ['Notifications\\Controllers\\NotificationRuleController', 'index',         ['auth', 'maintenance']],
    'POST /notifications/contacts/store'   => ['Notifications\\Controllers\\NotificationRuleController', 'storeContact',  ['auth', 'maintenance', 'csrf']],
    'POST /notifications/rules/store'      => ['Notifications\\Controllers\\NotificationRuleController', 'storeRule',     ['auth', 'maintenance', 'csrf']],
    'POST /notifications/contacts/{id}/toggle' => ['Notifications\\Controllers\\NotificationRuleController', 'toggleContact', ['auth', 'maintenance', 'csrf']],
    'POST /notifications/rules/{id}/toggle' => ['Notifications\\Controllers\\NotificationRuleController', 'toggleRule',   ['auth', 'maintenance', 'csrf']],

    // CGU (L3)
    'POST /ajax/terms/status'              => ['Ajax\\Controllers\\TermsAjaxController',            'status',               ['auth', 'ajax', 'csrf']],

    // Entreprises (L5)
    'GET /ajax/companies/list'             => ['Ajax\\Controllers\\CompaniesAjaxController',        'list',                 ['auth', 'ajax']],
    'GET /ajax/companies/search'           => ['Ajax\\Controllers\\CompaniesAjaxController',        'search',               ['auth', 'ajax']],
    'GET /ajax/companies/by-type'          => ['Ajax\\Controllers\\CompaniesAjaxController',        'byType',               ['auth', 'ajax']],
    'GET /ajax/companies/{id}/brands'      => ['Ajax\\Controllers\\CompaniesAjaxController',        'brandsForCompany',     ['auth', 'ajax']],
    'GET /ajax/brands/list'                => ['Ajax\\Controllers\\CompaniesAjaxController',        'listBrands',           ['auth', 'ajax']],
    'GET /ajax/brands/search'              => ['Ajax\\Controllers\\CompaniesAjaxController',        'searchBrands',         ['auth', 'ajax']],

    // ── Utilisateurs ─────────────────────────────────────────────────────
    'POST /users/{id}/resend-verification'    => ['Users\\Controllers\\UserController', 'resendVerification', ['auth', 'maintenance', 'csrf']],
    'GET /ajax/users/search'               => ['Ajax\\Controllers\\UsersAjaxController',            'search',               ['auth', 'ajax']],
    'GET /ajax/users/{id}/companies'       => ['Ajax\\Controllers\\UsersAjaxController',            'getUserCompanies',     ['auth', 'ajax']],
    'GET /ajax/users/{id}/managers'        => ['Ajax\\Controllers\\UsersAjaxController',            'getUserManagers',      ['auth', 'ajax']],
    'GET /ajax/users/{id}/subordinates'    => ['Ajax\\Controllers\\UsersAjaxController',            'getSubordinates',      ['auth', 'ajax']],
    'GET /ajax/users/creatable-roles'      => ['Ajax\\Controllers\\UsersAjaxController',            'getCreatableRoles',    ['auth', 'ajax']],
    'GET /ajax/users/manageable-companies' => ['Ajax\\Controllers\\UsersAjaxController',            'getManageableCompanies', ['auth', 'ajax']],
    'POST /ajax/users/{id}/attach-company' => ['Ajax\\Controllers\\UsersAjaxController',            'attachCompany',        ['auth', 'ajax', 'csrf']],
    'POST /ajax/users/{id}/detach-company' => ['Ajax\\Controllers\\UsersAjaxController',            'detachCompany',        ['auth', 'ajax', 'csrf']],
    'POST /ajax/users/{id}/add-manager'    => ['Ajax\\Controllers\\UsersAjaxController',            'addManager',           ['auth', 'ajax', 'csrf']],
    'POST /ajax/users/{id}/remove-manager' => ['Ajax\\Controllers\\UsersAjaxController',            'removeManager',        ['auth', 'ajax', 'csrf']],
    'POST /ajax/users/{id}/set-primary-company' => ['Ajax\\Controllers\\UsersAjaxController',       'setPrimaryCompany',    ['auth', 'ajax', 'csrf']],

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
    'POST /functions/store'                 => ['Functions\\Controllers\\FunctionController',  'store',   ['auth', 'maintenance', 'csrf']],
    'GET /functions/{id}/edit'              => ['Functions\\Controllers\\FunctionController',  'edit',    ['auth', 'maintenance']],
    'POST /functions/{id}/update'           => ['Functions\\Controllers\\FunctionController',  'update',  ['auth', 'maintenance', 'csrf']],
    'POST /functions/{id}/toggle'           => ['Functions\\Controllers\\FunctionController',  'toggle',  ['auth', 'maintenance', 'csrf']],

    // ========================================================
    // MÉTIERS — Module L7
    // ========================================================
    'GET /jobs'                             => ['Jobs\\Controllers\\JobController',            'index',   ['auth', 'maintenance']],
    'GET /jobs/create'                      => ['Jobs\\Controllers\\JobController',            'create',  ['auth', 'maintenance']],
    'POST /jobs/store'                      => ['Jobs\\Controllers\\JobController',            'store',   ['auth', 'maintenance', 'csrf']],
    'GET /jobs/{id}/edit'                   => ['Jobs\\Controllers\\JobController',            'edit',    ['auth', 'maintenance']],
    'POST /jobs/{id}/update'                => ['Jobs\\Controllers\\JobController',            'update',  ['auth', 'maintenance', 'csrf']],
    'POST /jobs/{id}/toggle'                => ['Jobs\\Controllers\\JobController',            'toggle',  ['auth', 'maintenance', 'csrf']],

    // ========================================================
    // AJAX FONCTIONS — Module L7
    // ========================================================
    'GET /ajax/functions/list'              => ['Ajax\\Controllers\\FunctionsAjaxController',  'list',           ['auth', 'ajax']],
    'GET /ajax/functions/search'            => ['Ajax\\Controllers\\FunctionsAjaxController',  'search',         ['auth', 'ajax']],
    'GET /ajax/users/{id}/functions'        => ['Ajax\\Controllers\\FunctionsAjaxController',  'forUser',        ['auth', 'ajax']],
    'POST /ajax/users/{id}/assign-function' => ['Ajax\\Controllers\\FunctionsAjaxController',  'assignToUser',   ['auth', 'ajax', 'csrf']],
    'POST /ajax/users/{id}/unassign-function' => ['Ajax\\Controllers\\FunctionsAjaxController','unassignFromUser',['auth', 'ajax', 'csrf']],
    'POST /ajax/users/{id}/sync-functions'  => ['Ajax\\Controllers\\FunctionsAjaxController',  'syncForUser',    ['auth', 'ajax', 'csrf']],

    // ========================================================
    // AJAX MÉTIERS — Module L7
    // ========================================================
    'GET /ajax/jobs/list'                   => ['Ajax\\Controllers\\JobsAjaxController',        'list',           ['auth', 'ajax']],
    'GET /ajax/jobs/by-company-type'        => ['Ajax\\Controllers\\JobsAjaxController',        'byCompanyType',  ['auth', 'ajax']],
    'GET /ajax/jobs/search'                 => ['Ajax\\Controllers\\JobsAjaxController',        'search',         ['auth', 'ajax']],
    'GET /ajax/users/{id}/jobs'             => ['Ajax\\Controllers\\JobsAjaxController',        'forUser',        ['auth', 'ajax']],
    'POST /ajax/users/{id}/assign-job'      => ['Ajax\\Controllers\\JobsAjaxController',        'assignToUser',   ['auth', 'ajax', 'csrf']],
    'POST /ajax/users/{id}/unassign-job'    => ['Ajax\\Controllers\\JobsAjaxController',        'unassignFromUser',['auth', 'ajax', 'csrf']],
    'POST /ajax/users/{id}/sync-jobs'       => ['Ajax\\Controllers\\JobsAjaxController',        'syncForUser',    ['auth', 'ajax', 'csrf']],






























































    // Lot 14 — Connecteurs / API keys / Webhooks
    'GET /connectors' => ['Connectors\Controllers\ConnectorController', 'index', ['auth', 'maintenance']],
    'GET /admin/connectors' => ['Connectors\Controllers\ConnectorController', 'index', ['auth', 'maintenance']],
    'GET /connectors/export.json' => ['Connectors\Controllers\ConnectorController', 'exportJson', ['auth', 'maintenance']],
    'GET /connectors/create' => ['Connectors\Controllers\ConnectorController', 'create', ['auth', 'maintenance']],
    'POST /connectors/store' => ['Connectors\Controllers\ConnectorController', 'store', ['auth', 'maintenance', 'csrf']],
    'GET /connectors/{id}/edit' => ['Connectors\Controllers\ConnectorController', 'edit', ['auth', 'maintenance']],
    'POST /connectors/{id}/update' => ['Connectors\Controllers\ConnectorController', 'update', ['auth', 'maintenance', 'csrf']],
    'POST /connectors/{id}/delete' => ['Connectors\Controllers\ConnectorController', 'delete', ['auth', 'maintenance', 'csrf']],
    'GET /connectors/api-keys' => ['Connectors\Controllers\ConnectorController', 'apiKeys', ['auth', 'maintenance']],
    'POST /connectors/api-keys/store' => ['Connectors\Controllers\ConnectorController', 'storeApiKey', ['auth', 'maintenance', 'csrf']],
    'POST /connectors/api-keys/{id}/revoke' => ['Connectors\Controllers\ConnectorController', 'revokeApiKey', ['auth', 'maintenance', 'csrf']],
    'GET /connectors/webhooks' => ['Connectors\Controllers\ConnectorController', 'webhooks', ['auth', 'maintenance']],
    'GET /connectors/events' => ['Connectors\Controllers\ConnectorController', 'events', ['auth', 'maintenance']],
    'POST /connectors/events/store' => ['Connectors\Controllers\ConnectorController', 'storeEvent', ['auth', 'maintenance', 'csrf']],


    // Lot 15 — Standards / versions / exigences
    'GET /standards' => ['Standards\Controllers\StandardController', 'index', ['auth', 'maintenance']],
    'GET /admin/standards' => ['Standards\Controllers\StandardController', 'index', ['auth', 'maintenance']],
    'GET /standards/export.json' => ['Standards\Controllers\StandardController', 'exportJson', ['auth', 'maintenance']],
    'GET /standards/create' => ['Standards\Controllers\StandardController', 'create', ['auth', 'maintenance']],
    'POST /standards/store' => ['Standards\Controllers\StandardController', 'store', ['auth', 'maintenance', 'csrf']],
    'GET /standards/{id}/edit' => ['Standards\Controllers\StandardController', 'edit', ['auth', 'maintenance']],
    'POST /standards/{id}/update' => ['Standards\Controllers\StandardController', 'update', ['auth', 'maintenance', 'csrf']],
    'POST /standards/{id}/delete' => ['Standards\Controllers\StandardController', 'delete', ['auth', 'maintenance', 'csrf']],
    'GET /standards/versions' => ['Standards\Controllers\StandardController', 'versions', ['auth', 'maintenance']],
    'POST /standards/versions/store' => ['Standards\Controllers\StandardController', 'storeVersion', ['auth', 'maintenance', 'csrf']],
    'POST /standards/versions/{id}/update' => ['Standards\Controllers\StandardController', 'updateVersion', ['auth', 'maintenance', 'csrf']],
    'POST /standards/versions/{id}/delete' => ['Standards\Controllers\StandardController', 'deleteVersion', ['auth', 'maintenance', 'csrf']],
    'POST /standards/versions/{id}/soumettre' => ['Standards\Controllers\StandardController', 'soumettreVersion', ['auth', 'maintenance', 'csrf']],
    'POST /standards/versions/{id}/valider' => ['Standards\Controllers\StandardController', 'validerVersion', ['auth', 'maintenance', 'csrf']],
    'POST /standards/versions/{id}/rejeter' => ['Standards\Controllers\StandardController', 'rejeterVersion', ['auth', 'maintenance', 'csrf']],
    'GET /standards/exigences' => ['Standards\Controllers\StandardController', 'exigences', ['auth', 'maintenance']],
    'POST /standards/exigences/store' => ['Standards\Controllers\StandardController', 'storeExigence', ['auth', 'maintenance', 'csrf']],
    'POST /standards/exigences/{id}/delete' => ['Standards\Controllers\StandardController', 'deleteExigence', ['auth', 'maintenance', 'csrf']],
    'GET /standards/versions/{id}/evaluation' => ['Standards\Controllers\StandardController', 'evaluation', ['auth', 'maintenance']],

    // Lot 16 — Validation / demandes sensibles / règles de validation
    'GET /validations' => ['Validation\Controllers\ValidationController', 'index', ['auth', 'maintenance']],
    'GET /admin/validations' => ['Validation\Controllers\ValidationController', 'index', ['auth', 'maintenance']],
    'GET /validations/export.json' => ['Validation\Controllers\ValidationController', 'exportJson', ['auth', 'maintenance']],
    'GET /validations/create' => ['Validation\Controllers\ValidationController', 'create', ['auth', 'maintenance']],
    'POST /validations/store' => ['Validation\Controllers\ValidationController', 'store', ['auth', 'maintenance', 'csrf']],
    'GET /validations/{id}' => ['Validation\Controllers\ValidationController', 'show', ['auth', 'maintenance']],
    'POST /validations/{id}/decision' => ['Validation\Controllers\ValidationController', 'decide', ['auth', 'maintenance', 'csrf']],
    'POST /validations/{id}/delete' => ['Validation\Controllers\ValidationController', 'delete', ['auth', 'maintenance', 'csrf']],
    'GET /validation-rules' => ['Validation\Controllers\ValidationController', 'rules', ['auth', 'maintenance']],
    'GET /admin/validation-rules' => ['Validation\Controllers\ValidationController', 'rules', ['auth', 'maintenance']],
    'GET /validation-rules/create' => ['Validation\Controllers\ValidationController', 'createRule', ['auth', 'maintenance']],
    'POST /validation-rules/store' => ['Validation\Controllers\ValidationController', 'storeRule', ['auth', 'maintenance', 'csrf']],
    'GET /validation-rules/{id}/edit' => ['Validation\Controllers\ValidationController', 'editRule', ['auth', 'maintenance']],
    'POST /validation-rules/{id}/update' => ['Validation\Controllers\ValidationController', 'updateRule', ['auth', 'maintenance', 'csrf']],
    'POST /validation-rules/{id}/delete' => ['Validation\Controllers\ValidationController', 'deleteRule', ['auth', 'maintenance', 'csrf']],


    // Lot 18 — Horaires / calendriers de travail / exceptions horaires
    'GET /horaires' => ['Horaires\Controllers\HorairesController', 'index', ['auth', 'maintenance']],
    'GET /admin/horaires' => ['Horaires\Controllers\HorairesController', 'index', ['auth', 'maintenance']],
    'GET /horaires/export.json' => ['Horaires\Controllers\HorairesController', 'exportJson', ['auth', 'maintenance']],
    'GET /horaires/calendrier' => ['Horaires\Controllers\HorairesController', 'calendrier', ['auth', 'maintenance']],
    'GET /horaires/create' => ['Horaires\Controllers\HorairesController', 'create', ['auth', 'maintenance']],
    'POST /horaires/store' => ['Horaires\Controllers\HorairesController', 'store', ['auth', 'maintenance', 'csrf']],
    'GET /horaires/exceptions' => ['Horaires\Controllers\HorairesController', 'exceptions', ['auth', 'maintenance']],
    'GET /horaires/exceptions/create' => ['Horaires\Controllers\HorairesController', 'createException', ['auth', 'maintenance']],
    'POST /horaires/exceptions/store' => ['Horaires\Controllers\HorairesController', 'storeException', ['auth', 'maintenance', 'csrf']],
    'GET /horaires/exceptions/{id}/edit' => ['Horaires\Controllers\HorairesController', 'editException', ['auth', 'maintenance']],
    'POST /horaires/exceptions/{id}/update' => ['Horaires\Controllers\HorairesController', 'updateException', ['auth', 'maintenance', 'csrf']],
    'POST /horaires/exceptions/{id}/delete' => ['Horaires\Controllers\HorairesController', 'deleteException', ['auth', 'maintenance', 'csrf']],
    'GET /horaires/{id}/edit' => ['Horaires\Controllers\HorairesController', 'edit', ['auth', 'maintenance']],
    'POST /horaires/{id}/update' => ['Horaires\Controllers\HorairesController', 'update', ['auth', 'maintenance', 'csrf']],
    'POST /horaires/{id}/delete' => ['Horaires\Controllers\HorairesController', 'delete', ['auth', 'maintenance', 'csrf']],



    // Lot 19 — Verrous d’entités / concurrence / sessions / historique de contexte
    'GET /verrous' => ['Verrous\Controllers\VerrousController', 'index', ['auth', 'maintenance']],
    'GET /admin/verrous' => ['Verrous\Controllers\VerrousController', 'index', ['auth', 'maintenance']],
    'GET /verrous/export.json' => ['Verrous\Controllers\VerrousController', 'exportJson', ['auth', 'maintenance']],
    'GET /verrous/create' => ['Verrous\Controllers\VerrousController', 'create', ['auth', 'maintenance']],
    'POST /verrous/store' => ['Verrous\Controllers\VerrousController', 'store', ['auth', 'maintenance', 'csrf']],
    'POST /verrous/{id}/release' => ['Verrous\Controllers\VerrousController', 'release', ['auth', 'maintenance', 'csrf']],
    'GET /verrous/sessions' => ['Verrous\Controllers\VerrousController', 'sessions', ['auth', 'maintenance']],
    'POST /verrous/sessions/{id}/revoke' => ['Verrous\Controllers\VerrousController', 'revokeSession', ['auth', 'maintenance', 'csrf']],
    'GET /verrous/contextes' => ['Verrous\Controllers\VerrousController', 'contextes', ['auth', 'maintenance']],
    'GET /verrous/maintenance' => ['Verrous\Controllers\VerrousController', 'maintenance', ['auth', 'maintenance']],


    // Lot 20 — Référentiels système / pays / devises / fuseaux horaires / TVA / statuts
    'GET /referentiels' => ['Referentiels\Controllers\ReferentielController', 'index', ['auth', 'maintenance']],
    'GET /admin/referentiels' => ['Referentiels\Controllers\ReferentielController', 'index', ['auth', 'maintenance']],
    'GET /referentiels/export.json' => ['Referentiels\Controllers\ReferentielController', 'exportJson', ['auth', 'maintenance']],
    'GET /referentiels/pays' => ['Referentiels\Controllers\ReferentielController', 'pays', ['auth', 'maintenance']],
    'GET /referentiels/devises' => ['Referentiels\Controllers\ReferentielController', 'devises', ['auth', 'maintenance']],
    'GET /referentiels/fuseaux' => ['Referentiels\Controllers\ReferentielController', 'fuseaux', ['auth', 'maintenance']],
    'GET /referentiels/tva' => ['Referentiels\Controllers\ReferentielController', 'tva', ['auth', 'maintenance']],
    'GET /referentiels/statuts' => ['Referentiels\Controllers\ReferentielController', 'statuts', ['auth', 'maintenance']],
    'GET /referentiels/transitions' => ['Referentiels\Controllers\ReferentielController', 'transitions', ['auth', 'maintenance']],
    'GET /referentiels/{type}/create' => ['Referentiels\Controllers\ReferentielController', 'create', ['auth', 'maintenance']],
    'POST /referentiels/{type}/store' => ['Referentiels\Controllers\ReferentielController', 'store', ['auth', 'maintenance', 'csrf']],
    'GET /referentiels/{type}/{id}/edit' => ['Referentiels\Controllers\ReferentielController', 'edit', ['auth', 'maintenance']],
    'POST /referentiels/{type}/{id}/update' => ['Referentiels\Controllers\ReferentielController', 'update', ['auth', 'maintenance', 'csrf']],
    'POST /referentiels/{type}/{id}/delete' => ['Referentiels\Controllers\ReferentielController', 'delete', ['auth', 'maintenance', 'csrf']],


    // Lot 21 — Organisation interne / départements / secteurs / services / équipes
    'GET /organisation' => ['Organisation\Controllers\OrganisationController', 'index', ['auth', 'maintenance']],
    'GET /admin/organisation' => ['Organisation\Controllers\OrganisationController', 'index', ['auth', 'maintenance']],
    'GET /organisation/export.json' => ['Organisation\Controllers\OrganisationController', 'exportJson', ['auth', 'maintenance']],
    'GET /organisation/liaisons' => ['Organisation\Controllers\OrganisationController', 'liaisons', ['auth', 'maintenance']],
    'POST /organisation/liaisons/store' => ['Organisation\Controllers\OrganisationController', 'storeLiaison', ['auth', 'maintenance', 'csrf']],
    'POST /organisation/liaisons/{type}/{id}/delete' => ['Organisation\Controllers\OrganisationController', 'deleteLiaison', ['auth', 'maintenance', 'csrf']],
    'GET /organisation/{type}' => ['Organisation\Controllers\OrganisationController', 'list', ['auth', 'maintenance']],
    'GET /organisation/{type}/create' => ['Organisation\Controllers\OrganisationController', 'create', ['auth', 'maintenance']],
    'POST /organisation/{type}/store' => ['Organisation\Controllers\OrganisationController', 'store', ['auth', 'maintenance', 'csrf']],
    'GET /organisation/{type}/{id}/edit' => ['Organisation\Controllers\OrganisationController', 'edit', ['auth', 'maintenance']],
    'POST /organisation/{type}/{id}/update' => ['Organisation\Controllers\OrganisationController', 'update', ['auth', 'maintenance', 'csrf']],
    'POST /organisation/{type}/{id}/delete' => ['Organisation\Controllers\OrganisationController', 'delete', ['auth', 'maintenance', 'csrf']],


    // ========================================================
    // RELATIONS SOCIETES / TYPES / REPRESENTATIONS MARQUES (Lot 23)
    // ========================================================
    'GET /relations-societes'                       => ['Relations\Controllers\RelationSocieteController', 'index',                 ['auth', 'maintenance']],
    'GET /admin/relations-societes'                 => ['Relations\Controllers\RelationSocieteController', 'index',                 ['auth', 'maintenance']],
    'GET /relations-societes/export.json'           => ['Relations\Controllers\RelationSocieteController', 'exportJson',            ['auth', 'maintenance']],
    'GET /relations-societes/create'                => ['Relations\Controllers\RelationSocieteController', 'create',                ['auth', 'maintenance']],
    'POST /relations-societes/store'                => ['Relations\Controllers\RelationSocieteController', 'store',                 ['auth', 'maintenance', 'csrf']],
    'GET /relations-societes/{id}/edit'             => ['Relations\Controllers\RelationSocieteController', 'edit',                  ['auth', 'maintenance']],
    'POST /relations-societes/{id}/update'          => ['Relations\Controllers\RelationSocieteController', 'update',                ['auth', 'maintenance', 'csrf']],
    'POST /relations-societes/{id}/delete'          => ['Relations\Controllers\RelationSocieteController', 'delete',                ['auth', 'maintenance', 'csrf']],

    'GET /relations-societes/types'                 => ['Relations\Controllers\RelationSocieteController', 'types',                 ['auth', 'maintenance']],
    'GET /relations-societes/types/create'          => ['Relations\Controllers\RelationSocieteController', 'createType',            ['auth', 'maintenance']],
    'POST /relations-societes/types/store'          => ['Relations\Controllers\RelationSocieteController', 'storeType',             ['auth', 'maintenance', 'csrf']],
    'GET /relations-societes/types/{id}/edit'       => ['Relations\Controllers\RelationSocieteController', 'editType',              ['auth', 'maintenance']],
    'POST /relations-societes/types/{id}/update'    => ['Relations\Controllers\RelationSocieteController', 'updateType',            ['auth', 'maintenance', 'csrf']],
    'POST /relations-societes/types/{id}/delete'    => ['Relations\Controllers\RelationSocieteController', 'deleteType',            ['auth', 'maintenance', 'csrf']],

    'GET /representations-marques'                  => ['Relations\Controllers\RelationSocieteController', 'representations',       ['auth', 'maintenance']],
    'GET /representations-marques/create'           => ['Relations\Controllers\RelationSocieteController', 'createRepresentation',  ['auth', 'maintenance']],
    'POST /representations-marques/store'           => ['Relations\Controllers\RelationSocieteController', 'storeRepresentation',   ['auth', 'maintenance', 'csrf']],
    'GET /representations-marques/{id}/edit'        => ['Relations\Controllers\RelationSocieteController', 'editRepresentation',    ['auth', 'maintenance']],
    'POST /representations-marques/{id}/update'     => ['Relations\Controllers\RelationSocieteController', 'updateRepresentation',  ['auth', 'maintenance', 'csrf']],
    'POST /representations-marques/{id}/delete'     => ['Relations\Controllers\RelationSocieteController', 'deleteRepresentation',  ['auth', 'maintenance', 'csrf']],


    // ========================================================
    // ABONNEMENTS / FORMULES / ESPACES APPLICATIFS / MODULES SOCIETES (Lot 24)
    // ========================================================
    'GET /abonnements'                                      => ['Abonnements\Controllers\AbonnementController', 'index',                 ['auth', 'maintenance']],
    'GET /admin/abonnements'                                => ['Abonnements\Controllers\AbonnementController', 'index',                 ['auth', 'maintenance']],
    'GET /abonnements/export.json'                          => ['Abonnements\Controllers\AbonnementController', 'exportJson',            ['auth', 'maintenance']],
    'GET /abonnements/create'                               => ['Abonnements\Controllers\AbonnementController', 'create',                ['auth', 'maintenance']],
    'POST /abonnements/store'                               => ['Abonnements\Controllers\AbonnementController', 'store',                 ['auth', 'maintenance', 'csrf']],
    'GET /abonnements/souscrire'                            => ['Abonnements\Controllers\AbonnementController', 'souscrireForm',         ['auth', 'maintenance']],
    'POST /abonnements/souscrire'                           => ['Abonnements\Controllers\AbonnementController', 'souscrire',             ['auth', 'maintenance', 'csrf']],
    'GET /abonnements/{id}/edit'                            => ['Abonnements\Controllers\AbonnementController', 'edit',                  ['auth', 'maintenance']],
    'POST /abonnements/{id}/update'                         => ['Abonnements\Controllers\AbonnementController', 'update',                ['auth', 'maintenance', 'csrf']],
    'POST /abonnements/{id}/delete'                         => ['Abonnements\Controllers\AbonnementController', 'delete',                ['auth', 'maintenance', 'csrf']],

    'GET /abonnements/formules'                             => ['Abonnements\Controllers\AbonnementController', 'formules',              ['auth', 'maintenance']],
    'GET /abonnements/formules/create'                      => ['Abonnements\Controllers\AbonnementController', 'createFormule',         ['auth', 'maintenance']],
    'POST /abonnements/formules/store'                      => ['Abonnements\Controllers\AbonnementController', 'storeFormule',          ['auth', 'maintenance', 'csrf']],
    'GET /abonnements/formules/{id}/edit'                   => ['Abonnements\Controllers\AbonnementController', 'editFormule',           ['auth', 'maintenance']],
    'POST /abonnements/formules/{id}/update'                => ['Abonnements\Controllers\AbonnementController', 'updateFormule',         ['auth', 'maintenance', 'csrf']],
    'POST /abonnements/formules/{id}/delete'                => ['Abonnements\Controllers\AbonnementController', 'deleteFormule',         ['auth', 'maintenance', 'csrf']],

    'GET /abonnements/espaces'                              => ['Abonnements\Controllers\AbonnementController', 'espaces',               ['auth', 'maintenance']],
    'GET /abonnements/espaces/create'                       => ['Abonnements\Controllers\AbonnementController', 'createEspace',          ['auth', 'maintenance']],
    'POST /abonnements/espaces/store'                       => ['Abonnements\Controllers\AbonnementController', 'storeEspace',           ['auth', 'maintenance', 'csrf']],
    'GET /abonnements/espaces/{id}/edit'                    => ['Abonnements\Controllers\AbonnementController', 'editEspace',            ['auth', 'maintenance']],
    'POST /abonnements/espaces/{id}/update'                 => ['Abonnements\Controllers\AbonnementController', 'updateEspace',          ['auth', 'maintenance', 'csrf']],
    'POST /abonnements/espaces/{id}/delete'                 => ['Abonnements\Controllers\AbonnementController', 'deleteEspace',          ['auth', 'maintenance', 'csrf']],

    'GET /abonnements/modules-societes'                     => ['Abonnements\Controllers\AbonnementController', 'modulesSocietes',       ['auth', 'maintenance']],
    'GET /abonnements/modules-societes/create'              => ['Abonnements\Controllers\AbonnementController', 'createModuleSociete',   ['auth', 'maintenance']],
    'POST /abonnements/modules-societes/store'              => ['Abonnements\Controllers\AbonnementController', 'storeModuleSociete',    ['auth', 'maintenance', 'csrf']],
    'GET /abonnements/modules-societes/{id}/edit'           => ['Abonnements\Controllers\AbonnementController', 'editModuleSociete',     ['auth', 'maintenance']],
    'POST /abonnements/modules-societes/{id}/update'        => ['Abonnements\Controllers\AbonnementController', 'updateModuleSociete',   ['auth', 'maintenance', 'csrf']],
    'POST /abonnements/modules-societes/{id}/delete'        => ['Abonnements\Controllers\AbonnementController', 'deleteModuleSociete',   ['auth', 'maintenance', 'csrf']],


    // ========================================================
    // MENUS DYNAMIQUES / ELEMENTS / NAVIGATION ACTIVE (Lot 26)
    // ========================================================
    'GET /menus-dashboard'                         => ['Menus\Controllers\MenuController', 'index',          ['auth', 'maintenance']],
    'GET /admin/menus'                             => ['Menus\Controllers\MenuController', 'index',          ['auth', 'maintenance']],
    'GET /menus'                                   => ['Menus\Controllers\MenuController', 'menus',          ['auth', 'maintenance']],
    'GET /menus/export.json'                       => ['Menus\Controllers\MenuController', 'exportJson',     ['auth', 'maintenance']],
    'GET /menus/navigation.json'                   => ['Menus\Controllers\MenuController', 'navigationJson', ['auth', 'maintenance']],
    'GET /menus/create'                            => ['Menus\Controllers\MenuController', 'create',         ['auth', 'maintenance']],
    'POST /menus/store'                            => ['Menus\Controllers\MenuController', 'store',          ['auth', 'maintenance', 'csrf']],
    'GET /menus/{id}/edit'                         => ['Menus\Controllers\MenuController', 'edit',           ['auth', 'maintenance']],
    'POST /menus/{id}/update'                      => ['Menus\Controllers\MenuController', 'update',         ['auth', 'maintenance', 'csrf']],
    'POST /menus/{id}/delete'                      => ['Menus\Controllers\MenuController', 'delete',         ['auth', 'maintenance', 'csrf']],
    'GET /menus/elements'                          => ['Menus\Controllers\MenuController', 'elements',       ['auth', 'maintenance']],
    'GET /menus/elements/create'                   => ['Menus\Controllers\MenuController', 'createElement',  ['auth', 'maintenance']],
    'POST /menus/elements/store'                   => ['Menus\Controllers\MenuController', 'storeElement',   ['auth', 'maintenance', 'csrf']],
    'GET /menus/elements/{id}/edit'                => ['Menus\Controllers\MenuController', 'editElement',    ['auth', 'maintenance']],
    'POST /menus/elements/{id}/update'             => ['Menus\Controllers\MenuController', 'updateElement',  ['auth', 'maintenance', 'csrf']],
    'POST /menus/elements/{id}/delete'             => ['Menus\Controllers\MenuController', 'deleteElement',  ['auth', 'maintenance', 'csrf']],


    // ========================================================
    // PORTAIL SOCIETE / CONTEXTE ACTIF / SELECTEURS (Lot 27)
    // ========================================================
    'GET /portail'                         => ['Portail\Controllers\PortailController', 'index',         ['auth', 'maintenance']],
    'GET /portail/contexte'                => ['Portail\Controllers\PortailController', 'contexte',      ['auth', 'maintenance']],
    'GET /portail/contexte/options.json'   => ['Portail\Controllers\PortailController', 'optionsJson',   ['auth', 'maintenance']],
    'POST /portail/contexte/update'        => ['Portail\Controllers\PortailController', 'updateContexte',['auth', 'maintenance', 'csrf']],


    // ========================================================
    // SUPER-ADMIN / GESTION APPLICATION / AUTOTESTS (Lot 29)
    // ========================================================
    // CORRECTIF (section 3 du roadmap, "routes /super-admin/* protegees
    // seulement au niveau controleur") : 'role:super_administrateur' ajoute
    // une seconde ligne de defense au niveau du routeur lui-meme (RoleMiddleware,
    // jusqu'ici defini mais jamais utilise par aucune route). Le requireRole()
    // existant dans chaque action de SuperAdminController est conserve tel quel
    // (defense en profondeur) : si une future action oublie cet appel, le
    // routeur bloque quand meme l'acces avant meme d'instancier le controleur.
    'GET /super-admin'                     => ['SuperAdmin\\Controllers\\SuperAdminController', 'index',       ['auth', 'maintenance', 'role:super_administrateur']],
    'GET /admin/application'               => ['SuperAdmin\\Controllers\\SuperAdminController', 'application', ['auth', 'maintenance', 'role:super_administrateur']],
    'GET /super-admin/application'         => ['SuperAdmin\\Controllers\\SuperAdminController', 'application', ['auth', 'maintenance', 'role:super_administrateur']],
    'GET /super-admin/export.json'         => ['SuperAdmin\\Controllers\\SuperAdminController', 'exportJson',   ['auth', 'maintenance', 'role:super_administrateur']],
    'GET /super-admin/justification'       => ['SuperAdmin\\Controllers\\SuperAdminController', 'justificationForm',   ['auth', 'maintenance', 'role:super_administrateur']],
    'POST /super-admin/justification'      => ['SuperAdmin\\Controllers\\SuperAdminController', 'justificationSubmit', ['auth', 'maintenance', 'role:super_administrateur', 'csrf']],

    'GET /autotests'                       => ['AutoTests\\Controllers\\AutoTestController',    'index',       ['auth', 'maintenance']],
    'GET /admin/autotests'                 => ['AutoTests\\Controllers\\AutoTestController',    'index',       ['auth', 'maintenance']],
    'GET /autotests/export.json'           => ['AutoTests\\Controllers\\AutoTestController',    'exportJson',  ['auth', 'maintenance']],
    'GET /autotests/module/{module}'       => ['AutoTests\\Controllers\\AutoTestController',    'module',      ['auth', 'maintenance']],
    'GET /autotests/debogage'              => ['AutoTests\\Controllers\\AutoTestController',    'debogage',    ['auth', 'maintenance']],

];