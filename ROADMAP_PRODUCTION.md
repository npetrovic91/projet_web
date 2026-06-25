# Feuille de route — AutoSAV vers la production

Audit réalisé le 2026-06-25 sur le code extrait de `public_html (6).zip`. Quatre angles inspectés : sécurité/autorisation, modèle de données multi-tenant, architecture/qualité des modules, tests & exploitation.

Légende sévérité : 🔴 Critique (bloquant production) · 🟡 Majeur (à corriger avant un lancement réel) · 🟠 Moyen · ⚪ Mineur

---

## 0. Déjà fait dans cette session

- **Bug corrigé** : les notifications flash (succès/erreur) n'étaient jamais affichées — `SweetAlertGenerator::renderFromFlash()` existait mais n'était appelé nulle part dans `Core/Theme/Vue.php`. La page `/profile` ne signalait donc jamais qu'un mot de passe et sa confirmation ne correspondaient pas. Corrigé + ajout d'un contrôle JS côté client.
- **Profils démo ajoutés** : Groupe de concessions et Concession (11 comptes chacun), même échelle de rôles que MotorGroup/ImportAuto/NeoVolt — `database/seed_demo_concession_groupe.sql`.
- **Dépôt git initialisé** proprement pour le projet (l'ancien `npetrovic91/projet_web` est désynchronisé — structure `src/Core` obsolète — et le `.git` local précédent englobait tout le profil Windows par erreur).

---

## 1. Bloquants critiques (🔴) — à traiter avant tout lancement

| # | Sujet | Constat | Action |
|---|---|---|---|
| 1.1 | **Schéma DB non versionné** | `database/migrations/` est vide. Le schéma vit uniquement dans un dump SQL externe (`u166513890_base (4).sql`), sans traçabilité ni rollback possible. | Découper le dump existant en migrations numérotées (un fichier par table/groupe cohérent), avec un petit runner (`bin/migrate.php`) qui applique/journalise ce qui a déjà tourné. |
| 1.2 | **Aucun pipeline CI** | Pas de `.github/workflows`. Déploiement 100% manuel (upload de zip). Aucun garde-fou avant mise en ligne. | GitHub Actions minimal : `composer install`, lint PHP (`php -l`), exécution des tests, puis `php scripts/check-production.php` en mode dry-run sur chaque PR. |
| 1.3 | **Tests quasi inexistants** | 20 scripts de test maison (pas PHPUnit malgré la dépendance déclarée), 90% des ~40 modules sans aucun test, rien n'est exécuté automatiquement. | Prioriser PHPUnit réel sur les modules sensibles (Auth, Roles/ABAC, Profile, Companies, Abonnements) avant d'étendre. Brancher dans la CI du point 1.2. |
| 1.4 | **Rétention des sauvegardes non appliquée** | `BACKUP_RETENTION_DAYS=30` déclaré en `.env` mais aucun script ne purge les anciens fichiers — `bin/backup_database.php` crée, rien ne nettoie. Le disque se remplit indéfiniment sur un hébergement mutualisé à quota limité. | Ajouter la purge dans `bin/backup_database.php` ou un script dédié, planifié par le même cron. |
| 1.5 | **Cron de prod non installé** | `deploy/cron.example` documente backup/rotate-logs/maintenance/health-check, mais rien ne dit que c'est réellement actif sur l'hébergement Hostinger. | Vérifier/installer le cron réel côté hébergeur, puis confirmer dans `scripts/check-production.php` que les dernières exécutions sont récentes (pas juste que le script existe). |

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
