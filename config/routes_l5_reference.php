<?php
/**
 * LIVRABLE 5 — Ajouts de routes
 * À intégrer dans config/urls.php
 *
 * Format de chaque entrée :
 *   ['method' => 'GET|POST', 'uri' => '/path', 'controller' => 'Module\Controller', 'action' => 'method', 'middleware' => []]
 */

// ============================================================
// MODULE COMPANIES
// ============================================================
$routes[] = ['method' => 'GET',  'uri' => '/companies',                     'controller' => 'Companies\Controllers\CompanyController', 'action' => 'index',         'middleware' => ['auth', 'csrf_view']];
$routes[] = ['method' => 'GET',  'uri' => '/companies/create',              'controller' => 'Companies\Controllers\CompanyController', 'action' => 'create',        'middleware' => ['auth', 'csrf_view']];
$routes[] = ['method' => 'POST', 'uri' => '/companies/store',               'controller' => 'Companies\Controllers\CompanyController', 'action' => 'store',         'middleware' => ['auth', 'csrf']];
$routes[] = ['method' => 'GET',  'uri' => '/companies/{id:\d+}',            'controller' => 'Companies\Controllers\CompanyController', 'action' => 'show',          'middleware' => ['auth']];
$routes[] = ['method' => 'GET',  'uri' => '/companies/{id:\d+}/edit',       'controller' => 'Companies\Controllers\CompanyController', 'action' => 'edit',          'middleware' => ['auth', 'csrf_view']];
$routes[] = ['method' => 'POST', 'uri' => '/companies/{id:\d+}/update',     'controller' => 'Companies\Controllers\CompanyController', 'action' => 'update',        'middleware' => ['auth', 'csrf']];
$routes[] = ['method' => 'POST', 'uri' => '/companies/{id:\d+}/delete',     'controller' => 'Companies\Controllers\CompanyController', 'action' => 'delete',        'middleware' => ['auth', 'csrf']];
$routes[] = ['method' => 'POST', 'uri' => '/companies/{id:\d+}/restore',    'controller' => 'Companies\Controllers\CompanyController', 'action' => 'restore',       'middleware' => ['auth', 'csrf']];
$routes[] = ['method' => 'POST', 'uri' => '/companies/relation/add',        'controller' => 'Companies\Controllers\CompanyController', 'action' => 'addRelation',   'middleware' => ['auth', 'csrf']];
$routes[] = ['method' => 'POST', 'uri' => '/companies/relation/remove',     'controller' => 'Companies\Controllers\CompanyController', 'action' => 'removeRelation','middleware' => ['auth', 'csrf']];
$routes[] = ['method' => 'POST', 'uri' => '/companies/{id:\d+}/brand/attach',  'controller' => 'Companies\Controllers\CompanyController', 'action' => 'attachBrand',  'middleware' => ['auth', 'csrf']];
$routes[] = ['method' => 'POST', 'uri' => '/companies/{id:\d+}/brand/detach',  'controller' => 'Companies\Controllers\CompanyController', 'action' => 'detachBrand',  'middleware' => ['auth', 'csrf']];

// ============================================================
// MODULE BRANDS
// ============================================================
$routes[] = ['method' => 'GET',  'uri' => '/brands',                     'controller' => 'Brands\Controllers\BrandController', 'action' => 'index',      'middleware' => ['auth']];
$routes[] = ['method' => 'GET',  'uri' => '/brands/create',              'controller' => 'Brands\Controllers\BrandController', 'action' => 'create',     'middleware' => ['auth', 'csrf_view']];
$routes[] = ['method' => 'POST', 'uri' => '/brands/store',               'controller' => 'Brands\Controllers\BrandController', 'action' => 'store',      'middleware' => ['auth', 'csrf']];
$routes[] = ['method' => 'GET',  'uri' => '/brands/{id:\d+}/edit',       'controller' => 'Brands\Controllers\BrandController', 'action' => 'edit',       'middleware' => ['auth', 'csrf_view']];
$routes[] = ['method' => 'POST', 'uri' => '/brands/{id:\d+}/update',     'controller' => 'Brands\Controllers\BrandController', 'action' => 'update',     'middleware' => ['auth', 'csrf']];
$routes[] = ['method' => 'POST', 'uri' => '/brands/{id:\d+}/deactivate', 'controller' => 'Brands\Controllers\BrandController', 'action' => 'deactivate', 'middleware' => ['auth', 'csrf']];

// ============================================================
// AJAX — COMPANIES & BRANDS (authentifié)
// ============================================================
$routes[] = ['method' => 'GET', 'uri' => '/ajax/companies/list',                  'controller' => 'Ajax\Controllers\CompaniesAjaxController', 'action' => 'list',              'middleware' => ['ajax', 'auth', 'csrf_ajax']];
$routes[] = ['method' => 'GET', 'uri' => '/ajax/companies/search',                'controller' => 'Ajax\Controllers\CompaniesAjaxController', 'action' => 'search',            'middleware' => ['ajax', 'auth', 'csrf_ajax']];
$routes[] = ['method' => 'GET', 'uri' => '/ajax/companies/by-type',               'controller' => 'Ajax\Controllers\CompaniesAjaxController', 'action' => 'byType',            'middleware' => ['ajax', 'auth', 'csrf_ajax']];
$routes[] = ['method' => 'GET', 'uri' => '/ajax/companies/{id:\d+}/brands',       'controller' => 'Ajax\Controllers\CompaniesAjaxController', 'action' => 'brandsForCompany',  'middleware' => ['ajax', 'auth', 'csrf_ajax']];
$routes[] = ['method' => 'GET', 'uri' => '/ajax/brands/list',                     'controller' => 'Ajax\Controllers\CompaniesAjaxController', 'action' => 'listBrands',        'middleware' => ['ajax', 'auth', 'csrf_ajax']];
$routes[] = ['method' => 'GET', 'uri' => '/ajax/brands/search',                   'controller' => 'Ajax\Controllers\CompaniesAjaxController', 'action' => 'searchBrands',      'middleware' => ['ajax', 'auth', 'csrf_ajax']];

// ============================================================
// PERMISSIONS À AJOUTER (seeds/permissions_seed.sql)
// ============================================================
/*
INSERT INTO sav_permissions (prm_code, prm_module, prm_action, prm_label) VALUES
  ('companies.read',   'companies', 'read',   'Voir les entreprises'),
  ('companies.create', 'companies', 'create', 'Créer des entreprises'),
  ('companies.update', 'companies', 'update', 'Modifier des entreprises'),
  ('companies.delete', 'companies', 'delete', 'Supprimer des entreprises'),
  ('brands.read',      'brands',    'read',   'Voir les marques'),
  ('brands.create',    'brands',    'create', 'Créer des marques'),
  ('brands.update',    'brands',    'update', 'Modifier des marques'),
  ('brands.delete',    'brands',    'delete', 'Désactiver des marques');
*/
