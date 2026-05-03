<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Services;

use Nenad\Autosav\Modules\Users\Models\UserHierarchyModel;

class UserHierarchyService
{
    public function __construct(private UserHierarchyModel $hierarchy) {}

    public function addManager(
        int $userId,
        int $managerId,
        ?int $companyId,
        bool $isPrimary,
        int $operatorId,
        ?string $validFrom = null
    ): array {
        if ($userId <= 0 || $managerId <= 0) {
            return ['success' => false, 'message' => 'Utilisateur ou manager invalide.'];
        }
        if ($userId === $managerId) {
            return ['success' => false, 'message' => 'Un utilisateur ne peut pas etre son propre manager.'];
        }
        if ($this->hierarchy->relationExists($userId, $managerId)) {
            return ['success' => false, 'message' => 'Relation hierarchique deja active.'];
        }
        if ($this->wouldCreateCycle($userId, $managerId)) {
            return ['success' => false, 'message' => 'Relation refusee : boucle hierarchique detectee.'];
        }

        $this->hierarchy->addManager($userId, $managerId, $companyId, $isPrimary, $validFrom, $operatorId);
        logger('audit')->info('user_manager_added', [
            'user_id' => $userId,
            'manager_id' => $managerId,
            'operator_id' => $operatorId,
        ]);

        return ['success' => true, 'message' => 'Manager ajoute.'];
    }

    public function removeManager(int $userId, int $managerId, int $operatorId): array
    {
        if (!$this->hierarchy->relationExists($userId, $managerId)) {
            return ['success' => false, 'message' => 'Relation hierarchique introuvable.'];
        }

        $this->hierarchy->removeManager($userId, $managerId, $operatorId);
        logger('audit')->info('user_manager_removed', [
            'user_id' => $userId,
            'manager_id' => $managerId,
            'operator_id' => $operatorId,
        ]);

        return ['success' => true, 'message' => 'Manager retire.'];
    }

    public function getManagers(int $userId): array
    {
        return $this->hierarchy->getManagers($userId);
    }

    public function getSubordinates(int $managerId, ?int $companyId = null): array
    {
        return $this->hierarchy->getSubordinates($managerId, $companyId);
    }

    public function wouldCreateCycle(int $userId, int $managerId): bool
    {
        $visited = [];
        $queue = [$managerId];

        while ($queue !== []) {
            $current = array_shift($queue);
            if (in_array($current, $visited, true)) {
                continue;
            }
            $visited[] = $current;

            foreach ($this->hierarchy->getManagerIds((int) $current) as $nextManagerId) {
                if ((int) $nextManagerId === $userId) {
                    return true;
                }
                $queue[] = (int) $nextManagerId;
            }
        }

        return false;
    }
}
