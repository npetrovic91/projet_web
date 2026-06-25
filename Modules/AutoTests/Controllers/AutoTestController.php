<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\AutoTests\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\AutoTests\Services\AutoTestService;

final class AutoTestController extends BaseController
{
    private AutoTestService $service;

    public function __construct(?\Nenad\Autosav\Core\Database\Database $database = null)
    {
        parent::__construct($database);
        $this->service = new AutoTestService();
    }

    public function index(): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $this->render('AutoTests/index', [
            'pageTitle' => 'Autotests des modules',
            'breadcrumb' => ['Super-admin' => '/super-admin', 'Autotests' => '/autotests'],
            'resume' => $this->service->resumeGlobal(),
            'modules' => $this->service->modules(),
        ]);
    }

    public function module(string|int|null $module = null): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $nomModule = (string)($module ?? ($_GET['module'] ?? ''));
        $resultat = $this->service->testerModule($nomModule);
        $viewPath = $this->viewPathFor($resultat['nom']);
        $this->render($viewPath, [
            'pageTitle' => 'Autotest — ' . $resultat['nom'],
            'breadcrumb' => ['Autotests' => '/autotests', $resultat['nom'] => '/autotests/module/' . rawurlencode($resultat['nom'])],
            'resultat' => $resultat,
        ]);
    }

    private function viewPathFor(string $nomModule): string
    {
        $root = defined('SRC_PATH') ? SRC_PATH : dirname(__DIR__, 3);
        if ($nomModule === 'Core' && is_file($root . '/Core/autotest.php')) {
            return 'Core/autotest';
        }
        if (str_starts_with($nomModule, 'Core_')) {
            $coreView = 'Core/' . substr($nomModule, 5) . '/autotest';
            if (is_file($root . '/' . $coreView . '.php')) {
                return $coreView;
            }
        }

        $vueModule = 'Modules/' . $nomModule . '/Views/autotest.php';
        return is_file($root . '/' . $vueModule)
            ? $nomModule . '/Views/autotest'
            : 'AutoTests/module';
    }

    public function exportJson(): never
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $this->json(true, $this->service->export(), 'Export des autotests généré.');
    }

    public function debogage(): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $module = (string)($_GET['module'] ?? '');
        $classe = (string)($_GET['classe'] ?? '');
        $fonction = (string)($_GET['fonction'] ?? '');
        $message = sprintf(
            'Demande de débogage enregistrée pour le module %s, classe %s%s.',
            $module,
            $classe,
            $fonction !== '' ? '::' . $fonction : ''
        );
        try {
            $this->db->prepare(
                "INSERT INTO sav_journaux_audit (jau_utilisateur_id, jau_societe_id, jau_action, jau_table_cible, jau_id_cible, jau_raison, jau_cree_le)
                 VALUES (:utilisateur, :societe, 'autotest.debogage.demande', 'module', 0, :raison, NOW())"
            )->execute([
                ':utilisateur' => (int)($_SESSION['user_id'] ?? 0) ?: null,
                ':societe' => (int)($_SESSION['active_company_id'] ?? 0) ?: null,
                ':raison' => $message,
            ]);
        } catch (\Throwable) {
            // L'affichage de la demande reste possible même si la base est indisponible.
        }
        $this->render('AutoTests/debogage', [
            'pageTitle' => 'Demande de débogage',
            'message' => $message,
            'module' => $module,
            'classe' => $classe,
            'fonction' => $fonction,
        ]);
    }
}
