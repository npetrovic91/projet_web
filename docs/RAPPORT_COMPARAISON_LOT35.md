# Comparaison AUTOSAV — archive lot35 jointe vs autosav_production_ready.zip

## Verdict

Oui : la version `autosav_production_ready.zip` que j’ai générée est incomplète par rapport à l’archive jointe. Elle correspond à un socle production minimal/stabilisé, pas à une reprise complète du lot 35. L’archive jointe doit être considérée comme base canonique.

## Synthèse chiffrée

| Élément | Archive lot35 jointe | Version générée | Écart |
|---|---:|---:|---:|
| Fichiers | 473 | 225 | -248 |
| Taille fichiers | 1 822 860 o | 1 176 615 o | -646 245 o |
| Routes déclarées | 429 | 42 | -387 |
| Routes POST | 182 | 21 | -161 |
| Fichiers communs identiques | 66 | 66 | — |
| Fichiers communs modifiés | 144 | 144 | — |
| Fichiers présents seulement dans lot35 | 263 | 0 | 263 manquants |
| Fichiers présents seulement dans version générée | 0 | 15 | 15 ajouts spécifiques |

## Manques principaux dans la version générée

### Par dossier racine

- `Modules` : 221 fichier(s) manquant(s)
- `Core` : 17 fichier(s) manquant(s)
- `tests` : 8 fichier(s) manquant(s)
- `bin` : 5 fichier(s) manquant(s)
- `database` : 4 fichier(s) manquant(s)
- `deploy` : 3 fichier(s) manquant(s)
- `storage` : 2 fichier(s) manquant(s)
- `config` : 1 fichier(s) manquant(s)
- `docs` : 1 fichier(s) manquant(s)
- `public` : 1 fichier(s) manquant(s)

### Modules impactés

| Module | Fichiers manquants |
|---|---:|
| Roles | 13 |
| Abonnements | 12 |
| Users | 12 |
| Horaires | 10 |
| Notes | 10 |
| Relations | 10 |
| Verrous | 10 |
| Connectors | 9 |
| Menus | 9 |
| Standards | 9 |
| Validation | 9 |
| Contacts | 8 |
| Emails | 8 |
| Invitations | 8 |
| Organisation | 8 |
| Files | 7 |
| LegalDocuments | 7 |
| Referentiels | 7 |
| Settings | 7 |
| AutoTests | 6 |
| Portail | 6 |
| SuperAdmin | 6 |
| Auth | 5 |
| Notifications | 4 |
| BulkMail | 3 |
| Dashboard | 3 |
| EventTriggers | 3 |
| Administration | 1 |
| Ajax | 1 |
| Brands | 1 |
| Companies | 1 |
| Functions | 1 |
| GDPR | 1 |
| Jobs | 1 |
| Maintenance | 1 |
| Profile | 1 |
| Qualifications | 1 |
| Skills | 1 |
| Society | 1 |

### Core impacté

| Sous-dossier Core | Fichiers manquants |
|---|---:|
| Services | 7 |
| Security | 4 |
| Helpers | 2 |
| Middleware | 2 |
| Logger | 1 |
| View | 1 |

## Routes par module

| Module routeur | Lot35 | Version générée |
|---|---:|---:|
| Abonnements | 26 | 0 |
| Administration | 6 | 7 |
| Ajax | 50 | 0 |
| Auth | 15 | 15 |
| AutoTests | 5 | 0 |
| Brands | 6 | 0 |
| BulkMail | 8 | 0 |
| Companies | 12 | 0 |
| Connectors | 14 | 0 |
| Contacts | 13 | 0 |
| Dashboard | 1 | 1 |
| Emails | 8 | 0 |
| EventTriggers | 2 | 0 |
| Files | 7 | 0 |
| Functions | 6 | 0 |
| GDPR | 8 | 0 |
| Horaires | 15 | 0 |
| Invitations | 13 | 0 |
| Jobs | 6 | 0 |
| LegalDocuments | 9 | 0 |
| Maintenance | 3 | 3 |
| Menus | 16 | 0 |
| Notes | 18 | 0 |
| Notifications | 6 | 0 |
| Organisation | 12 | 0 |
| Portail | 4 | 0 |
| Profile | 6 | 0 |
| Qualifications | 5 | 0 |
| Referentiels | 14 | 0 |
| Relations | 20 | 0 |
| Roles | 19 | 0 |
| Settings | 10 | 0 |
| Skills | 5 | 0 |
| Standards | 16 | 0 |
| SuperAdmin | 4 | 0 |
| Users | 16 | 16 |
| Validation | 15 | 0 |
| Verrous | 10 | 0 |

## Fichiers ajoutés uniquement par la version générée

- `.gitignore`
- `.htaccess`
- `Core/Theme/Views/errors/403.php`
- `Core/Theme/Views/errors/404.php`
- `Modules/BulkMail/Views/bulkmail_index.php`
- `database/u166513890_base.sql`
- `docs/NE-pas-OUBLIER.txt`
- `docs/PRODUCTION_CORRECTIONS.md`
- `docs/PROJET.md`
- `docs/SCHEMA_ALIGNMENT_REPORT.md`
- `docs/urls.legacy.full.php`
- `layouts/admin.php`
- `public/.user.ini`
- `scripts/check-production.php`
- `storage/.gitkeep`

## Premiers fichiers manquants côté lot35 non repris

- `Core/Helpers/AuthHelper.php`
- `Core/Helpers/functions.php`
- `Core/Logger/Class/Handler/a.php`
- `Core/Middleware/ApiKeyMiddleware.php`
- `Core/Middleware/RateLimitMiddleware.php`
- `Core/Security/Class/CspNonce.php`
- `Core/Security/Class/FileUploadValidator.php`
- `Core/Security/Class/SqlControlGuard.php`
- `Core/Security/Encryption.php`
- `Core/Services/Contracts/AuditableServiceInterface.php`
- `Core/Services/Contracts/ReadableServiceInterface.php`
- `Core/Services/Contracts/ServiceInterface.php`
- `Core/Services/Production/BackupService.php`
- `Core/Services/Production/HealthCheckService.php`
- `Core/Services/Production/LogRotationService.php`
- `Core/Services/VerrouEntiteService.php`
- `Core/View/ViewDataNormalizer.php`
- `Modules/Abonnements/Controllers/AbonnementController.php`
- `Modules/Abonnements/Models/AbonnementModel.php`
- `Modules/Abonnements/Services/AbonnementService.php`
- `Modules/Abonnements/Views/abonnement_form.php`
- `Modules/Abonnements/Views/autotest.php`
- `Modules/Abonnements/Views/espace_form.php`
- `Modules/Abonnements/Views/espaces.php`
- `Modules/Abonnements/Views/formule_form.php`
- `Modules/Abonnements/Views/formules.php`
- `Modules/Abonnements/Views/index.php`
- `Modules/Abonnements/Views/module_societe_form.php`
- `Modules/Abonnements/Views/modules_societes.php`
- `Modules/Administration/Views/autotest.php`
- `Modules/Ajax/Views/autotest.php`
- `Modules/Auth/Models/AuthModel.php`
- `Modules/Auth/Services/AuthService.php`
- `Modules/Auth/Views/autotest.php`
- `Modules/Auth/Views/forgot_password.php`
- `Modules/Auth/Views/reset_password.php`
- `Modules/AutoTests/Controllers/AutoTestController.php`
- `Modules/AutoTests/Services/AutoTestService.php`
- `Modules/AutoTests/Views/autotest.php`
- `Modules/AutoTests/Views/debogage.php`
- `Modules/AutoTests/Views/index.php`
- `Modules/AutoTests/Views/module.php`
- `Modules/Brands/Views/autotest.php`
- `Modules/BulkMail/Views/autotest.php`
- `Modules/BulkMail/Views/index.php`
- `Modules/BulkMail/Views/unsubscribed.php`
- `Modules/Companies/Views/autotest.php`
- `Modules/Connectors/Controllers/ConnectorController.php`
- `Modules/Connectors/Models/ConnectorModel.php`
- `Modules/Connectors/Services/ConnectorService.php`
- `Modules/Connectors/Views/api_keys.php`
- `Modules/Connectors/Views/autotest.php`
- `Modules/Connectors/Views/events.php`
- `Modules/Connectors/Views/form.php`
- `Modules/Connectors/Views/index.php`
- `Modules/Connectors/Views/webhooks.php`
- `Modules/Contacts/Controllers/ContactSocieteController.php`
- `Modules/Contacts/Models/ContactSocieteModel.php`
- `Modules/Contacts/Services/ContactSocieteService.php`
- `Modules/Contacts/Views/autotest.php`
- `Modules/Contacts/Views/form.php`
- `Modules/Contacts/Views/index.php`
- `Modules/Contacts/Views/type_form.php`
- `Modules/Contacts/Views/types.php`
- `Modules/Dashboard/Models/DashboardStatsModel.php`
- `Modules/Dashboard/Views/autotest.php`
- `Modules/Dashboard/Views/widgets/widget_user_company_levels.php`
- `Modules/Emails/Controllers/EmailsController.php`
- `Modules/Emails/Models/EmailModel.php`
- `Modules/Emails/Services/EmailService.php`
- `Modules/Emails/Views/autotest.php`
- `Modules/Emails/Views/index.php`
- `Modules/Emails/Views/logs.php`
- `Modules/Emails/Views/template_form.php`
- `Modules/Emails/Views/templates.php`
- `Modules/EventTriggers/Controllers/EventTriggerController.php`
- `Modules/EventTriggers/Views/autotest.php`
- `Modules/EventTriggers/Views/index.php`
- `Modules/Files/Controllers/FileController.php`
- `Modules/Files/Models/FileModel.php`
- `Modules/Files/Services/FileService.php`
- `Modules/Files/Views/autotest.php`
- `Modules/Files/Views/index.php`
- `Modules/Files/Views/show.php`
- `Modules/Files/Views/upload.php`
- `Modules/Functions/Views/autotest.php`
- `Modules/GDPR/Views/autotest.php`
- `Modules/Horaires/Controllers/HorairesController.php`
- `Modules/Horaires/Models/HorairesModel.php`
- `Modules/Horaires/Services/HorairesService.php`
- `Modules/Horaires/Views/_filters.php`
- `Modules/Horaires/Views/autotest.php`
- `Modules/Horaires/Views/calendrier.php`
- `Modules/Horaires/Views/exceptions.php`
- `Modules/Horaires/Views/form_exception.php`
- `Modules/Horaires/Views/form_horaire.php`
- `Modules/Horaires/Views/index.php`
- `Modules/Invitations/Controllers/InvitationController.php`
- `Modules/Invitations/Models/InvitationModel.php`
- `Modules/Invitations/Services/InvitationService.php`
- `Modules/Invitations/Views/accept.php`
- `Modules/Invitations/Views/autotest.php`
- `Modules/Invitations/Views/form.php`
- `Modules/Invitations/Views/index.php`
- `Modules/Invitations/Views/premier_admin.php`
- `Modules/Jobs/Views/autotest.php`
- `Modules/LegalDocuments/Controllers/LegalDocumentController.php`
- `Modules/LegalDocuments/Models/LegalDocumentModel.php`
- `Modules/LegalDocuments/Services/LegalDocumentService.php`
- `Modules/LegalDocuments/Views/autotest.php`
- `Modules/LegalDocuments/Views/form.php`
- `Modules/LegalDocuments/Views/index.php`
- `Modules/LegalDocuments/Views/show.php`
- `Modules/Maintenance/Views/autotest.php`
- `Modules/Menus/Controllers/MenuController.php`
- `Modules/Menus/Models/MenuModel.php`
- `Modules/Menus/Services/MenuService.php`
- `Modules/Menus/Views/autotest.php`
- `Modules/Menus/Views/elements.php`
- `Modules/Menus/Views/form_element.php`
- `Modules/Menus/Views/form_menu.php`
- `Modules/Menus/Views/index.php`
- `Modules/Menus/Views/menus.php`
- `Modules/Notes/Controllers/NotesController.php`
- `Modules/Notes/Models/NotesModel.php`
- `Modules/Notes/Services/NotesService.php`
- `Modules/Notes/Views/assignments.php`
- `Modules/Notes/Views/autotest.php`
- `Modules/Notes/Views/form_label.php`
- `Modules/Notes/Views/form_note.php`
- `Modules/Notes/Views/index.php`
- `Modules/Notes/Views/labels.php`
- `Modules/Notes/Views/show_note.php`
- `Modules/Notifications/Models/NotificationChannelModel.php`
- `Modules/Notifications/Models/NotificationPreferenceModel.php`
- `Modules/Notifications/Models/NotificationTemplateModel.php`
- `Modules/Notifications/Views/autotest.php`
- `Modules/Organisation/Controllers/OrganisationController.php`
- `Modules/Organisation/Models/OrganisationModel.php`
- `Modules/Organisation/Services/OrganisationService.php`
- `Modules/Organisation/Views/autotest.php`
- `Modules/Organisation/Views/form.php`
- `Modules/Organisation/Views/index.php`
- `Modules/Organisation/Views/liaisons.php`
- `Modules/Organisation/Views/list.php`
- `Modules/Portail/Controllers/PortailController.php`
- `Modules/Portail/Models/ContexteActifModel.php`
- `Modules/Portail/Services/ContexteActifService.php`
- `Modules/Portail/Views/autotest.php`
- `Modules/Portail/Views/contexte.php`
- `Modules/Portail/Views/index.php`
- `Modules/Profile/Views/autotest.php`
- `Modules/Qualifications/Views/autotest.php`
- `Modules/Referentiels/Controllers/ReferentielController.php`
- `Modules/Referentiels/Models/ReferentielModel.php`
- `Modules/Referentiels/Services/ReferentielService.php`
- `Modules/Referentiels/Views/autotest.php`
- `Modules/Referentiels/Views/form.php`
- `Modules/Referentiels/Views/index.php`
- `Modules/Referentiels/Views/list.php`
- `Modules/Relations/Controllers/RelationSocieteController.php`
- `Modules/Relations/Models/RelationSocieteModel.php`
- `Modules/Relations/Services/RelationSocieteService.php`
- `Modules/Relations/Views/autotest.php`
- `Modules/Relations/Views/form.php`
- `Modules/Relations/Views/index.php`
- `Modules/Relations/Views/representation_form.php`
- `Modules/Relations/Views/representations.php`
- `Modules/Relations/Views/type_form.php`
- `Modules/Relations/Views/types.php`
- `Modules/Roles/Controllers/AccessPolicyController.php`
- `Modules/Roles/Controllers/PermissionController.php`
- `Modules/Roles/Controllers/RoleController.php`
- `Modules/Roles/Models/RolePermissionModel.php`
- `Modules/Roles/Services/RolePermissionService.php`
- `Modules/Roles/Views/autotest.php`
- `Modules/Roles/Views/form.php`
- `Modules/Roles/Views/index.php`
- `Modules/Roles/Views/permission_form.php`
- `Modules/Roles/Views/permissions.php`
- `Modules/Roles/Views/policies.php`
- `Modules/Roles/Views/policy_show.php`
- `Modules/Roles/Views/show.php`
- `Modules/Settings/Controllers/SettingsController.php`
- `Modules/Settings/Models/ApplicationSettingModel.php`
- `Modules/Settings/Services/SettingsService.php`
- `Modules/Settings/Views/autotest.php`
- `Modules/Settings/Views/form.php`
- `Modules/Settings/Views/index.php`
- `Modules/Settings/Views/system.php`
- `Modules/Skills/Views/autotest.php`
- `Modules/Society/Views/autotest.php`
- `Modules/Standards/Controllers/StandardController.php`
- `Modules/Standards/Models/StandardModel.php`
- `Modules/Standards/Services/StandardService.php`
- `Modules/Standards/Views/autotest.php`
- `Modules/Standards/Views/evaluation.php`
- `Modules/Standards/Views/exigences.php`
- `Modules/Standards/Views/form.php`
- `Modules/Standards/Views/index.php`
- `Modules/Standards/Views/versions.php`
- `Modules/SuperAdmin/Controllers/SuperAdminController.php`
- `Modules/SuperAdmin/Models/SuperAdminModel.php`
- `Modules/SuperAdmin/Services/SuperAdminService.php`
- `Modules/SuperAdmin/Views/application.php`
- `Modules/SuperAdmin/Views/autotest.php`
- `Modules/SuperAdmin/Views/index.php`
- `Modules/Users/Models/UserCompanyHistoryModel.php`
- `Modules/Users/Models/UserCompanyModel.php`
- `Modules/Users/Models/UserHierarchyModel.php`
- `Modules/Users/Models/UserModel.php`
- `Modules/Users/Models/UserRoleModel.php`
- `Modules/Users/Services/UserCompanyService.php`
- `Modules/Users/Services/UserHierarchyService.php`
- `Modules/Users/Services/UserService.php`
- `Modules/Users/Views/_form.php`
- `Modules/Users/Views/autotest.php`
- `Modules/Users/Views/create.php`
- `Modules/Users/Views/edit.php`
- `Modules/Validation/Controllers/ValidationController.php`
- `Modules/Validation/Models/ValidationModel.php`
- `Modules/Validation/Services/ValidationService.php`
- `Modules/Validation/Views/autotest.php`
- `Modules/Validation/Views/form_demande.php`
- `Modules/Validation/Views/form_rule.php`
- `Modules/Validation/Views/index.php`
- `Modules/Validation/Views/rules.php`
- `Modules/Validation/Views/show_demande.php`
- `Modules/Verrous/Controllers/VerrousController.php`
- `Modules/Verrous/Models/VerrousModel.php`
- `Modules/Verrous/Services/VerrousService.php`
- `Modules/Verrous/Views/_filters.php`
- `Modules/Verrous/Views/autotest.php`
- `Modules/Verrous/Views/contextes.php`
- `Modules/Verrous/Views/create.php`
- `Modules/Verrous/Views/index.php`
- `Modules/Verrous/Views/maintenance.php`
- `Modules/Verrous/Views/sessions.php`
- `bin/backup_database.php`
- `bin/health_check.php`
- `bin/production_preflight.php`
- `bin/rotate_logs.php`
- `bin/run_maintenance.php`
- `config/production.php`
- `database/migrations/.gitkeep`
- `database/migrations/2026_06_01_lot31_index_performance.sql`
- `database/migrations/2026_06_01_lot32_index_qualite_robustesse.sql`
- `database/migrations/2026_06_01_lot33_infrastructure_production.sql`
- `deploy/apache-vhost.example.conf`
- `deploy/cron.example`
- … 13 autres fichiers

## Conclusion opérationnelle

La bonne suite est de reconstruire une archive de production complète à partir du lot35 joint, puis de réappliquer uniquement les durcissements utiles de `autosav_production_ready.zip` : `.htaccess` racine, `.user.ini`, contrôle production, dump SQL si nécessaire, et éventuelles corrections de configuration. Il ne faut pas remplacer le lot35 par la version générée.