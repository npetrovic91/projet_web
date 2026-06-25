<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Roles\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Roles\Services\GroupPropagationService;

/**
 * AUTOSAV — Propagation massive groupe -> concessions (ACC-009)
 * Réservé aux rôles pilotant un groupe de concessions.
 */
final class GroupPropagationController extends BaseController
{
    private const ROLES_AUTORISES = ['responsable_groupe_concessions', 'directeur_groupe', 'administrateur_groupe_concessions'];

    private GroupPropagationService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new GroupPropagationService();
    }

    public function form(): void
    {
        $this->requireRole(self::ROLES_AUTORISES);
        $groupeId = $this->groupeSocieteId();
        $type = (string) $this->get('type', 'fonction');

        $this->render('Roles/Views/propagation', [
            'pageTitle' => 'Propagation groupe → concessions',
            'groupeId' => $groupeId,
            'type' => $type,
            'types' => GroupPropagationService::typesValides(),
            'catalogue' => $this->service->catalogueGroupe($type, $groupeId),
            'concessions' => $this->service->concessionsRattachees($groupeId),
            'historique' => $this->service->historique($groupeId),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function previsualiser(): void
    {
        $this->requireRole(self::ROLES_AUTORISES);
        $this->validateCsrf();

        $type = (string) $this->request->post('type', '');
        $cibleId = (int) $this->request->post('cible_id', 0);
        $concessionIds = (array) $this->request->post('concession_ids', []);

        try {
            $resultat = $this->service->previsualiser($type, $cibleId, $this->groupeSocieteId(), $concessionIds, $this->operatorId());
            $this->json(true, $resultat, 'Prévisualisation calculée.');
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage(), 422);
        }
    }

    public function confirmer(string $id): void
    {
        $this->requireRole(self::ROLES_AUTORISES);
        $this->validateCsrf();

        try {
            $resultat = $this->service->confirmer((int) $id, $this->operatorId());
            $this->flash()->success(sprintf(
                'Propagation exécutée : %d créé(s), %d erreur(s).',
                count($resultat['crees']),
                count($resultat['erreurs'])
            ));
            if (function_exists('logger')) {
                logger('security')->info('propagation_groupe_executee', [
                    'bulk_action_id' => (int) $id,
                    'user_id' => $this->operatorId(),
                    'resultat' => $resultat,
                ]);
            }
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/roles/propagation');
    }

    public function annuler(string $id): void
    {
        $this->requireRole(self::ROLES_AUTORISES);
        $this->validateCsrf();

        try {
            $this->service->annuler((int) $id, $this->operatorId());
            $this->flash()->success('Propagation annulée (rollback effectué).');
            if (function_exists('logger')) {
                logger('security')->warning('propagation_groupe_rollback', [
                    'bulk_action_id' => (int) $id,
                    'user_id' => $this->operatorId(),
                ]);
            }
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/roles/propagation');
    }

    private function groupeSocieteId(): int
    {
        return (int) ($_SESSION['user']['actual_society_id'] ?? $_SESSION['active_company_id'] ?? $_SESSION['user']['active_company_id'] ?? 0);
    }
}
