<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Modules\Notifications\Services\ActionNotifier;
use Nenad\Autosav\Modules\Users\Models\UserHierarchyModel;
use Nenad\Autosav\Modules\Users\Models\UserModel;
use PDO;

/** Service de compatibilité aligné sur sav_hierarchie_utilisateurs. */
class UserHierarchyService implements ServiceInterface{
    private UserModel $users;
    private PDO $pdo;
    private ActionNotifier $notifier;

    public function __construct(private ?UserHierarchyModel $hierarchy = null, ?ActionNotifier $notifier = null)
    {
        $db = Database::getInstance();
        $this->pdo = $db->getPdo();
        $this->users = new UserModel($db);
        $this->notifier = $notifier ?? new ActionNotifier();
    }

    public function getManagers(int $userId): array
    {
        return $this->users->managersUtilisateur($userId);
    }

    public function getSubordinates(int $userId, ?int $companyId = null): array
    {
        return $this->users->subordonnesUtilisateur($userId, $companyId);
    }

    public function addManager(int $userId, int $managerId, ?int $companyId, bool $isPrimary, int $actionUserId): array
    {
        if ($managerId <= 0 || $managerId === $userId || !$companyId) {
            return ['success' => false, 'message' => 'Supérieur et société obligatoires.'];
        }
        $this->removeManager($userId, $managerId, $actionUserId);
        $stmt = $this->pdo->prepare(
            'INSERT INTO sav_hierarchie_utilisateurs
             (hiu_societe_id, hiu_utilisateur_id, hiu_superieur_utilisateur_id, hiu_debute_le, hiu_statut_id, hiu_cree_par_utilisateur_id)
             VALUES (:sid, :uid, :mid, CURDATE(), :statut, :action)'
        );
        $stmt->bindValue(':sid', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':mid', $managerId, PDO::PARAM_INT);
        $stmt->bindValue(':statut', $this->users->statutId('general', 'actif'), PDO::PARAM_INT);
        $stmt->bindValue(':action', $actionUserId ?: null, $actionUserId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
        // CORRECTIF 2.4 (notifications in-app) : l'utilisateur n'était
        // jamais informé qu'un nouveau supérieur hiérarchique lui était
        // assigné.
        if ($userId !== $actionUserId) {
            $this->notifier->notifierUtilisateur(
                $userId,
                'utilisateur.superieur_assigne',
                'Nouveau supérieur hiérarchique assigné',
                'Un nouveau supérieur hiérarchique vous a été assigné.',
                ['manager_id' => $managerId, 'company_id' => $companyId],
                $companyId,
                $actionUserId ?: null
            );
        }
        return ['success' => true, 'message' => 'Supérieur hiérarchique ajouté.'];
    }

    public function removeManager(int $userId, int $managerId, int $actionUserId): array
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sav_hierarchie_utilisateurs
             SET hiu_archive_le = NOW(), hiu_archive_par_utilisateur_id = :action
             WHERE hiu_utilisateur_id = :uid AND hiu_supprime_le IS NULL AND hiu_archive_le IS NULL'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':action', $actionUserId ?: null, $actionUserId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
        return ['success' => true, 'message' => 'Supérieur hiérarchique retiré.'];
    }
}
