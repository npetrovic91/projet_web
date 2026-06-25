# OMD — Modèle de données central révisé

> **Projet confidentiel soumis sous licence.**  
> Ce document contient des éléments brevetés.  
> Application écrite en français.

---

## 0. Objet du document

Ce document réécrit l’**OMD — Objet / Modèle de Données** de l’application modulaire multi-tenant.

Il tient compte :

- du cahier des charges initial ;
- des décisions d’architecture validées ;
- du modèle de données central proposé ;
- des précisions apportées concernant les sociétés abonnées et non abonnées ;
- des futurs modules métier, dont la marketplace.

L’objectif est de définir un **noyau de données stable**, extensible, capable de supporter :

- une base applicative vide au départ ;
- des modules métier installables sous forme d’extensions ;
- des sociétés abonnées ;
- des sociétés non abonnées ;
- des relations commerciales, techniques,fournisseur, hiérarchiques et de sous-traitance ;
- des réseaux d’entreprises sans limite stricte ;
- une récupération d’historique si une société non abonnée devient abonnée plus tard ;
- une gestion fine des utilisateurs, rôles, fonctions, permissions et contextes.

---

## 1. Vision générale

L’application est une **plateforme SaaS modulaire multi-tenant**, orientée principalement vers l’écosystème automobile, mais conçue pour rester extensible à d’autres métiers.

La base (noyau) doit fonctionner comme une **coquille applicative vide**, sans données métier intégrées par défaut.

Les modules métier futurs pourront être ajoutés sous forme d’extensions :

- RH ;
- standards constructeur ;
- standards importateur;
- standards concession;
- planning intelligent ;
- qualité ;
- satisfaction client ;
- boutique en ligne ;
- marketplace ;
- gestion outillage (catalogue,maintenance, achats, fiche produits, location, moteur de recherche) ;
- comptabilité ;
- gestion de caisse ;
- gestion de parc automobile ;
- Location et prêt de véhicules;
- gestion des tickets d’assistance ;
- gestion après-vente automobile ;
- gestion de stock ;
- gestion de références ;
- facturation ;
- proformas ;
- devis ;
- ordres de réparation.

Le socle doit gérer les éléments communs :

- sociétés ;
- utilisateurs ;
- droits ;
- rôles ;
- fonctions ;
- competances ;
- permissions ;
- relations entre sociétés ;
- abonnements ;
- modules ;
- menus ;
- sessions ;
- sécurité ;
- audit ;
- fichiers ;
- emails ;
- documents juridiques ;
- connecteurs inter-modules.

---

## 2. Règle centrale : société enregistrée, société abonnée et accès applicatif

La règle fondamentale du modèle est la suivante :
Société enregistrée dans la base
≠ Société abonnée
≠ Société ayant accès à l’application
Une société peut être présente dans la base de données sans être abonnée.
Une société non abonnée peut exister pour plusieurs raisons :
- elle est cliente d’une société abonnée ;
- elle est fournisseur d’une société abonnée ;
- elle est sous-traitante ;
- elle est partenaire commercial ;
- elle est partenaire technique ;
- elle est vendeur marketplace ;
- elle est acheteur marketplace ;
- elle est organisme externe ;
- elle reçoit des documents par email ;
- elle possède un historique de commandes, documents ou échanges ;
- elle est susceptible de s’abonner plus tard.

Une société non abonnée :

- n’a pas d’accès à l’application ;
- peut avoir  d’utilisateurs pour permetre la communications par email ;
- doit recevoir des documents par email ;
- doit avoir une fiche complète dans la base ;
- doit avoir des relations avec une ou plusieurs sociétés ;
- doit être liée à des commandes, devis, factures ou interventions ;
- doit récupérer son historique si elle souscrit un abonnement ultérieurement.

Une société abonnée :

- possède un accès applicatif ;
- doit être considérée comme tenant actif ;
- doit avoir des utilisateurs ;
- peut souscrire des modules ;
- peut créer des relations avec d’autres sociétés ;
- peut envoyer des documents à des sociétés non abonnées par email ;
- peut créer des fiches de sociétés externes.

---

## 3. Tenant principal

Le **tenant principal est une société abonnée**.

Cependant, toutes les sociétés présentes dans `sav_companies` ne sont pas des tenants actifs.

Il faut donc séparer :

- l’entité société ;
- l’abonnement ;
- l’accès applicatif ;
- les relations commerciales et techniques.

Une concession indépendante seule peut être un tenant complet.
Un groupe de concessions peut avoir des concessions dans plusieurs pays, même si ce cas reste isolé.

## 4. Base de données et conventions

### 4.1 Base unique

La plateforme utilise une **base MariaDB unique pour tous les clients**.

Le cloisonnement des données doit se faire par :

- `company_id` ;
- `tenant_id` lorsque nécessaire ;
- contexte utilisateur actif ;
- rôles ;
- permissions ;
- règles ABAC ;
- modules activés ;
- relations entre sociétés.

### 4.2 Préfixe des tables

Toutes les tables de la base ont un préfixe unique de trois lettres suivi d’un underscore.

Préfixe retenu :
sav_



### 4.3 Suppression logique

La suppression doit être logique.

Chaque table sensible doit prévoir :

created_at
updated_at
deleted_at
created_by
updated_by

La suppression physique doit rester exceptionnelle et réservée au super-admin.

### 4.4 Format des données

Les échanges entre modules doivent se faire en priorité via des structures JSON.

Chaque module doit pouvoir interagir :

- avec le noyau ;
- avec un autre module ;
- avec une application externe ;
- via des connecteurs ;
- via des données structurées en JSON

## 5. Sessions applicatives

Les données utilisateur sont construites à la connexion et stockées en session.

Structure attendue :
$_SESSION['user'] = [];
$_SESSION['society'] = [];
$_SESSION['concession'] = [];
$_SESSION['marque'] = [];
$_SESSION['permissions'] = [];
$_SESSION['security'] = [];

Les sélections actives doivent être disponibles dans :

$_SESSION['user']['actual_concession_id'];
$_SESSION['user']['actual_brand'];
$_SESSION['user']['actual_service_id'];
$_SESSION['user']['actual_team_id'];
$_SESSION['permissions'];



Le contexte actif doit rester stocké uniquement en session a condition de ne pas dégrader les performance ou la securité.


## 6. Sélection active concession / marque

L’utilisateur doit pouvoir sélectionner dans la sidebar :

- une concession ;
- une marque.

Les champs prévus sont :

```html
<select id="sel-concessions"></select>
<select id="sel-marques"></select>
```

Chaque changement doit déclencher un rechargement Ajax des données liées au contexte sélectionné.

Règles :

- le choix de concession est obligatoire après connexion ;
- si l’utilisateur n’a accès qu’à une seule concession, le champ est verrouillé ;
- par défaut, toutes les marques autorisées sont sélectionnées ;
- l’utilisateur peut choisir une seule marque ;
- une marque ne peut être sélectionnée que si la concession la représente ;
- si une concession ne représente qu’une marque, la marque est automatiquement sélectionnée ;
- les données rechargées doivent concerner uniquement le contexte actif.

---

## 7. Administration générale

### 7.1 Super-admin

Le super-admin est l’administrateur global de l’application.

Il peut :

- créer les sociétés dans le système ;
- créer le premier administrateur général de chaque société ;
- autoriser l’accès aux modules métier ;
- gérer les formules souscrites ;
- analyser les logs système ;
- analyser les logs de sécurité ;
- analyser les données statistiques ;
- mettre le site en maintenance ;
- purger les tables ;
- bloquer une entreprise ;
- réactiver un utilisateur désactivé définitivement ;
- accéder à toutes les données.

### 7.2 Administrateur général d’une société

L’administrateur général d’une société :
- vision global de la société (services, equipes, horaires, standards)
- lien avec le constructeur
- lien avec l'importateur
- lien avec la marque
- n’a pas de supérieur hiérarchique dans sa société ;
- donne les accès aux fonctions de chaque module à l'administrateur service
- donne les accès aux fonctions de chaque module à l'administrateur equipe
- administre uniquement la société qu’il représente ;
- peut créer des utilisateurs pour sa société ;
- peut modifier les utilisateurs de sa société ;
- peut bloquer des utilisateurs de sa société ;
- peut attribuer les rôles ;
- peut attribuer les fonctions ;
- peut attribuer les compétences ;
- peut créer des equipes propres à sa société ;
- peut créer des compétences propres à sa société ;
- peut créer des fonctions propres à sa société ;
- peut créer des standars propres à sa société ;
- peut créer des references inventaire propres à sa société (le besoin minimal materiel,formations,compétances....);
- peut créer des rôles propres à sa société ;
- créer ou liée(si société existe dans la base de données) une societé à sa société
- peut gérer les informations de sa société : téléphone, email, logo, coordonnées ;
- peut gérer les relations hiérarchiques entre utilisateurs de sa société,.
- peut gérer les relations hiérarchiques entre sa société et ses sous-traitans
- peut gérer les relations hiérarchiques entre sa société et ses société client
- peux valider certaines operation sensible (enjeux securité ou financier)  executé/demander par autres utilisateurs ou déléguer un un administrateur departement et administrateur service
- valide le panier d'achat

### 7.3 Administrateur departement
 - vision global des service apartenant a son departement
 - accès identique que chaque Administrateur Service
 - administration Administrateur Service

### 7.3 Administrateur Service
- vision global de son service
- ne peut pas voir les autres service
- peux faire tout se que Administrateur equipe fait
- peut créer des utilisateurs pour sa société ;
- peut modifier les utilisateurs de sa société ;
- peut bloquer des utilisateurs de sa société ;
- peut attribuer les rôles ;
- peut attribuer les fonctions ;
- peut attribuer les compétences ;
- peut attribuer les utilisateur à une  équipe ;
- peut attribuer l'amplitude horaire à une équipe ;
- peut attribuer l'amplitude horaire à un utilisateur ;
- déclancher les inventaires
- valider les demandes de chaque utilisateur de son service (congé, achat outils, achat fournitures)
Si l’administrateur général donne l'autorisation, Administrateur Service peut également:
- lien avec l'importateur
- lien avec la marque
- peut créer des equipes propres à sa société ;
- peut créer des compétences propres à sa société ;
- peut créer des fonctions propres à sa société ;
- peut créer des rôles propres à sa société ;
- créer ou liée(si société existe dans la base de données) une societé à sa société
- peux valider certaines operation sensible (enjeux securité ou financier) executé/demander par autres utilisateurs
- avoir access a toutes les fonctions que l'administarteur générale autorise pour chaque module
- valide le panier d'achat
- supervise les fournisseurs
- supervise les sous-traitans

### 7.4 Administrateur equipe
- superviser son equipe
- vision global de l'equipe
- géré les absences et les retards
- informer son administrateur service (faire des requettes)
- gérer les demandes utilisateurs 
- gérer les commandes futures
- visualise le panier d'achat
- créer une demande d'achat  


### 7.5 Utilisateur standard
- consulte les données le concernant
- ne peut pas voir les autres utilisateur
- créer une demande pour les congés
- créer une demande materiel
- plus tard ajouter des articles dans le panier (panier géneral par société)

Chaque utilisateur, sauf l’administrateur général, doit avoir un supérieur hiérarchique.

Un utilisateur peut être administré :

- par le super-admin ;
- par l’administrateur général de sa société ;
- par l’administrateur service ;
- par l’administrateur equipe
- de manière limitée par lui-même.

---

## 8. Sociétés

### 8.1 Table `sav_companies`

La table `sav_companies` contient toutes les sociétés connues par la plateforme.

Elle inclut :

- sociétés abonnées ;
- sociétés non abonnées ;
- prospects ;
- clients externes ;
- fournisseurs ;
- sous-traitants ;
- partenaires ;
- constructeurs ;
- marques ;
- importateurs ;
- groupes de concessions ;
- concessions ;
- organismes de contrôle ;
- loueurs ;
- fabricants d’outils ;
- distributeurs ;
- vendeurs marketplace ;
- acheteurs marketplace.

Champs recommandés :

`com_id` bigint(20) UNSIGNED NOT NULL,
  `com_uuid` char(36) NOT NULL,
  `com_code` char(15)  NULL,
  `com_name` varchar(191) NOT NULL,
  `com_legal_name` varchar(191) DEFAULT NULL,
  `com_short_name` varchar(60) DEFAULT NULL,
  `com_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `com_siret` varchar(20) DEFAULT NULL,
  `com_vat_number` varchar(20) DEFAULT NULL,
  `com_address` varchar(255) DEFAULT NULL,
  `com_postal_code` varchar(10) DEFAULT NULL,
  `com_city` varchar(100) DEFAULT NULL,
  `com_zipcode` varchar(10) DEFAULT NULL,
  `com_country` varchar(60) DEFAULT 'France',
  `com_phone` varchar(30) DEFAULT NULL,
  `com_email` varchar(191) DEFAULT NULL,
  `com_website` varchar(255) DEFAULT NULL,
  `com_logo_url` varchar(255) DEFAULT NULL,
  `com_is_active` tinyint(1) NOT NULL DEFAULT 1,
  `com_status` varchar(20) NOT NULL DEFAULT 'active',
  `com_is_holding` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1 si cette entreprise est une holding (société mère)',
  `com_parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `com_holding_id` bigint(20) UNSIGNED DEFAULT NULL,
  `com_created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by_company_id` varchar(30) DEFAULT NULL,
  `com_updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `com_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `com_updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `com_deleted_at` datetime DEFAULT NULL,
  `com_deleted_by` bigint(20) UNSIGNED DEFAULT NULL

Remarque importante :

`created_by_company_id` permet de savoir quelle société a créé la fiche d’une société externe ou non abonnée.

### 8.2 Statuts société

Le statut général d’une société peut être :

active
inactive
blocked
archived
deleted

Le statut d’abonnement est séparé et ne doit pas être confondu avec le statut société.

---

## 9. Types de sociétés

Les types de sociétés sont administrables.

Une société peut avoir plusieurs types.

Exemples :

- constructeur ;
- marque ;
- importateur ;
- groupe de concessions ;
- concession ;
- réseau de centres automobiles ;
- indépendant ;
- organisme de contrôle ;
- maintenance industrielle ;
- loueur de véhicules ;
- fabricant d’outils ;
- distributeur d’outils ;
- contrôle technique ;
- fournisseur ;
- sous-traitant ;
- partenaire technique ;
- partenaire commercial ;
- vendeur marketplace ;
- acheteur marketplace,
-MRA

### 9.1 `sav_company_types`

```sql
id
code
name
description
icone
logo
status
created_at
updated_at
deleted_at

### 9.2 `sav_company_type_assignments`

```sql
id
company_id
company_type_id
start_at
end_at
status
created_at
updated_at
archived_at
```

Exemples :

```text
Une société peut être importateur et concession.
Une marque est une société avec le type marque.
Un vendeur marketplace est une société avec le type vendeur_marketplace.
Un constructeur peut être vendeur_marketplace.
Un importateur peut être vendeur_marketplace.
Une marque peut être vendeur_marketplace.
Une concession peut être vendeur_marketplace.
Un fabricun d'outils peut être vendeur_marketplace.
Un distributeur d'outil peut être vendeur_marketplace.
Un sous-traitant non abonné est une société avec le type sous_traitant.


---

## 10. Abonnements et tenants

### 10.1 `sav_company_subscriptions`

Cette table décrit l’état d’abonnement d’une société.

```sql
id
company_id
subscription_plan_id
subscription_status
payment_status
start_at
end_at
created_at
updated_at
```

Statuts possibles :

```text
none
trial
active
suspended
expired
cancelled
```

Une société non abonnée possède :

```text
subscription_status = none
```

### 10.2 `sav_subscription_plans`

Les formules peuvent être personnalisées par client.

```sql
id
name
description
price_mode
functions
status
created_at
updated_at
```

### 10.3 `sav_tenants`

Cette table représente les sociétés ayant un espace applicatif actif.

```sql
id
company_id
subscription_id
status
created_at
updated_at
blocked_at
blocked_reason
```

Une société peut exister dans `sav_companies` sans exister dans `sav_tenants`.

---

## 11. Relations entre sociétés

Chaque société peut créer des liens avec d’autres sociétés à l’infini.

Une relation peut être :

- commerciale ;
- technique ;
- hiérarchique ;
- contractuelle ;
- réseau ;
- sous-traitance ;
- marketplace ;
- constructeur / marque ;
- constructeur / importateur ;
- importateur / marque ;
- importateur / concession ;
- groupe / concession ;
- concession / marque.

Une même société peut appartenir à plusieurs réseaux en même temps.

Exemple :

```text
Une concession représente trois marques issues de trois importateurs différents.
```

Une entreprise peut être sous-traitante de plusieurs sociétés abonnées ou non abonnées.

Une société non abonnée peut avoir des relations avec plusieurs sociétés abonnées.

### 11.1 `sav_company_relationship_types`

```sql
id
code
name
description
is_directional
status
created_at
updated_at
```

Exemples de types :

```text
constructeur_possede_marque
marque_appartient_constructeur
importateur_distribue_marque
concession_represente_marque
groupe_possede_concession
societe_appartient_reseau
client_de
fournisseur_de
sous_traitant_de
partenaire_commercial_de
partenaire_technique_de
vendeur_pour
acheteur_de
prestataire_de
organisme_controle_de
```

### 11.2 `sav_company_relationships`

```sql
id
source_company_id
target_company_id
relationship_type_id
start_at
end_at
status
created_by_company_id
created_by_user_id
created_at
updated_at
archived_at
```

Règles :

- les relations doivent avoir une date de début ;
- elles peuvent avoir une date de fin ;
- elles doivent être historisées ;
- les anciennes relations doivent être conservées en archive ;
- une société peut avoir un nombre illimité de relations ;
- une société non abonnée peut être liée à plusieurs sociétés ;
- une société abonnée peut créer des relations avec des sociétés abonnées ou non abonnées.

---

## 12. Représentation marque / constructeur / importateur / concession

Une marque est une société à part entière.

Un constructeur peut posséder plusieurs marques.

Une marque peut changer de constructeur dans le temps.

Un importateur peut importer plusieurs marques de plusieurs constructeurs différents. Il peut être privé ou une extension du  constructeur direct

Une concession peut représenter plusieurs marques venant de plusieurs importateurs différents. Donc avoir pusieurs régles (standars).

Pour gérer précisément cette réalité, une table spécialisée est recommandée.

### 12.1 `sav_company_brand_representations`

```sql
id
concession_company_id
brand_company_id
importer_company_id
constructor_company_id
start_at
end_at
status
created_by_user_id
created_at
updated_at
archived_at
```

Cette table sert notamment à :

- alimenter le sélecteur de marques ;
- vérifier qu’une concession représente bien une marque ;
- filtrer les données métier par concession et marque ;
- historiser les changements de représentation ;
- conserver les anciennes représentations en archive.

---

## 13. Hiérarchie interne des sociétés

La hiérarchie interne n’est pas commune à toutes les sociétés.

Les niveaux doivent être libres.

Le choix retenu est d’utiliser des tables séparées :


sav_departments
sav_sectors
sav_services
sav_teams


Les relations entre niveaux doivent rester flexibles.

Une grande entreprise peut avoir :

Organisation
└── Département
    └── Secteur
        └── Service
            └── Équipe
```

Une petite entreprise peut avoir :

```text
Organisation
    └── Service
        └── Équipe
```

Une équipe peut être liée à plusieurs services pour garder de la flexibilité, même si en pratique une équipe agit souvent pour un seul service.

### 13.1 `sav_departments`

```sql
id
company_id
name
description
code
status
created_at
updated_at
deleted_at
```

### 13.2 `sav_sectors`

```sql
id
company_id
name
description
code
status
created_at
updated_at
deleted_at
```

### 13.3 `sav_services`

```sql
id
company_id
name
decsription
code
status
created_at
updated_at
deleted_at
```

Exemples de services :

- mécanique ;
- carrosserie ;
- BRC ;
- peinture ;
- PRA ;
- location véhicule ;
- service technique ;
- service administratif.

### 13.4 `sav_teams`

```sql
id
company_id
name
code
status
created_at
updated_at
deleted_at
```

### 13.5 Tables de liaison hiérarchique

```text
sav_department_sectors
sav_department_services
sav_sector_services
sav_service_teams
sav_team_services
```

Champs communs recommandés :

```sql
id
company_id
parent_id
child_id
start_at
end_at
status
created_at
updated_at
archived_at
```

---

## 14. Utilisateurs

Un utilisateur possède un seul compte global.

L’email est unique dans toute l’application.

Un utilisateur ne peut pas avoir plusieurs comptes avec le même email dans des sociétés différentes.

Un contact externe peut devenir plus tard un utilisateur avec accès à la plateforme.

Statuts utilisateur :

```text
active
inactive
suspended
security_blocked
deleted
```

La suppression est logique.

### 14.1 `sav_users`

```sql
 `use_id` bigint(20) UNSIGNED NOT NULL COMMENT 'PK auto-incrémenté',
  `use_uuid` char(36) NOT NULL COMMENT 'UUID v4 public immuable',
  `use_username` varchar(100) DEFAULT NULL,
  `use_email` varchar(191) NOT NULL COMMENT 'Email normalisé lowercase',
  `use_password_hash` varchar(255) NOT NULL COMMENT 'Hash Argon2id',
  `use_password_changed_at` datetime DEFAULT NULL,
  `use_must_change_password` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `use_password_expires_at` datetime DEFAULT NULL,
  `use_password_history_hash` longtext DEFAULT NULL COMMENT 'JSON : anciens hash',
  `use_email_verified_at` datetime DEFAULT NULL,
  `use_email_verification_token` varchar(64) DEFAULT NULL,
  `use_email_verification_sent_at` datetime DEFAULT NULL,
  `use_email_verification_attempts` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `use_2fa_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `use_2fa_secret` varchar(255) DEFAULT NULL,
  `use_2fa_backup_codes` longtext DEFAULT NULL COMMENT 'JSON : codes backup',
  `use_2fa_enabled_at` datetime DEFAULT NULL,
  `use_2fa_method` varchar(10) DEFAULT NULL COMMENT 'app|sms|email',
  `use_lastname` varchar(100) DEFAULT NULL,
  `use_firstname` varchar(100) DEFAULT NULL,
  `use_civility` varchar(10) DEFAULT NULL,
  `use_phone` varchar(30) DEFAULT NULL,
  `use_mobile` varchar(30) DEFAULT NULL,
  `use_photo_url` varchar(255) DEFAULT NULL,
  `use_employee_number` varchar(50) DEFAULT NULL,
  `use_department` varchar(100) DEFAULT NULL,
  `use_job_title` varchar(100) DEFAULT NULL,
  `use_address_street` varchar(255) DEFAULT NULL,
  `use_address_city` varchar(100) DEFAULT NULL,
  `use_address_zipcode` varchar(10) DEFAULT NULL,
  `use_address_country` varchar(60) DEFAULT 'France',
  `use_birthdate` date DEFAULT NULL,
  `use_birthplace` varchar(120) DEFAULT NULL,
  `use_ssn` varchar(255) DEFAULT NULL,
  `use_iban` varchar(255) DEFAULT NULL,
  `use_bic` varchar(20) DEFAULT NULL,
  `use_bank_name` varchar(120) DEFAULT NULL,
  `use_emergency_name` varchar(120) DEFAULT NULL,
  `use_emergency_phone` varchar(30) DEFAULT NULL,
  `use_hire_date` date DEFAULT NULL,
  `use_contract_type` varchar(30) DEFAULT NULL,
  `use_manager_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Auto-référence manager',
  `use_is_active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `use_is_locked` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `use_locked_until` datetime DEFAULT NULL,
  `use_locked_reason` varchar(255) DEFAULT NULL,
  `use_active_company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `use_active_brand_id` bigint(20) UNSIGNED DEFAULT NULL,
  `use_terms_accepted_version` varchar(20) DEFAULT NULL,
  `use_terms_accepted_at` datetime DEFAULT NULL,
  `use_gdpr_anonymized` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `use_failed_login_attempts` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `use_last_login_at` datetime DEFAULT NULL,
  `use_last_login_ip` varbinary(16) DEFAULT NULL COMMENT 'IPv4/IPv6 packed',
  `use_last_user_agent` varchar(255) DEFAULT NULL,
  `use_locale` varchar(10) DEFAULT NULL,
  `use_timezone` varchar(100) DEFAULT 'Europe/Paris',
  `use_is_system` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `use_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `use_updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `use_deleted_at` datetime DEFAULT NULL COMMENT 'Soft delete',
  `use_deleted_reason` varchar(255) DEFAULT NULL,
  `use_avatar_color` varchar(7) DEFAULT NULL,
  `use_invitation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `use_contract_file` varchar(255) DEFAULT NULL COMMENT 'Chemin contrat de travail',
  `use_role_rh` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Est admin RH',
  `use_seniority_date` date DEFAULT NULL COMMENT 'Date début ancienneté',
  `use_updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `use_deleted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `use_created_by` bigint(20) UNSIGNED DEFAULT NULL
```

### 14.2 `sav_user_company_memberships`

Cette table indique dans quelles sociétés un utilisateur a un accès ou une appartenance.

```sql
id
user_id
company_id
start_at
end_at
status
created_by_user_id
created_at
updated_at
deleted_at
```

Une société non abonnée peut exister sans aucune ligne active dans cette table.

### 14.3 Appartenance aux structures internes

Un utilisateur peut appartenir à plusieurs équipes dans la même société.

Un utilisateur peut appartenir à plusieurs services dans la même société.

Tables recommandées :

```text
sav_user_departments
sav_user_services
sav_user_teams
```

Champs communs :

```sql
id
user_id
company_id
department_id
service_id
team_id
start_at
end_at
status
created_at
updated_at
archived_at
```

### 14.4 Hiérarchie utilisateur

Chaque utilisateur, sauf l’administrateur général de la société, doit avoir un supérieur hiérarchique.

Table recommandée :

```text
sav_user_hierarchy
```

```sql
id
company_id
user_id
manager_user_id
start_at
end_at
status
created_at
updated_at
archived_at
```

---

## 15. Fonctions, rôles et permissions

La distinction validée est :

```text
Fonction = métier réel
Rôle = droit applicatif
Permission = action technique précise
```

Exemple :

```text
Fonction : technicien
Rôle : responsable_atelier
Permission : repair_order.create
```

### 15.1 Fonctions métier

Les fonctions peuvent être :

- globales ;
- propres à une marque ;
- propres à un importateur ;
- propres à un groupe de concessions ;
- propres à une concession ;
- propres à une marque d’outils ;
- propres à un organisme de contrôle ;
- propres à une société ;
- propres à un service technique.

Fonctions génériques initiales :

- PDG ;
- chef de service ;
- chef de departement
- chef d’équipe ;
- technicien ;
- expert technique ;
- carrossier ;
- peintre ;
- réceptionnaire ;
- administrateur de groupe de concessions ;
- administrateur marque.

### 15.2 `sav_functions`

```sql
id
code
name
description
owner_company_id
company_type_id
status
created_at
updated_at
deleted_at
```

### 15.3 `sav_user_functions`

```sql
id
user_id
company_id
function_id
brand_company_id
concession_company_id
service_id
team_id
start_at
end_at
status
created_at
updated_at
archived_at
```

### 15.4 Rôles applicatifs

Les rôles sont toujours liés à un contexte.

Ils peuvent être différents selon :

- le type de société ;
- la société ;
- la marque ;
- l’importateur ;
- la concession ;
- le groupe ;
- le service ;
- le module.

### 15.5 `sav_roles`

```sql
id
code
name
description
owner_company_id
company_type_id
module_id
status
created_at
updated_at
deleted_at
```

### 15.6 Permissions

Les permissions doivent être fines.

Exemples :

```text
user.create
user.update
user.block
company.create
company.update
company.block
module.access
invoice.create
invoice.cancel
repair_order.create
stock.update
marketplace.order.create
marketplace.seller.manage
```

### 15.7 `sav_permissions`

```sql
id
code
description
module_id
status
created_at
updated_at
```

### 15.8 `sav_role_permissions`

```sql
id
role_id
permission_id
effect
created_at
updated_at
```

Valeurs possibles pour `effect` :

```text
allow
deny
```

Règle obligatoire :

```text
deny > allow
```

En cas de conflit, le refus est catégorique et doit être journalisé.

### 15.9 `sav_user_context_roles`

Table centrale des rôles contextuels.

```sql
id
user_id
role_id
company_id
brand_company_id
concession_company_id
department_id
sector_id
service_id
team_id
module_id
start_at
end_at
status
created_at
updated_at
archived_at
```

Cette table permet de dire :

```text
Un utilisateur peut être administrateur dans une société,
chef d’équipe dans une concession,
et simple utilisateur dans une autre concession.
```

---

## 16. ABAC — règles d’accès contextuelles

Le système doit combiner :

- RBAC : droits par rôle ;
- ABAC : droits selon les attributs et le contexte.

Les permissions doivent dépendre d’attributs dynamiques.

Exemples d’attributs :

- société active ;
- concession active ;
- marque active ;
- service actif ;
- équipe active ;
- fonction utilisateur ;
- certification valide ;
- niveau de compétence ;
- module actif ;
- relation entre sociétés ;
- statut d’abonnement ;
- type de société.

### 16.1 `sav_access_policies`

```sql
id
code
name
module_id
permission_id
effect
status
created_at
updated_at
```

### 16.2 `sav_access_policy_conditions`

```sql
id
policy_id
attribute
operator
value
created_at
updated_at
```

Exemple de règle :

```text
L’utilisateur peut créer un ordre de réparation uniquement si :
- il appartient à la concession active ;
- il a accès à la marque active ;
- le module après-vente est actif ;
- sa fonction ou son rôle l’autorise ;
- il fait partie appartien à l'entreprise ;
- il possède les certifications obligatoires si elles sont exigées.
```

---

## 17. Modules, extensions et menus

Les modules sont activés par le super-admin selon les formules souscrites.

Les formules sont personnalisées par client.

Un module peut être actif pour une société et inactif pour une autre.

Un module peut imposer :

- ses propres tables ;
- ses propres permissions ;
- ses propres menus ;
- ses propres connecteurs ;
- ses extensions de tables existantes.
 mais ne modifie jamais la structure de base.

### 17.1 `sav_modules`

```sql
id
code
name
description
version
is_core
status
created_at
updated_at
```

Modules futurs :

- RH ;
- standards ;
- planning intelligent ;
- qualité ;
- satisfaction client ;
- boutique en ligne ;
- marketplace ;
- gestion outillage ;
- comptabilité ;
- caisse ;
- parc automobile ;
- tickets d’assistance ;
- après-vente automobile ;
- stock ;
- références.

### 17.2 `sav_company_modules`

```sql
id
company_id
module_id
subscription_plan_id
activated_by_user_id
start_at
end_at
status
created_at
updated_at
```

### 17.3 Menus dynamiques

Les menus doivent dépendre :

- des modules actifs ;
- des permissions ;
- du contexte utilisateur ;
- de la société ;
- de la concession ;
- de la marque.

Tables :

```text
sav_menus
sav_menu_items
```

### 17.4 `sav_menu_items`

```sql
id
menu_id
module_id
parent_id
label
route
icon
position
required_permission_id
status
created_at
updated_at
```

Les menus peuvent être chargés depuis un fichier et/ou depuis la base.

---

## 18. Compétences, niveaux, certifications et standards

Les compétences peuvent être :

- globales ;
- propres à une concession ;
- propres à un importateur ;
- propres à une marque ;
- propres à une société ;
- définies dans des standards.

### 18.1 Compétences initiales

- Vidange DSG ;
- embrayages boîte mécanique ;
- réparation boîte automatique ;
- diagnostic bruit ;
- diagnostic électronique ;
- climatisation R134a ;
- climatisation R1234yf ;
- climatisation R744 ;
- courroie de distribution ;
- chaîne de distribution ;
- remplacement moteur ;
- freins céramique.

### 18.2 `sav_skills`

```sql
id
code
name
description
owner_company_id
status
created_at
updated_at
deleted_at
```

### 18.3 `sav_skill_levels`

```sql
id
name
rank
status
```

Exemple :

```text
1 = débutant
2 = autonome
3 = confirmé
4 = expert
```

### 18.4 `sav_user_skills`

```sql
id
user_id
skill_id
skill_level_id
company_id
brand_company_id
issued_by_company_id
start_at
end_at
status
created_at
updated_at
archived_at
```

### 18.5 Certifications initiales

- R134a ;
- B2VL ;
- BCL ;
- B2TL ;
- soudure aluminium.

### 18.6 `sav_certifications`

```sql
id
code
name
description
prérequis
owner_company_id
status
created_at
updated_at
deleted_at
```

### 18.7 `sav_user_certifications`

```sql
id
user_id
certification_id
company_id
brand_company_id
issuer_company_id
issued_at
expires_at
status
proof_file_id
created_at
updated_at
archived_at
```

Une certification peut être délivrée par :

- une marque ;
- un organisme ;
- une société ;
- un administrateur.

Elle peut avoir :

- une date d’expiration ;
- une preuve d'authenticité ;
- une traçabilité ;
- un lien avec une exigence de conformité.

### 18.8 Standards versionnés

Les standards doivent être versionnés.

```text
sav_standards
sav_standard_versions
sav_standard_requirements
```

#### `sav_standards`

```sql
id
code
name
owner_company_id
standard_type
status
created_at
updated_at
```

#### `sav_standard_versions`

```sql
id
standard_id
version
valid_from
valid_to
status
created_at
updated_at
```

#### `sav_standard_requirements`

```sql
id
standard_version_id
requirement_type
skill_id
certification_id
minimum_skill_level_id
mandatory
created_at
updated_at
```

Une concession peut être évaluée par rapport à plusieurs standards en même temps.

---

## 19. Horaires, amplitudes et exceptions

Les horaires doivent être définis au niveau :

- société ;
- service ;
- équipe.

Ils peuvent varier :

- d’une entreprise à une autre ;
- d’un service à un autre ;
- d’une équipe à une autre.

Il faut gérer :

- jours fériés ;
- fermetures exceptionnelles ;
- horaires d’été ;
- inventaires ;
- absences
- congées payés
- exceptions ponctuelles.

### 19.1 `sav_working_hours`

```sql
id
company_id
scope_type
scope_id
day_of_week
opens_at
closes_at
is_closed
created_at
updated_at
```

Valeurs possibles de `scope_type` :

```text
company
service
departement
team
```

### 19.2 `sav_working_hour_exceptions`

```sql
id
company_id
scope_type
scope_id
date
opens_at
closes_at
is_closed
reason
created_at
updated_at
```

---

## 20. Contacts, emails et notifications

Chaque société doit disposer d’une liste de contacts.

Un contact peut être :

- un utilisateur de la table `sav_users` ;
- une adresse email externe.

Les contacts servent à envoyer des emails groupés déclenchés par des actions.

Exemples :

- tentative de connexion frauduleuse ;
- notification administrateur site ;
- notification responsable sécurité ;
- envoi de documents à une société non abonnée ;
- communication avec un sous-traitant ;
- communication avec un client externe ;
- notifications marketplace.

### 20.1 `sav_company_contacts`

```sql
id
company_id
user_id
external_email
firstname
lastname
contact_type_id
status
created_at
updated_at
deleted_at
```

Règle :

```text
Soit user_id est renseigné,
soit external_email est renseigné.
```

### 20.2 `sav_contact_types`

```sql
id
code
name
description
status
```

Exemples :

- sécurité ;
- direction ;
- facturation ;
- RH ;
- technique ;
- qualité ;
- juridique ;
- marketplace ;
- sous-traitance.

### 20.3 `sav_email_templates`

```sql
id
code
subject
body
status
created_at
updated_at
```

### 20.4 `sav_email_logs`

```sql
id
template_id
sender_company_id
recipient_company_id
recipient_user_id
recipient_email
event_type
subject
body
sent_at
status
created_at
```

Tous les emails envoyés doivent être historisés.

---

## 21. Documents, commandes et historique des sociétés non abonnées

Une société non abonnée peut recevoir des documents par email de la part d’une société abonnée.

Elle peut aussi avoir un historique de :

- commandes ;
- devis ;
- factures ;
- proformas ;
- documents techniques ;
- interventions ;
- tickets ;
- demandes de sous-traitance ;
- échanges marketplace.

Règle obligatoire :

```text
Les documents et opérations doivent référencer les sociétés concernées par leur company_id,
même si une des sociétés n’est pas abonnée.
```

Exemple de structure commune pour les modules commerciaux :

```sql
issuer_company_id
receiver_company_id
seller_company_id
buyer_company_id
billing_company_id
shipping_company_id
```

Cela permet à une société non abonnée de récupérer son historique si elle devient abonnée.

---

## 22. Marketplace future

La future marketplace doit utiliser le même modèle de sociétés.

Un vendeur est une société.

Un acheteur est une société.

Un client marketplace peut être abonné ou non abonné.

Un vendeur peut avoir ses clients.

Une société peut être :

- vendeur pour certains clients ;
- acheteur auprès de certains fournisseurs ;
- sous-traitant pour plusieurs sociétés ;
- fournisseur pour plusieurs sociétés ;
- client de certains société abonnée ;
- client de certains société non abonnée.

Tables futures probables :

```text
sav_marketplace_sellers
sav_marketplace_customers
sav_marketplace_orders
sav_marketplace_order_items
sav_marketplace_catalogs
sav_marketplace_products
```

Règle :

```text
La marketplace ne doit pas créer un deuxième modèle de clients.
Elle doit réutiliser sav_companies et sav_company_relationships.
```

---

## 23. Fichiers stockés en base

Les fichiers doivent être stockés en base de données.

Table centrale :

```text
sav_files
```

```sql
id
uuid
filename
mime_type
size_bytes
content_blob
checksum
created_by_user_id
created_at
updated_at
deleted_at
```

Utilisations :

- logo société ;
- medias société;
- medias utilisateurs;
- preuves de certification ;
- documents légaux ;
- documents envoyés par email ;
- pièces jointes ;
- documents marketplace ;
- médias marque ;
- documents techniques.

---

## 24. Documents juridiques, CGV et CGU

Chaque société peut :

- avoir ses propres CGV ;
- avoir ses propres CGU ;
- être liée à des conditions imposées par une marque ;
- être liée à des conditions imposées par un importateur ;
- combiner des conditions générales imposées avec ses propres conditions.

Les documents doivent être versionnés.

Les acceptations utilisateur doivent être historisées.

### 24.1 `sav_legal_documents`

```sql
id
company_id
brand_company_id
document_type
title
version
content
valid_from
valid_to
status
created_at
updated_at
```

Types possibles :

```text
cgv
cgu
privacy_policy
contract
brand_terms
marketplace_terms
```

### 24.2 `sav_company_legal_document_links`

```sql
id
company_id
legal_document_id
priority
is_mandatory
status
created_at
updated_at
```

### 24.3 `sav_user_legal_acceptances`

```sql
id
user_id
legal_document_id
version
accepted_at
ip_address
user_agent
```

---

## 25. Sécurité, authentification et blocages

Toutes les tentatives de connexion doivent être enregistrées.

Informations à conserver :

- utilisateur si identifié ;
- email tenté ;
- IP ;
- navigateur ;
- user-agent ;
- appareil ;
- succès ou échec ;
- raison de l’échec.

Le blocage doit être progressif jusqu’à la désactivation de l’utilisateur concerné.

Règles :

- un blocage soft peut être levé par un superviseur ou administrateur général autorisé ;
- une désactivation complète doit être réactivée uniquement par le super-admin ;
- le nombre d’échecs est paramétrable dans `app.php` ;
- la double authentification n’est pas prévue au début.

### 25.1 `sav_login_attempts`

```sql
id
user_id
email_attempted
ip_address
user_agent
browser
device
success
failure_reason
created_at
```

### 25.2 `sav_security_blocks`

```sql
id
user_id
ip_address
block_level
reason
starts_at
ends_at
status
created_at
updated_at
```

---

## 26. Logs, audit et archivage

Les logs doivent avoir des niveaux :

```text
info
warning
security
critical
```

L’audit concerne les tables sensibles.

Il n’est pas obligatoire de stocker systématiquement ancienne valeur et nouvelle valeur.

Il faut pouvoir savoir :

- qui a fait l’action ;
- quand ;
- comment ;
- où ;
- dans quel contexte ;
- depuis quelle IP ;
- avec quel navigateur.

### 26.1 `sav_system_logs`

```sql
id
level
category
message
user_id
company_id
ip_address
user_agent
created_at
```

### 26.2 `sav_audit_logs`

```sql
id
user_id
company_id
action
target_table
target_id
reason
ip_address
user_agent
metadata_json
created_at
```

`metadata_json` est optionnel et peut contenir des informations supplémentaires lorsque nécessaire.

### 26.3 `sav_log_retention_policies`

La durée de conservation des logs doit être paramétrable.

Les logs doivent pouvoir être archivés dans une base séparée.

```sql
id
log_type
retention_days
archive_enabled
archive_database_name
status
created_at
updated_at
```

---

## 27. Pays, devises, TVA et fuseaux horaires

L’application ne gère pas le multilingue au départ.

Elle doit cependant prévoir :

- plusieurs pays ;
- plusieurs devises ;
- plusieurs taux de TVA ;
- plusieurs fuseaux horaires.

Tables recommandées :

```text
sav_countries
sav_currencies
sav_tax_rates
sav_timezones
```

---

## 28. Tables centrales prioritaires

Les tables prioritaires du noyau sont :

```text
sav_companies
sav_company_types
sav_company_type_assignments
sav_company_subscriptions
sav_subscription_plans
sav_tenants
sav_company_relationship_types
sav_company_relationships
sav_company_brand_representations
sav_users
sav_user_company_memberships
sav_user_hierarchy
sav_departments
sav_sectors
sav_services
sav_teams
sav_functions
sav_user_functions
sav_roles
sav_permissions
sav_role_permissions
sav_user_context_roles
sav_access_policies
sav_access_policy_conditions
sav_modules
sav_company_modules
sav_menus
sav_menu_items
sav_skills
sav_skill_levels
sav_user_skills
sav_certifications
sav_user_certifications
sav_standards
sav_standard_versions
sav_standard_requirements
sav_working_hours
sav_working_hour_exceptions
sav_company_contacts
sav_contact_types
sav_email_templates
sav_email_logs
sav_files
sav_legal_documents
sav_company_legal_document_links
sav_user_legal_acceptances
sav_login_attempts
sav_security_blocks
sav_system_logs
sav_audit_logs
sav_log_retention_policies
```

---

## 29. Schéma logique simplifié

```text
sav_companies
    ├── sav_company_type_assignments
    ├── sav_company_subscriptions
    ├── sav_tenants
    ├── sav_company_relationships
    ├── sav_company_brand_representations
    ├── sav_company_modules
    ├── sav_departments
    ├── sav_services
    ├── sav_teams
    ├── sav_company_contacts
    └── sav_legal_documents

sav_users
    ├── sav_user_company_memberships
    ├── sav_user_hierarchy
    ├── sav_user_functions
    ├── sav_user_context_roles
    ├── sav_user_skills
    ├── sav_user_certifications
    └── sav_user_legal_acceptances

sav_roles
    └── sav_role_permissions
        └── sav_permissions

sav_modules
    ├── sav_company_modules
    ├── sav_permissions
    └── sav_menu_items

sav_company_relationships
    └── permet les liens commerciaux, techniques, réseaux, marketplace et sous-traitance
```

---

## 30. Règles d’évolution de la base

Chaque nouvelle table métier doit être reliée aux tables centrales par les identifiants pertinents.

Tables centrales à réutiliser :

- `sav_users` ;
- `sav_companies` ;
- `sav_modules` ;
- `sav_files` ;
- `sav_permissions` ;
- `sav_company_relationships` ;
- `sav_company_brand_representations`.

Pour chaque évolution concernant les utilisateurs ou les sociétés, une nouvelle table doit relier :

- l’utilisateur ;
- la société ;
- la marque si nécessaire ;
- la concession si nécessaire ;
- le module si nécessaire ;
- la donnée métier concernée.

Règle importante :

```text
Ne jamais créer un modèle parallèle de clients, fournisseurs ou sous-traitants.
Tout doit revenir à sav_companies.
```

---

## 31. Architecture applicative

L’architecture cible est une architecture **MVC modulaire**.

Chaque module doit disposer de connecteurs permettant :

- l’interconnexion avec les autres modules ;
- l’interconnexion avec des applications externes ;
- l’échange de données JSON ;
- l’utilisation des tables centrales ;
- l’ajout de tables d’extension si nécessaire.

Technologies prévues :

- PHP natif ;
- MariaDB ;
- AdminLTE ;
- Bootstrap ;
- PHPMailer ;
- TCPDF ;
- Composer ;
- DataTables ;
- Ajax ;
- jQuery ;
- Dolibarr si besoin pour inspiration graphique moderne.

---

## 32. Inspirations

Solutions d’inspiration :

- Dolibarr ;
- Sage ;
- Factorial RH ;
- Mecaplaning ;
- CarBase DMS ;
- Cros DMS ;
- IceHRM ;
- Amazon Seller ;
- Cdiscount Marketplace ;
- Prestashop.

---

## 33. API externe et recssources
BAN https://adresse.data.gouv.fr/outils/telechargements


## 34. Synthèse finale du modèle

Le modèle central repose sur une séparation stricte entre :

```text
1. La société connue dans la base
2. La société abonnée
3. Le tenant actif
4. L’utilisateur ayant accès
5. La relation entre sociétés
6. Les modules activés
7. Les droits contextuels
8. L’historique commercial et technique
```

La société est l’entité centrale, mais elle n’est pas obligatoirement abonnée.

Une société non abonnée peut :

- être enregistrée ;
- recevoir des documents ;
- être liée à plusieurs sociétés ;
- avoir un historique ;
- être cliente, fournisseur, sous-traitante ou partenaire ;
- devenir abonnée plus tard ;
- récupérer son historique existant.

Cette règle rend le système compatible avec :

- un ERP modulaire ;
- un DMS automobile ;
- un CRM ;
- un réseau de concessions ;
- un réseau de sous-traitants ;
- une marketplace ;
- une plateforme SaaS multi-tenant.

---

## 35. Prochaine étape recommandée

La prochaine étape technique consiste à produire le **script SQL MariaDB initial** pour les tables du noyau, dans cet ordre :

1. sociétés ;
2. types de sociétés ;
3. abonnements et tenants ;
4. relations entre sociétés ;
5. utilisateurs ;
6. hiérarchie interne ;
7. fonctions, rôles et permissions ;
8. modules et menus ;
9. compétences, certifications et standards ;
10. sécurité, logs et audit ;
11. fichiers, emails et documents juridiques.
