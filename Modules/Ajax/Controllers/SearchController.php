<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Ajax\Services\GlobalSearchService;
use Nenad\Autosav\Core\Database\Database;

/**
 * AUTOSAV — Contrôleur Recherche Globale AJAX
 * Fichier : src/Modules/Ajax/Controllers/SearchController.php
 */
class SearchController extends AjaxController
{
    private GlobalSearchService $search;

    public function __construct()
    {
        parent::__construct();
        $this->search = new GlobalSearchService(Database::getInstance());
    }

    public function global(): void
    {
        $query       = trim((string) ($_GET['q'] ?? ''));
        $isSuperAdmin = in_array(ROLE_SUPERADMIN, $this->user['roles'], true);

        if (mb_strlen($query) < 2) {
            AjaxResponseService::success('OK', ['users'=>[], 'companies'=>[], 'brands'=>[], 'query'=>$query]);
        }

        $results = $this->search->search($query, $this->user['id'], $isSuperAdmin);

        AjaxResponseService::success('Recherche effectuée.', $results);
    }
}
