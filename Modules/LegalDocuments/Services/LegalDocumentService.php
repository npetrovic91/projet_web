<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\LegalDocuments\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\LegalDocuments\Models\LegalDocumentModel;

class LegalDocumentService implements ServiceInterface{
    public function __construct(private readonly LegalDocumentModel $documents = new LegalDocumentModel()) {}

    public function dashboard(array $filters = []): array
    {
        return [
            'documents' => $this->documents->lister($filters),
            'stats' => $this->documents->statistiques(),
            'types' => $this->documents->types(),
            'societes' => $this->documents->societes(),
            'statuts' => $this->documents->statuts(),
        ];
    }

    public function trouver(int $id): ?array { return $this->documents->trouver($id); }
    public function creer(array $input, int $userId): int { return $this->documents->creer($input, $userId); }
    public function modifier(int $id, array $input, int $userId): bool { return $this->documents->modifier($id, $input, $userId); }
    public function supprimer(int $id, int $userId): bool { return $this->documents->supprimer($id, $userId); }
    public function liensSocietes(int $id): array { return $this->documents->liensSocietes($id); }
    public function acceptations(int $id): array { return $this->documents->acceptations($id); }
    public function lierSociete(int $documentId, int $companyId, bool $mandatory, int $priority, ?int $statusId): void { $this->documents->lierSociete($documentId, $companyId, $mandatory, $priority, $statusId); }
    public function accepter(int $documentId, int $userId, string $version, string $ip, string $userAgent): void { $this->documents->accepter($documentId, $userId, $version, $ip, $userAgent); }
}
