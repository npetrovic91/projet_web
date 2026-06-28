<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\SuperAdmin\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Security\Class\SuperAdminAccessGuard;
use Nenad\Autosav\Core\Services\Production\LogViewerService;
use Nenad\Autosav\Core\Services\Production\SessionViewerService;
use Nenad\Autosav\Modules\Society\Services\ServiceSocietes;
use Nenad\Autosav\Modules\SuperAdmin\Services\SuperAdminService;

final class SuperAdminController extends BaseController
{
    private SuperAdminService $service;
    private LogViewerService $logs;
    private SessionViewerService $sessions;

    public function __construct(?\Nenad\Autosav\Core\Database\Database $database = null)
    {
        parent::__construct($database);
        $this->service = new SuperAdminService();
        $this->logs = new LogViewerService();
        $this->sessions = new SessionViewerService();
    }

    public function index(): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $this->render('SuperAdmin/index', [
            'pageTitle' => 'Super-admin — Gestion de l’application',
            'breadcrumb' => ['Super-admin' => '/super-admin'],
            'data' => $this->service->tableauDeBord(),
        ]);
    }

    public function application(): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $this->render('SuperAdmin/application', [
            'pageTitle' => 'Gestion globale de l’application',
            'breadcrumb' => ['Super-admin' => '/super-admin', 'Application' => '/super-admin/application'],
            'data' => $this->service->tableauDeBord(),
        ]);
    }

    public function exportJson(): never
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $this->json(true, $this->service->export(), 'Export de gestion application généré.');
    }

    /**
     * Formulaire de justification obligatoire (ACC-007) avant l'accès
     * du super_admin aux données métier d'une société cliente.
     */
    public function justificationForm(): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');

        $societeId = (int) $this->get('societe_id', 0);
        $retour = (string) $this->get('retour', '/companies/' . $societeId);
        if ($societeId <= 0) {
            $this->redirect('/super-admin');
        }

        $societe = (new ServiceSocietes())->ficheComplete($societeId);

        $this->render('SuperAdmin/justification', [
            'pageTitle' => 'Justification d’accès — données métier',
            'breadcrumb' => ['Super-admin' => '/super-admin', 'Justification' => null],
            'societeId' => $societeId,
            'societeNom' => $societe['societe']['soc_nom'] ?? $societe['societe']['com_name'] ?? ('Société #' . $societeId),
            'retour' => $retour,
            'motifs' => SuperAdminAccessGuard::MOTIFS,
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function justificationSubmit(): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');
        $this->validateCsrf();

        $societeId = (int) $this->request->post('societe_id', 0);
        $motif = (string) $this->request->post('motif', '');
        $justification = trim((string) $this->request->post('justification', ''));
        $retour = (string) $this->request->post('retour', '/companies/' . $societeId);

        if ($societeId <= 0 || !in_array($motif, SuperAdminAccessGuard::MOTIFS, true) || mb_strlen($justification) < 10) {
            $this->flash('error', 'Motif invalide ou justification trop courte (10 caractères minimum).');
            $this->redirect('/super-admin/justification?societe_id=' . $societeId . '&retour=' . rawurlencode($retour));
        }

        SuperAdminAccessGuard::ouvrirAcces($this->operatorId(), $societeId, $motif, $justification, $retour);
        $this->flash('warning', 'Accès aux données métier de cette société journalisé (motif : ' . $motif . ').');
        $this->redirect($retour !== '' ? $retour : ('/companies/' . $societeId));
    }

    /**
     * Lecture des journaux applicatifs (storage/logs/) — réservé au
     * super_administrateur, lecture seule.
     */
    public function logs(): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');

        $canal = (string) $this->get('canal', 'application');
        $recherche = (string) $this->get('q', '');
        $niveau = (string) $this->get('niveau', '');
        $limite = (int) $this->get('limite', 200);

        $this->render('SuperAdmin/logs', [
            'pageTitle' => 'Super-admin — Journaux applicatifs',
            'breadcrumb' => ['Super-admin' => '/super-admin', 'Journaux' => '/super-admin/logs'],
            'canaux' => $this->logs->canaux(),
            'canalActif' => $canal,
            'recherche' => $recherche,
            'niveau' => $niveau,
            'limite' => $limite,
            'resultat' => $this->logs->lire($canal, $limite, $recherche, $niveau),
        ]);
    }

    /**
     * Liste des sessions PHP fichier (storage/sessions/) — réservé au
     * super_administrateur, lecture seule.
     */
    public function sessions(): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');

        $this->render('SuperAdmin/sessions', [
            'pageTitle' => 'Super-admin — Sessions actives',
            'breadcrumb' => ['Super-admin' => '/super-admin', 'Sessions' => '/super-admin/sessions'],
            'sessions' => $this->sessions->lister(),
        ]);
    }

    /**
     * Détail d'une session précise (contenu décodé).
     */
    public function sessionShow(string $id): void
    {
        $this->requireRole(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur');

        $session = $this->sessions->afficher($id);
        if ($session === null) {
            $this->flash('error', 'Session introuvable ou expirée.');
            $this->redirect('/super-admin/sessions');
        }

        $this->render('SuperAdmin/session_show', [
            'pageTitle' => 'Super-admin — Session ' . $id,
            'breadcrumb' => ['Super-admin' => '/super-admin', 'Sessions' => '/super-admin/sessions', $id => null],
            'session' => $session,
        ]);
    }
}
