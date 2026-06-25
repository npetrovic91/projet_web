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

## 2. Majeurs (🟡) — à traiter avant une ouverture commerciale

| # | Sujet | Constat | Action |
|---|---|---|---|
| 2.1 | **2FA non branchée** | Le champ DB existe, mais `AuthService` retourne toujours `requires_2fa => false`. | Implémenter l'activation/vérification TOTP, au moins pour les rôles `super_admin`, `pdg`, `directeur_groupe`. |
| 2.2 | **Concession sans `importateur_id` obligatoire** | Le cahier des charges l'impose ("chaque concession doit avoir un importateur_id obligatoire"), mais aucun contrôle dans `Modules/Society` ni `Modules/Organisation` ne l'empêche. Les concessions démo existantes (Auto Avenue Paris, etc.) n'ont d'ailleurs pas ce lien. | Ajouter la contrainte applicative à la création/modification d'une concession (Service de validation), puis backfill les concessions démo existantes. |
| 2.3 | **Pas d'outillage qualité de code** | Ni PHPStan/Psalm ni PHPCS configurés. Rien n'empêche une régression silencieuse de type erreur de typage. | Ajouter PHPStan niveau 5+ a minima sur `Core/`, intégré à la CI du 1.2. |
| 2.4 | **Gestion d'erreurs à 3 vitesses** | Mélange d'exceptions catchées, de retours `['success' => bool, ...]`, et de `null`/`false` silencieux selon le Service — rend le comportement imprévisible pour un nouveau développeur et masque des erreurs en prod. | Converger vers un seul contrat (recommandé : retour `['success' => bool, 'message' => ..., 'errors' => ...]` partout, exceptions réservées aux erreurs système/infra). |

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
