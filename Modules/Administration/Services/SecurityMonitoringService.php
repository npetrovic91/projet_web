<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Administration\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;
use Nenad\Autosav\Modules\Administration\Models\SecurityMonitoringModel;

/**
 * AUTOSAV — Supervision sécurité (déblocage IP/compte)
 *
 * CORRECTIF (section 3 du roadmap, "SecurityController contient de la
 * logique métier qui devrait être dans un Service") : la validation de la
 * raison de déblocage, le choix IP/compte, et la gestion d'erreur
 * vivaient directement dans le contrôleur — logique métier non testable
 * indépendamment du HTTP, et impossible à réutiliser (ex. CLI d'admin
 * d'urgence) sans dupliquer le contrôleur.
 */
final class SecurityMonitoringService implements ServiceInterface
{
    public function __construct(private SecurityMonitoringModel $model = new SecurityMonitoringModel())
    {
    }

    public function tableauDeBord(?string $filterIp, ?string $filterEmail, int $page, int $perPage = 30): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        return [
            'stats' => $this->model->getSecurityStats(24),
            'topFailedIps' => $this->model->getTopFailedIps(10),
            'attempts' => $this->model->getAttempts($perPage, $offset, $filterIp, $filterEmail),
            'totalAttempts' => $this->model->countAttempts($filterIp, $filterEmail),
            'activeIpBlocks' => $this->model->getActiveIpBlocks(50),
            'activeEmailBlocks' => $this->model->getActiveEmailBlocks(50),
            'unblockHistory' => $this->model->getUnblockHistory(20),
        ];
    }

    /**
     * @param 'ip'|'user' $type
     * @return array{success: bool, message: string}
     */
    public function debloquer(string $type, int $id, int $adminId, string $adminIp, string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            return ['success' => false, 'message' => 'La raison du déblocage est obligatoire.'];
        }
        if (!in_array($type, ['ip', 'user'], true)) {
            return ['success' => false, 'message' => 'Type de déblocage invalide.'];
        }

        try {
            if ($type === 'ip') {
                $this->model->unblockIp($id, $adminId, $adminIp, $reason);
                return ['success' => true, 'message' => "Blocage IP #{$id} levé avec succès."];
            }
            $this->model->unblockUser($id, $adminId, $adminIp, $reason);
            return ['success' => true, 'message' => "Compte utilisateur #{$id} déverrouillé avec succès."];
        } catch (\Throwable $e) {
            if (function_exists('logger')) {
                logger('security')->error('security_unblock_failed', [
                    'type' => $type,
                    'id' => $id,
                    'admin_id' => $adminId,
                    'error' => $e->getMessage(),
                ]);
            }
            return ['success' => false, 'message' => 'Une erreur est survenue lors du déblocage.'];
        }
    }
}
