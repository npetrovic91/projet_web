<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Modules\Notifications\Services\ActionNotifier;
use Nenad\Autosav\Modules\Users\Models\UserCompanyHistoryModel;
use Nenad\Autosav\Modules\Users\Models\UserCompanyModel;
use Nenad\Autosav\Modules\Users\Models\UserModel;
use PDO;

/** Service de compatibilité aligné sur sav_adhesions_utilisateurs_societes. */
class UserCompanyService implements ServiceInterface{
    private Database $db;
    private PDO $pdo;
    private ActionNotifier $notifier;

    public function __construct(
        private ?UserCompanyModel $companies = null,
        private ?UserCompanyHistoryModel $history = null,
        private ?UserModel $users = null,
        ?ActionNotifier $notifier = null
    ) {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getPdo();
        $this->users ??= new UserModel($this->db);
        $this->notifier = $notifier ?? new ActionNotifier();
    }

    public function getUserCompanies(int $userId): array
    {
        return $this->users->societesUtilisateur($userId);
    }

    public function getUserHistory(int $userId): array
    {
        return $this->users->societesUtilisateur($userId);
    }

    public function attachUserToCompany(int $userId, int $companyId, bool $isPrimary, string $joinedAt, string $jobTitle, int $actionUserId): array
    {
        if ($companyId <= 0) {
            return ['success' => false, 'message' => 'Société obligatoire.'];
        }
        $exists = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM sav_adhesions_utilisateurs_societes
             WHERE aus_utilisateur_id = :uid AND aus_societe_id = :sid AND aus_supprime_le IS NULL AND aus_archive_le IS NULL',
            ['uid' => $userId, 'sid' => $companyId]
        ) > 0;
        if (!$exists) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO sav_adhesions_utilisateurs_societes
                 (aus_utilisateur_id, aus_societe_id, aus_debute_le, aus_statut_id, aus_cree_par_utilisateur_id)
                 VALUES (:uid, :sid, :debute, :statut, :action)'
            );
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':sid', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':debute', preg_match('/^\d{4}-\d{2}-\d{2}$/', $joinedAt) ? $joinedAt : date('Y-m-d'));
            $stmt->bindValue(':statut', $this->users->statutId('general', 'actif'), PDO::PARAM_INT);
            $stmt->bindValue(':action', $actionUserId ?: null, $actionUserId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
        }
        if ($isPrimary) {
            $this->pdo->prepare('UPDATE sav_utilisateurs SET uti_societe_active_id = ? WHERE uti_id = ?')->execute([$companyId, $userId]);
        }
        // CORRECTIF 2.4 (notifications in-app) : l'utilisateur n'était
        // jamais informé d'un rattachement à une nouvelle société.
        if (!$exists && $userId !== $actionUserId) {
            $this->notifier->notifierUtilisateur(
                $userId,
                'utilisateur.societe_rattachee',
                'Nouvelle société rattachée à votre compte',
                'Vous avez été rattaché à une nouvelle société.',
                ['company_id' => $companyId],
                $companyId,
                $actionUserId ?: null
            );
        }
        return ['success' => true, 'message' => 'Société rattachée.'];
    }

    public function detachUserFromCompany(int $userId, int $companyId, string $reason, int $actionUserId): array
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sav_adhesions_utilisateurs_societes
             SET aus_archive_le = NOW(), aus_archive_par_utilisateur_id = :action
             WHERE aus_utilisateur_id = :uid AND aus_societe_id = :sid AND aus_supprime_le IS NULL AND aus_archive_le IS NULL'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':sid', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':action', $actionUserId ?: null, $actionUserId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
        return ['success' => true, 'message' => 'Société détachée.'];
    }

    public function setPrimaryCompany(int $userId, int $companyId, int $actionUserId): array
    {
        $this->attachUserToCompany($userId, $companyId, true, date('Y-m-d'), '', $actionUserId);
        return ['success' => true, 'message' => 'Société principale modifiée.'];
    }
}
