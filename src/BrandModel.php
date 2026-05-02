<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Profile\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Profile\Models\GdprRequestModel;
use Nenad\Autosav\Modules\Profile\Services\ProfileService;
use Nenad\Autosav\Modules\Qualifications\Models\QualificationModel;
use Nenad\Autosav\Modules\Qualifications\Models\UserQualificationModel;
use Nenad\Autosav\Modules\Qualifications\Services\QualificationService;
use Nenad\Autosav\Modules\Skills\Models\SkillModel;
use Nenad\Autosav\Modules\Skills\Models\UserSkillModel;
use Nenad\Autosav\Modules\Skills\Services\SkillService;
use Nenad\Autosav\Modules\Users\Models\UserCompanyHistoryModel;
use Nenad\Autosav\Modules\Users\Models\UserCompanyModel;
use Nenad\Autosav\Modules\Users\Models\UserModel;
use Nenad\Autosav\Modules\Users\Services\UserCompanyService;

class ProfileController extends BaseController
{
    private ProfileService $profiles;

    public function __construct()
    {
        parent::__construct();
        $userModel = new UserModel();
        $companyModel = new UserCompanyModel();
        $historyModel = new UserCompanyHistoryModel();

        $this->profiles = new ProfileService(
            $userModel,
            new UserCompanyService($companyModel, $historyModel, $userModel),
            new SkillService(new SkillModel(), new UserSkillModel()),
            new QualificationService(new QualificationModel(), new UserQualificationModel()),
            new GdprRequestModel()
        );
    }

    public function show(): void
    {
        $this->requireAuth();
        $userId = (int) $this->getCurrentUser()['use_id'];
        $this->render('Profile/show', [
            'profile' => $this->profiles->getProfile($userId),
            'csrf_token' => $this->csrfToken(),
            'page_title' => 'Mon profil',
        ]);
    }

    public function update(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $userId = (int) $this->getCurrentUser()['use_id'];
        $result = $this->profiles->updatePersonalData($userId, $this->request->all());
        $this->flash($result['success'] ? 'success' : 'error', $result['message'] ?? 'Modification impossible.');
        $this->redirect('/profile');
    }

    public function changePassword(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $result = $this->profiles->changePassword(
            (int) $this->getCurrentUser()['use_id'],
            (string) $this->request->post('current_password', ''),
            (string) $this->request->post('password', ''),
            (string) $this->request->post('password_confirm', '')
        );
        $this->flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Mot de passe modifie.' : implode(' ', $result['errors']));
        $this->redirect('/profile');
    }

    public function gdpr(): void
    {
        $this->requireAuth();
        $userId = (int) $this->getCurrentUser()['use_id'];
        $this->render('Profile/gdpr', [
            'requests' => $this->profiles->getGdprRequests($userId),
            'csrf_token' => $this->csrfToken(),
            'page_title' => 'Mes donnees personnelles',
        ]);
    }

    public function gdprRequest(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->profiles->createGdprRequest(
            (int) $this->getCurrentUser()['use_id'],
            (string) $this->request->post('type', 'access'),
            (string) $this->request->post('message', ''),
            client_ip()
        );
        $this->flash('success', 'Demande RGPD enregistree.');
        $this->redirect('/profile/gdpr');
    }

    public function gdprExport(): never
    {
        $this->requireAuth();
        $userId = (int) $this->getCurrentUser()['use_id'];
        $payload = $this->profiles->exportUserData($userId);

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="autosav-user-data-' . $userId . '.json"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
