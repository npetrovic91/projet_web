<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Users\Models\UserModel;
use Nenad\Autosav\Modules\Users\Services\UserService;
use PDO;

/**
 * Endpoints AJAX utilisateurs alignés sur la base SQL actuelle.
 */
class UsersAjaxController extends AjaxController
{
    private UserService $users;
    private UserModel $model;
    private Database $db;
    private PDO $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getPdo();
        $this->model = new UserModel($this->db);
        $this->users = new UserService($this->model);
    }

    public function search(): void
    {
        $query = trim((string) $this->request->get('q', ''));
        if (mb_strlen($query) < 2) {
            AjaxResponseService::success('OK', ['users' => []]);
        }
        $rows = $this->users->rechercher($query, (int) $this->user['id'], (int) $this->request->get('limit', 10));
        $users = array_map(static fn(array $user): array => [
            'id' => (int) ($user['uti_id'] ?? 0),
            'text' => trim((string) ($user['pui_prenom'] ?? '') . ' ' . (string) ($user['pui_nom'] ?? '')) . ' - ' . (string) ($user['uti_email'] ?? ''),
            'email' => (string) ($user['uti_email'] ?? ''),
            'role' => $user['roles_noms'] ?? null,
        ], $rows);
        AjaxResponseService::success('OK', ['users' => $users]);
    }

    public function getUserCompanies(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        AjaxResponseService::success('OK', ['companies' => $this->model->societesUtilisateur($targetId)]);
    }

    public function getUserManagers(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        AjaxResponseService::success('OK', ['managers' => $this->model->managersUtilisateur($targetId)]);
    }

    public function getSubordinates(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $companyId = $this->request->get('company_id') ? (int) $this->request->get('company_id') : null;
        AjaxResponseService::success('OK', ['subordinates' => $this->model->subordonnesUtilisateur($targetId, $companyId)]);
    }

    public function getCreatableRoles(): void
    {
        AjaxResponseService::success('OK', ['roles' => $this->users->getCreatableRoles((int) $this->user['id'])]);
    }

    public function getManageableCompanies(): void
    {
        AjaxResponseService::success('OK', ['companies' => $this->users->getManageableCompanies((int) $this->user['id'])]);
    }

    public function attachCompany(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $data = $this->jsonBody();
        $companyId = (int) ($data['company_id'] ?? $data['societe_id'] ?? 0);
        if ($companyId <= 0) {
            AjaxResponseService::validationError(['company_id' => 'Société obligatoire.']);
        }
        $this->insertAdhesion($targetId, $companyId, (int) $this->user['id']);
        if (!empty($data['is_primary'])) {
            $this->setActiveCompany($targetId, $companyId);
        }
        AjaxResponseService::success('Société rattachée.', ['companies' => $this->model->societesUtilisateur($targetId)]);
    }

    public function detachCompany(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $data = $this->jsonBody();
        $companyId = (int) ($data['company_id'] ?? $data['societe_id'] ?? 0);
        if ($companyId <= 0) {
            AjaxResponseService::validationError(['company_id' => 'Société obligatoire.']);
        }
        $stmt = $this->pdo->prepare(
            'UPDATE sav_adhesions_utilisateurs_societes
             SET aus_archive_le = NOW(), aus_archive_par_utilisateur_id = :uid
             WHERE aus_utilisateur_id = :user_id AND aus_societe_id = :company_id
               AND aus_archive_le IS NULL AND aus_supprime_le IS NULL'
        );
        $stmt->bindValue(':uid', (int) $this->user['id'], PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $targetId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        AjaxResponseService::success('Société détachée.', ['companies' => $this->model->societesUtilisateur($targetId)]);
    }

    public function addManager(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $data = $this->jsonBody();
        $managerId = (int) ($data['manager_id'] ?? $data['superieur_utilisateur_id'] ?? 0);
        $companyId = (int) ($data['company_id'] ?? $data['societe_id'] ?? 0);
        if ($managerId <= 0 || $managerId === $targetId || $companyId <= 0) {
            AjaxResponseService::validationError(['manager' => 'Supérieur et société obligatoires.']);
        }
        $this->archiveManagers($targetId, (int) $this->user['id']);
        $stmt = $this->pdo->prepare(
            'INSERT INTO sav_hierarchie_utilisateurs
             (hiu_societe_id, hiu_utilisateur_id, hiu_superieur_utilisateur_id, hiu_debute_le, hiu_statut_id, hiu_cree_par_utilisateur_id)
             VALUES (:company_id, :user_id, :manager_id, CURDATE(), :statut, :uid)'
        );
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $targetId, PDO::PARAM_INT);
        $stmt->bindValue(':manager_id', $managerId, PDO::PARAM_INT);
        $stmt->bindValue(':statut', $this->model->statutId('general', 'actif'), PDO::PARAM_INT);
        $stmt->bindValue(':uid', (int) $this->user['id'], PDO::PARAM_INT);
        $stmt->execute();
        AjaxResponseService::success('Supérieur hiérarchique ajouté.', ['managers' => $this->model->managersUtilisateur($targetId)]);
    }

    public function removeManager(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $this->archiveManagers($targetId, (int) $this->user['id']);
        AjaxResponseService::success('Supérieur hiérarchique retiré.', ['managers' => $this->model->managersUtilisateur($targetId)]);
    }

    public function setPrimaryCompany(string $id): void
    {
        $targetId = (int) $id;
        $this->assertCanManage($targetId);
        $data = $this->jsonBody();
        $companyId = (int) ($data['company_id'] ?? $data['societe_id'] ?? 0);
        if ($companyId <= 0) {
            AjaxResponseService::validationError(['company_id' => 'Société obligatoire.']);
        }
        $this->insertAdhesion($targetId, $companyId, (int) $this->user['id']);
        $this->setActiveCompany($targetId, $companyId);
        AjaxResponseService::success('Société principale modifiée.');
    }

    private function assertCanManage(int $targetId): void
    {
        if (!$this->users->canManageUser((int) $this->user['id'], $targetId)) {
            AjaxResponseService::forbidden('Accès refusé.');
        }
    }

    private function insertAdhesion(int $userId, int $companyId, int $actionUserId): void
    {
        $exists = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM sav_adhesions_utilisateurs_societes
             WHERE aus_utilisateur_id = :user_id AND aus_societe_id = :company_id
               AND aus_archive_le IS NULL AND aus_supprime_le IS NULL',
            ['user_id' => $userId, 'company_id' => $companyId]
        ) > 0;
        if ($exists) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO sav_adhesions_utilisateurs_societes
             (aus_utilisateur_id, aus_societe_id, aus_debute_le, aus_statut_id, aus_cree_par_utilisateur_id)
             VALUES (:user_id, :company_id, CURDATE(), :statut, :uid)'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':statut', $this->model->statutId('general', 'actif'), PDO::PARAM_INT);
        $stmt->bindValue(':uid', $actionUserId ?: null, $actionUserId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
    }

    private function setActiveCompany(int $userId, int $companyId): void
    {
        $stmt = $this->pdo->prepare('UPDATE sav_utilisateurs SET uti_societe_active_id = :company_id WHERE uti_id = :user_id');
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function archiveManagers(int $userId, int $actionUserId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sav_hierarchie_utilisateurs
             SET hiu_archive_le = NOW(), hiu_archive_par_utilisateur_id = :uid
             WHERE hiu_utilisateur_id = :user_id AND hiu_archive_le IS NULL AND hiu_supprime_le IS NULL'
        );
        $stmt->bindValue(':uid', $actionUserId ?: null, $actionUserId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
