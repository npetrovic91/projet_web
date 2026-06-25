<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Emails\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Emails\Models\EmailModel;

class EmailsController extends BaseController
{
    private EmailModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new EmailModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->render('Emails/Views/index', [
            'modeles' => $this->model->listerModeles(['search' => (string) $this->get('q', '')]),
            'journaux' => $this->model->listerJournaux(['email' => (string) $this->get('email', '')], 50),
            'page_title' => 'Emails',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function templates(): void
    {
        $this->requireAuth();
        $this->render('Emails/Views/templates', [
            'modeles' => $this->model->listerModeles(['search' => (string) $this->get('q', '')]),
            'page_title' => 'Modèles email',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function createTemplate(): void
    {
        $this->requireAuth();
        $this->render('Emails/Views/template_form', [
            'modele' => null,
            'statuts' => $this->model->listerStatuts('general'),
            'page_title' => 'Nouveau modèle email',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function storeTemplate(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $data = $_POST;
        if (trim((string) ($data['mel_sujet'] ?? '')) === '' || trim((string) ($data['mel_corps'] ?? '')) === '') {
            $this->flash()->error('Le sujet et le corps du modèle sont obligatoires.');
            $this->redirect('/emails/templates/create');
        }
        $id = $this->model->creerModele($data);
        $this->flash()->success('Modèle email créé.');
        $this->redirect('/emails/templates/' . $id . '/edit');
    }

    public function editTemplate(string $id): void
    {
        $this->requireAuth();
        $modele = $this->model->trouverModele((int) $id);
        if (!$modele) {
            $this->flash()->error('Modèle email introuvable.');
            $this->redirect('/emails/templates');
        }
        $this->render('Emails/Views/template_form', [
            'modele' => $modele,
            'statuts' => $this->model->listerStatuts('general'),
            'page_title' => 'Modifier modèle email',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function updateTemplate(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->model->modifierModele((int) $id, $_POST);
        $this->flash()->success('Modèle email mis à jour.');
        $this->redirect('/emails/templates');
    }

    public function deleteTemplate(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->model->supprimerModele((int) $id);
        $this->flash()->success('Modèle email supprimé.');
        $this->redirect('/emails/templates');
    }

    public function logs(): void
    {
        $this->requireAuth();
        $this->render('Emails/Views/logs', [
            'journaux' => $this->model->listerJournaux([
                'email' => (string) $this->get('email', ''),
                'type_evenement' => (string) $this->get('type', ''),
            ], 300),
            'page_title' => 'Journaux emails',
        ]);
    }
}
