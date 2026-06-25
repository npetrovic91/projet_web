<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Files\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Files\Models\FileModel;

class FileService implements ServiceInterface{
    public function __construct(private readonly FileModel $files = new FileModel()) {}

    public function dashboard(array $filters = []): array
    {
        [$userId, $companyId, $bypass] = $this->scope();
        return [
            'files' => $this->files->lister($filters, $userId, $companyId, $bypass),
            'stats' => $this->files->statistiques($userId, $companyId, $bypass),
            'statuts' => $this->files->statuts(),
        ];
    }

    public function trouver(int $id): ?array { return $this->files->trouver($id, ...$this->scope()); }
    public function contenu(int $id): ?array { return $this->files->contenu($id, ...$this->scope()); }
    public function liaisons(int $id): array { return $this->files->liaisons($id, ...$this->scope()); }
    public function upload(array $file, array $input, int $userId): int
    {
        [, $companyId] = $this->scope();
        return $this->files->creerDepuisUpload($file, $input, $userId, $companyId);
    }
    public function lier(int $fileId, string $targetType, int $targetId, string $linkType, int $userId): void
    {
        [, $companyId, $bypass] = $this->scope();
        $this->files->lier($fileId, $targetType, $targetId, $linkType, $userId, true, $userId, $companyId, $bypass);
    }
    public function supprimer(int $id, int $userId): bool
    {
        [, $companyId, $bypass] = $this->scope();
        return $this->files->supprimer($id, $userId, $companyId, $bypass);
    }

    private function scope(): array
    {
        $userId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        $companyId = (int) ($_SESSION['active_company_id'] ?? $_SESSION['user']['active_company_id'] ?? 0);
        $bypass = function_exists('has_role')
            && has_role(defined('ROLE_SUPERADMIN') ? (string) ROLE_SUPERADMIN : 'super_administrateur');
        return [$userId, $companyId, $bypass];
    }
}
