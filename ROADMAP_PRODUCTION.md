# Feuille de route — AutoSAV vers la production

Audit réalisé le 2026-06-25 sur le code extrait de `public_html (6).zip`. Quatre angles inspectés : sécurité/autorisation, modèle de données multi-tenant, architecture/qualité des modules, tests & exploitation.

Légende sévérité : 🔴 Critique (bloquant production) · 🟡 Majeur (à corriger avant un lancement réel) · 🟠 Moyen · ⚪ Mineur

---

## 0. Déjà fait dans cette session

- **Bug corrigé** : les notifications flash (succès/erreur) n'étaient jamais affichées — `SweetAlertGenerator::renderFromFlash()` existait mais n'était appelé nulle part dans `Core/Theme/Vue.php`. La page `/profile` ne signalait donc jamais qu'un mot de passe et sa confirmation ne correspondaient pas. Corrigé + ajout d'un contrôle JS côté client.
- **Profils démo ajoutés** : Groupe de concessions et Concession (11 comptes chacun), même échelle de rôles que MotorGroup/ImportAuto/NeoVolt — `database/seed_demo_concession_groupe.sql`.
- **Dépôt git initialisé** proprement pour le projet (l'ancien `npetrovic91/projet_web` est désynchronisé — structure `src/Core` obsolète — et le `.git` local précédent englobait tout le profil Windows par erreur).

---

## 1. Bloquants critiques (🔴) — traités le 2026-06-25

| # | Sujet | Constat initial | Traitement effectué |
|---|---|---|---|
| 1.1 | **Schéma DB non versionné** | `database/migrations/` était vide. | ✅ `database/migrations/0001_baseline_schema.sql` (schéma complet, 139 tables, extrait du dump sans aucune donnée) + `bin/migrate.php` (runner idempotent, suivi dans `sav_migrations`, `composer run autosav:migrate`). **Découverte en cours de route** : 5 migrations historiques (lot35/36/37/38, v431) étaient en réalité référencées par les tests existants mais absentes de l'archive — reconstituées à l'identique du contenu attendu par les tests et de l'état réel de la base (voir §1.3). |
| 1.2 | **Aucun pipeline CI** | Pas de `.github/workflows`. | ✅ `.github/workflows/ci.yml` : lint PHP sur tout le code, `composer run test`, `check-production.php` informatif. Se déclenche sur push/PR. |
| 1.3 | **Tests quasi inexistants** | 20 scripts maison, jamais exécutés automatiquement ; 7 d'entre eux échouaient en réalité (silencieusement). | ✅ Les 7 échecs réels ont été corrigés : 5 dus aux migrations manquantes (reconstituées), 1 à un test devenu obsolète après un refactor légitime de `RoleResolver` (assertion mise à jour), 1 à un vrai bug CSP/CSRF dans `Modules/Shared/Views` (3 handlers `onsubmit` inline + 7 formulaires postant l'ancien champ `csrf_token` au lieu du `_csrf_token` standard — **corrigés**, ces formulaires échouaient probablement la validation CSRF en silence). Ajout de `bin/run_tests.php` (runner unique, code de sortie exploitable en CI) + `tests/_assert_handler.php` (rend les échecs visibles malgré `display_errors=0` en prod — sans ce fichier, **tous les tests échouaient sans aucune sortie**, masquant le problème). 2 nouveaux tests ajoutés sur les zones les plus sensibles (`AbacDenyPrecedenceTest`, `ProfilePasswordChangeFlashTest`). 22/22 tests passent désormais. |
| 1.4 | **Rétention des sauvegardes** | Signalé comme non appliqué par l'audit initial. | ℹ️ **Faux positif corrigé** : `BackupService::purgeOldBackups()` existe et est bien appelé après chaque sauvegarde réussie (`Core/Services/Production/BackupService.php:81`). Rien à corriger ici — l'audit précédent avait mal lu le code. |
| 1.5 | **Cron de prod non installé** | Aucun moyen de vérifier si le cron tourne réellement sur Hostinger. | ✅ `scripts/check-production.php` vérifie maintenant la fraîcheur des `storage/logs/cron_*.log` (avertit si absents ou périmés). `deploy/cron.example` documente la procédure d'installation précise via hPanel (pas de `crontab -e` en mutualisé). **Reste à faire manuellement** : installer les 4 tâches sur hPanel — je n'ai pas d'accès à l'hébergement depuis cet environnement. |

**Limite à connaître** : `bin/migrate.php` et les 5 migrations reconstituées n'ont pas pu être testés contre une vraie connexion MariaDB depuis cet environnement (pas d'accès réseau à la base de prod). Le SQL a été construit à partir du schéma réel et des données déjà présentes dans le dump, et toutes les requêtes sont idempotentes (`INSERT IGNORE` / `WHERE NOT EXISTS` / `ON DUPLICATE KEY UPDATE`), mais une vérification sur une copie de la base avant exécution en production reste recommandée : `php bin/migrate.php --dry-run` puis `php bin/migrate.php` sur un environnement de test d'abord.

## 2. Majeurs (🟡)

| # | Sujet | Constat | Statut |
|---|---|---|---|
| 2.0a | **Contrainte importateur_id sur les concessions** | Le cahier des charges l'impose, aucun formulaire/validation ne l'appliquait. | ✅ Traité le 2026-06-25 : champ formulaire + validation backend + relation `sav_relations_societes` (`ConcessionImportateurConstraintTest`). |
| 2.0b | **Accès justifié super_admin (ACC-007)** | `Modules/SuperAdmin` ne faisait que du reporting, aucun flux de justification. | ✅ Traité : `SuperAdminAccessGuard`, table `sav_acces_donnees_superadmin`, formulaire de justification obligatoire (`SuperAdminJustificationTest`). |
| 2.0c | **Workflow de validation des standards (ACC-008)** | Une version de standard était immédiatement active, sans brouillon ni validation par un tiers. | ✅ Traité : états draft/pending_validation/approved/rejected, auto-validation interdite, niveau supérieur exigé (`StandardValidationWorkflowTest`). |
| 2.0d | **Propagation groupe → concessions (ACC-009)** | Table `sav_bulk_actions` absente du schéma réel, aucune implémentation. | ✅ Traité : `GroupPropagationService` (preview/confirmation/rollback génériques sur rôles/fonctions/compétences/certifications) (`GroupPropagationTest`). |
| 2.0e | **Historique société non abonnée (ACC-001)** | Créer l'abonnement et l'espace applicatif étaient deux actions manuelles séparées, sans preuve de conservation d'historique. | ✅ Traité : souscription atomique (transaction) + compteurs d'historique affichés (`SubscriptionHistoryPreservationTest`). |
| 2.1 | **2FA non branchée** | Le champ DB existe, mais `AuthService` retourne toujours `requires_2fa => false`. | ⏸️ Reporté à la demande explicite de l'utilisateur (« 2FA ne sera pas utilisée au début »). Pas de travail effectué. |
| 2.2 | **Pas d'outillage qualité de code** | Ni PHPStan/Psalm ni PHPCS configurés. | ✅ Traité : PHPStan niveau 5 + baseline (46 entrées, bruit lié à l'environnement statique de CI). **4 bugs d'exécution réels trouvés et corrigés** dès l'installation : `database()` fonction inexistante (4 endpoints cassés), `TermsVersionModel` classe inexistante instanciée, `BrandModel::getBrandsForCompany()` méthode inexistante appelée par 2 endpoints, fichier `Modules/Functions/Controllers/ContextController.php` en collision de namespace (mort, jamais chargé, supprimé). `composer run stan`, intégré à la CI (`PhpstanRealBugsRegressionTest`). |
| 2.3 | **Gestion d'erreurs à 3 vitesses** | Mélange d'exceptions catchées, de retours `['success' => bool, ...]`, et de `null`/`false` silencieux selon le Service — rend le comportement imprévisible pour un nouveau développeur et masque des erreurs en prod. | ✅ Traité, scope ajusté en cours de route : (1) `Core/Error/GlobalErrorHandler.php` — filet de sécurité global, tout throwable non intercepté (quel que soit le Service) suit désormais le même chemin (log critical + page générique). (2) Inventaire complet des 44 Services (3 catégories réelles trouvées après vérification manuelle modèle par modèle : ~11 utilisaient déjà `['success'=>bool,...]`, le reste mélangeait bool/int/void silencieux ou throw selon le cas). (3) `Core/Services/Concerns/ServiceResponse.php` formalise le shape déjà dominant pour toute nouvelle méthode de mutation. (4) Trous d'audit réels comblés sur les mutations critiques (cf. ligne suivante). **Décision explicite : pas de réécriture mécanique des ~20 Services à retours bool/int/void existants** — beaucoup sont des contrats légitimes (`supprimer(): bool`, `peutXxx(): bool` n'est pas un bug), et convertir en aveugle changerait la signature de dizaines de méthodes + leurs appelants Controller sans possibilité de test visuel exhaustif dans cette session : le risque de régression dépasserait la valeur d'une convergence cosmétique. |
| 2.3b | **Trous d'audit sur mutations critiques** | `StandardModel` (workflow de validation ACC-008 entier non journalisé), documents juridiques, fichiers, relations/marques/types de sociétés, coordonnées bancaires sociétés+utilisateurs. | ✅ Traité : `journaliser()` ajouté et appelé sur chaque mutation concernée (`sav_journaux_audit`). Coordonnées bancaires : seul le suffixe (4 derniers caractères) de l'IBAN est journalisé, jamais la donnée complète. |
| 2.4 | **Notifications in-app + audit pour chaque action utilisateur** | `ActionNotifier` existait déjà (créé lors d'un chantier précédent) mais n'était branché que sur 3 actions (mot de passe, rôle, validation/rejet de standard). | ✅ Étendu à 5 actions supplémentaires affectant directement un utilisateur identifiable : décision sur une demande de validation métier (`ValidationService::deciderDemande`), décision RGPD — **acceptation/rejet de demande** (`GdprService::acceptRequest/rejectRequest`, exigence légale RGPD d'informer la personne concernée, pas seulement une bonne pratique UX), rattachement à une nouvelle société (`UserCompanyService::attachUserToCompany`), assignation d'un nouveau supérieur hiérarchique (`UserHierarchyService::addManager`), acceptation d'invitation — notifie l'invitant (`InvitationService::accepterInvitation`). Pas d'email pour ces 5 cas (décision utilisateur confirmée : audit + notification in-app, pas un email pour chaque action). |
| 2.5 | **Emails PHPMailer fonctionnels (existants + nouveaux déclencheurs)** | EmailService déclaré mais pas vérifié end-to-end ; pas de nouveaux déclencheurs (changement mot de passe, changement de rôle...). | ✅ EmailService vérifié end-to-end lors d'un chantier précédent (sandbox, échec si SMTP mal configuré, voir `MailConfigurationTest`). 2 nouveaux déclencheurs réels ajoutés : (1) **`InvitationService`** — le jeton d'invitation n'était JAMAIS envoyé par email, seulement affiché en flash message à l'administrateur qui devait le transmettre lui-même : un parcours fonctionnellement cassé, pas juste une amélioration UX. Corrigé sur création ET régénération de jeton. (2) **`GdprService::acceptRequest()/rejectRequest()`** — passé de notification in-app seule à in-app + email réel : une notification in-app ne suffit pas à l'obligation légale RGPD d'informer la personne concernée si elle ne se reconnecte jamais à l'application. |

## 2.6 Audit DevOps senior 2026-06-26 — durcissement production

| Sévérité | Constat | Statut |
|---|---|---|
| CRIT-1 | `.env` réel non versionné (déjà correct), template production manquant | ✅ `.env.production.template` ajouté. `.env` lui-même volontairement non touché à la demande de l'utilisateur (sera renseigné juste avant mise en production). |
| HIGH-1 | Race condition TOCTOU dans `RateLimitMiddleware` (lecture sans verrou) | ✅ Traité : lecture+écriture sous un seul `flock(LOCK_EX)`. |
| HIGH-2 | `BaseModel::findAll()` — `$orderBy` concaténé sans validation | ✅ Traité : `quoteIdentifier()` + whitelist optionnelle. |
| HIGH-3 | Détection de placeholder `HEALTHCHECK_TOKEN`/`ENCRYPTION_KEY` ne couvrait pas le nouveau préfixe `REMPLACER_` | ✅ Traité : regex étendues + exigence de format réel (64 hex). |
| HIGH-4 | Détournement de session (changement de user-agent) détecté mais jamais journalisé | ✅ Traité : log `security` avant destruction + `sha256`/`hash_equals()`. |
| HIGH-5 | Nonce CSP prétendument non appliqué à `script-src`/`style-src` | ✅ Vérifié comme déjà correctement implémenté (faux positif de l'audit fourni). |
| MED-1 | `style-src-attr 'unsafe-inline'` actif (compat attributs `style=""` inline) | 🔜 **Non traité** : 78 occurrences `style="..."` recensées dans 41 fichiers de vues. Migration vers classes CSS nécessaire mais à risque de régression visuelle élevé sans possibilité de test visuel exhaustif dans cette session — à traiter par lot, vue par vue, avec vérification manuelle à chaque fois. |
| MED-2 | `storage/` exposable si `mod_rewrite` désactivé | ✅ Traité : `storage/.htaccess` dédié (`Require all denied`, indépendant de mod_rewrite). |
| MED-3 | Pas d'audit CVE en CI | ✅ Traité : `composer audit` ajouté à `ci.yml`. |
| MED-4 | `composer.lock` exclu du dépôt (builds non reproductibles) | ✅ Traité : retiré du `.gitignore`, versionné. |
| MED-5 | Buckets de rate limiting jamais purgés | ✅ Traité : `RateLimitMiddleware::purgeExpiredBuckets()` + appel depuis `run_maintenance.php`. |
| MED-6 | Mot de passe potentiellement loggé en clair si saisi par erreur dans le champ identifiant | ✅ Traité : `AuthModel::enregistrerTentativeConnexion()` ne persiste la valeur brute que si elle a la forme d'un email, sinon marqueur masqué. |
| MED-7 | Sessions PHP stockées en fichiers non chiffrés | 🔜 **Accepté comme risque résiduel documenté** : migrer vers un handler de session chiffré ou un stockage BDD est un changement d'architecture significatif, hors proportion avec la sévérité MED sur un hébergement mutualisé où le vrai contrôle est la permission fichier (déjà couverte par `Makefile storage-init`/`permissions` et le pool PHP-FPM dédié en cas de migration VPS). À revisiter si l'hébergement évolue vers un environnement multi-tenant à risque plus élevé. |
| LOW-1 | "PHP 7.2" dans un dump SQL vs PHP 8.1+ requis | ✅ Clarifié dans `RUNBOOK_DEPLOIEMENT.md` : en-tête d'export phpMyAdmin, sans rapport avec le runtime réel. |
| LOW-2 | 2FA non forcée pour les rôles sensibles | ⏸️ Reporté (cf. 2.1, décision explicite de l'utilisateur). |
| LOW-3 | Pas de `report-uri` CSP | ✅ Traité : `public/csp-report.php` + directive dans `CSP_POLICY`. |
| LOW-4 | Mot de passe démo documenté en clair dans le dépôt | ✅ Traité : avertissement renforcé dans `docs/COMPTES_DEMO_LOT35.md` (ne doit jamais exister en production, quel que soit le mot de passe). |

## 3. Moyens / mineurs (🟠⚪) — amélioration continue

- **EventTriggers** dépend silencieusement de `Notifications` sans encapsulation propre (Service intermédiaire manquant) — risque de casse si Notifications est refactorisé.
- **Administration/SecurityController** contient de la logique métier qui devrait être dans un Service, pas directement dans le Controller/Model.
- Deux noms différents pour la même opération CSRF (`requireCsrf`/`validateCsrf` vs `verifyCsrf`) — à unifier pour la lisibilité.
- Routes `/super-admin/*` protégées seulement au niveau contrôleur (`requireRole()`), pas par un middleware de routing dédié — fonctionnellement correct mais plus fragile si un contrôleur oublie l'appel.
- Échecs d'écriture d'audit avalés silencieusement si la base d'audit est indisponible (`UserModel.php:66-75`) — à transformer en alerte au minimum.
- `company_id` accepté en paramètre GET sur certains contrôleurs Ajax (mitigé par le middleware tenant, mais à nettoyer pour éviter toute énumération).

## 4. Ce qui est déjà solide (ne pas re-creuser)

- CSRF systématique sur tous les POST/PUT/DELETE, sessions régénérées après login.
- Hash Argon2id, politique de mot de passe stricte (10+ caractères, complexité).
- Moteur ABAC : `deny` bat bien `allow` dans le code (pas seulement dans la doc), pas seulement déclaratif.
- Aucune trace de fonctionnalité d'impersonation, conforme à l'exigence métier.
- Soft-delete respecté partout sur les données métier (`supprime_le`/`deleted_at`), pas de `DELETE` physique trouvé hors tables techniques.
- Cloisonnement par société active appliqué de façon centralisée via `TenantEntitlementMiddleware` (vérifie adhésion + abonnement + espace applicatif actif, pas juste l'existence de la société).
- Requêtes préparées systématiques, échappement HTML cohérent (`Esc::h`/`e()`), aucune injection SQL/XSS évidente trouvée sur l'échantillon audité.

---

## 5. Ordre de bataille recommandé

1. **Semaine 1** : migrations versionnées (1.1) + script de purge des sauvegardes (1.4) + vérification du cron réel (1.5). Ce sont les trois points où une absence devient un incident concret (perte de schéma, disque plein, pas de sauvegarde fraîche).
2. **Semaines 2-3** : pipeline CI minimal (1.2) + premiers tests PHPUnit sur Auth/Roles/Profile/Abonnements (1.3).
3. **Semaine 4** : 2FA (2.1) + contrainte importateur_id sur les concessions (2.2).
4. **En continu** : PHPStan (2.3), convergence de la gestion d'erreurs (2.4), nettoyage architecture (section 3).

Tant que les points 1.1 à 1.5 ne sont pas traités, le projet n'est pas "production-ready" au sens opérationnel — même si le code applicatif lui-même (sécurité, autorisation, cloisonnement) est globalement solide.
