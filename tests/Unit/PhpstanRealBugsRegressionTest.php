<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : bugs réels détectés par l'introduction de PHPStan
 *
 * L'installation de PHPStan (niveau 5 + baseline pour le bruit lié à
 * l'environnement statique) le 2026-06-25 a immédiatement révélé 4 bugs
 * d'exécution réels, jamais détectés faute d'outillage d'analyse statique :
 *
 *  1. Modules/Ajax/Controllers/AdminAjaxController.php appelait
 *     database() (fonction inexistante) au lieu de db() — 4 endpoints
 *     auraient planté au premier appel ("Call to undefined function").
 *  2. Modules/Ajax/Controllers/TermsAjaxController.php instanciait
 *     Auth\Models\TermsVersionModel, une classe qui n'existe nulle part —
 *     l'endpoint /ajax/terms/status aurait planté ("Class not found").
 *  3. Modules/Brands/Services/BrandService::getForCompany() et
 *     Modules/Ajax/Controllers/CompaniesAjaxController::brandsForCompany()
 *     appelaient BrandModel::getBrandsForCompany(), une méthode qui
 *     n'existait pas ("Call to an undefined method").
 *  4. Modules/Functions/Controllers/ContextController.php declarait le
 *     namespace Nenad\Autosav\Modules\Ajax\Controllers (collision de FQCN
 *     avec le vrai Modules/Ajax/Controllers/ContextController.php) :
 *     fichier mort, jamais charge par l'autoloader PSR-4, supprime.
 */

$root = dirname(__DIR__, 2);

$adminAjax = file_get_contents($root . '/Modules/Ajax/Controllers/AdminAjaxController.php') ?: '';
assert(!str_contains($adminAjax, 'database()'), 'AdminAjaxController ne doit plus appeler la fonction inexistante database().');
assert(str_contains($adminAjax, 'db()'), 'AdminAjaxController doit utiliser le helper db() existant.');

$termsAjax = file_get_contents($root . '/Modules/Ajax/Controllers/TermsAjaxController.php') ?: '';
assert(!str_contains($termsAjax, 'new TermsVersionModel'), 'TermsAjaxController ne doit plus instancier la classe inexistante TermsVersionModel.');
assert(!str_contains($termsAjax, 'use Nenad\Autosav\Modules\Auth\Models\TermsVersionModel'), 'TermsAjaxController ne doit plus importer la classe inexistante TermsVersionModel.');
assert(str_contains($termsAjax, 'trouverDernierDocumentJuridique'), 'TermsAjaxController doit utiliser le vrai mecanisme sav_documents_juridiques.');

$brandModel = file_get_contents($root . '/Modules/Brands/Models/BrandModel.php') ?: '';
assert(str_contains($brandModel, 'function getBrandsForCompany'), 'BrandModel doit definir getBrandsForCompany(), appelee par BrandService et CompaniesAjaxController.');

assert(!is_file($root . '/Modules/Functions/Controllers/ContextController.php'), 'Le fichier ContextController.php en collision de namespace dans Modules/Functions doit etre supprime.');

echo "PhpstanRealBugsRegressionTest SUCCESS\n";
