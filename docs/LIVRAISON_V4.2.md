# AUTOSAV v4.2 - Livraison Hostinger

## Objet

Cette livraison corrige les défauts bloquants identifiés dans v4.1 et prépare
un paquet déployable sur Hostinger.

## Corrections intégrées

- `composer.lock` racine généré depuis le `composer.json` applicatif correct.
- Autoload PSR-4 régénéré pour `Core/` et `Modules/`.
- Dépendances de production réduites à PHPMailer, TCPDF et Monolog.
- Dépendances orphelines Doctrine DBAL et FastRoute retirées.
- Dépendances de développement PHPUnit absentes du dossier `vendor/` livré.
- Extensions PHP requises déclarées dans `composer.json`.
- Assets frontend publiés dans `public/assets/vendor/`.
- Références du thème alignées avec les assets réellement livrés.
- Implémentation de chiffrement déplacée vers son chemin PSR-4 réel.
- Générateur SweetAlert déplacé vers son chemin PSR-4 réel.
- Fichiers Logger historiques non référencés retirés.
- Sessions, caches et journaux purgés avant empaquetage.
- Préflight production corrigé : les erreurs de configuration sont maintenant
  rapportées en JSON avec un code shell `1`.

## Contrôles exécutés

- Lint PHP applicatif hors `vendor/` : `454/454`.
- Tests unitaires et statiques : `13/13`.
- Validation Composer stricte : succès.
- Contrôle des exigences plateforme Composer : succès avec PHP `8.2.12`.
- Simulation `composer install --no-dev --optimize-autoloader` : succès.
- Autoload direct : classes AUTOSAV, PHPMailer, TCPDF et Monolog chargées.
- Assets publics critiques : `12/12` présents.
- Recette HTTP locale des assets : réponses `200`.
- Accès HTTP direct à `vendor/autoload.php` et `.env` : réponses `404`.
- Constantes applicatives post-bootstrap contrôlées : `8/8`.
- Gestionnaires JavaScript inline incompatibles CSP : `0`.
- Syntaxe JavaScript de `app.js` et `dashboard.js` : `2/2`.
- Healthcheck public contrôlé sans version PHP, chemin ni détail interne.

## Limite de recette locale

La recette fonctionnelle avec base locale n'a pas pu être terminée : MariaDB
XAMPP refuse de démarrer à cause d'une table système locale corrompue :

```text
Fatal error: Can't open and lock privilege tables: Incorrect file format 'db'
```

Cette erreur concerne l'environnement XAMPP local et non l'archive v4.2.
La recette fonctionnelle complète reste à exécuter sur l'environnement
Hostinger après import de la base et configuration du fichier `.env`.

## Déploiement Hostinger

1. Sélectionner PHP `8.2` ou `8.3` dans hPanel.
2. Déposer le projet complet dans `public_html/`, ou pointer la racine web vers
   `public/` lorsque le plan le permet.
3. Créer `.env` depuis `.env.example` et renseigner les valeurs réelles.
4. Importer la base MariaDB fournie.
5. Exécuter :

```bash
composer2 install --no-dev --optimize-autoloader
php bin/production_preflight.php
```

6. Ne mettre le site en ligne que lorsque le préflight retourne `SUCCESS`.

## Risque résiduel

TCPDF est encore utilisé par l'application mais son paquet Composer est
désormais marqué comme moteur PDF historique déprécié. Son remplacement doit
être planifié séparément pour éviter une régression documentaire.

## Correctifs post-audit

- Bootstrap complété avec les configurations Ajax, pagination, dashboard,
  maintenance, modules, RGPD et conditions d'utilisation.
- Validation CSRF rendue idempotente pendant une requête HTTP afin d'éviter
  les refus après passage dans le middleware.
- Script `public/assets/js/dashboard.js` ajouté.
- Confirmations inline remplacées par des attributs `data-confirm` compatibles
  avec la CSP stricte.
- Activation locale des modules effectivement contrôlée par le routeur.
- Réponse du healthcheck public limitée aux statuts, sans chemins ni version PHP.
- Mot de passe MySQL retiré de la ligne de commande `mysqldump`.
- Index déjà créés par le lot 31 retirés du lot 32.
- Arrêt CLI sur configuration de chiffrement invalide corrigé avec un code `1`.
- Logo email absent remplacé par un libellé texte propre tant que le véritable
  fichier de marque n'est pas fourni.
