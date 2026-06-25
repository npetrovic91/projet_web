<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Invitations\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Invitations\Services\InvitationService;

class InvitationController extends BaseController
{
    private InvitationService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new InvitationService();
    }

    public function index(): void
    {
        $this->requirePermission('invitation.read');
        $filters = [
            'q' => trim((string)$this->get('q', '')),
            'societe_id' => (int)$this->get('societe_id', 0) ?: $this->activeCompanyId(),
            'statut_id' => (int)$this->get('statut_id', 0) ?: null,
            'etat' => trim((string)$this->get('etat', '')),
        ];
        $this->render('Invitations/Views/index', [
            'pageTitle' => 'Invitations utilisateurs',
            'stats' => $this->service->statistiques($filters['societe_id'] ? (int)$filters['societe_id'] : null),
            'invitations' => $this->service->lister($filters),
            'filters' => $filters,
            'refs' => $this->service->references(),
        ]);
    }

    public function exportJson(): void
    {
        $this->requirePermission('invitation.read');
        $this->json(true, $this->service->export((int)$this->get('societe_id', 0) ?: null), 'Export des invitations.');
    }

    public function create(): void
    {
        $this->requirePermission('invitation.manage');
        $this->render('Invitations/Views/form', [
            'pageTitle' => 'Créer une invitation utilisateur',
            'invitation' => null,
            'refs' => $this->service->references(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('invitation.manage');
        $invitation = $this->service->trouver((int)$id);
        if (!$invitation) {
            $this->flash()->error('Invitation introuvable.');
            $this->redirect('/invitations');
        }
        $this->render('Invitations/Views/form', [
            'pageTitle' => 'Modifier une invitation utilisateur',
            'invitation' => $invitation,
            'refs' => $this->service->references(),
        ]);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(string $id): void
    {
        $this->save((int)$id);
    }

    public function resend(string $id): void
    {
        $this->requirePermission('invitation.manage');
        $this->validateCsrf();
        try {
            $token = $this->service->regenererJeton((int)$id, $this->userId(), client_ip());
            $this->flash()->success('Invitation régénérée. Jeton à transmettre : ' . $token);
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/invitations/' . (int)$id . '/edit');
    }

    public function cancel(string $id): void
    {
        $this->requirePermission('invitation.manage');
        $this->validateCsrf();
        try {
            $this->service->annuler((int)$id, $this->userId(), client_ip());
            $this->flash()->success('Invitation annulée.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/invitations');
    }

    public function acceptForm(string $token): void
    {
        $invitation = $this->service->trouverParJetonPublic($token);
        $this->render('Invitations/Views/accept', [
            'pageTitle' => 'Accepter une invitation',
            'token' => $token,
            'invitation' => $invitation,
        ], 'public');
    }

    public function accept(string $token): void
    {
        $this->validateCsrf();
        try {
            $userId = $this->service->accepterInvitation($token, $_POST, client_ip(), $_SERVER['HTTP_USER_AGENT'] ?? null);
            $this->flash()->success('Invitation acceptée. Votre compte utilisateur a été créé ou rattaché.');
            $this->redirect('/login');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/invitations/accept/' . rawurlencode($token));
        }
    }

    public function premierAdministrateur(): void
    {
        $this->requirePermission('invitation.manage');
        $this->render('Invitations/Views/premier_admin', [
            'pageTitle' => 'Créer le premier administrateur société',
            'refs' => $this->service->references(),
        ]);
    }

    public function creerPremierAdministrateur(): void
    {
        $this->requirePermission('invitation.manage');
        $this->validateCsrf();
        try {
            $resultat = $this->service->creerPremierAdministrateur($_POST, $this->userId(), client_ip());
            $this->flash()->success('Premier administrateur préparé. Jeton d’invitation : ' . $resultat['token']);
            $this->redirect('/invitations/' . $resultat['invitation_id'] . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/invitations/premier-administrateur');
        }
    }

    private function save(?int $id): void
    {
        $this->requirePermission('invitation.manage');
        $this->validateCsrf();
        try {
            $resultat = $this->service->enregistrer($_POST, $id, $this->userId(), client_ip());
            $message = $id ? 'Invitation modifiée.' : 'Invitation créée.';
            if (!$id && !empty($resultat['token'])) {
                $message .= ' Jeton à transmettre : ' . $resultat['token'];
            }
            $this->flash()->success($message);
            $this->redirect('/invitations/' . $resultat['id'] . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect($id ? '/invitations/' . $id . '/edit' : '/invitations/create');
        }
    }
}
