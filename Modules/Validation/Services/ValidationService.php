<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Validation\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Notifications\Services\ActionNotifier;
use Nenad\Autosav\Modules\Validation\Models\ValidationModel;

class ValidationService implements ServiceInterface{
    public function __construct(
        private ?ValidationModel $model = null,
        private ?ActionNotifier $notifier = null
    ) {
        $this->model ??= new ValidationModel();
        $this->notifier ??= new ActionNotifier();
    }

    public function dashboard(array $filters = []): array
    {
        return [
            'stats' => $this->model->stats(),
            'demandes' => $this->model->demandes($filters),
            'regles' => $this->model->regles($filters),
            'refs' => $this->model->refs(),
        ];
    }

    public function demande(int $id): ?array { return $this->model->findDemande($id); }
    public function regle(int $id): ?array { return $this->model->findRegle($id); }
    public function refs(): array { return $this->model->refs(); }
    public function demandes(array $filters = []): array { return $this->model->demandes($filters); }
    public function regles(array $filters = []): array { return $this->model->regles($filters); }
    public function creerDemande(array $data, int $userId): int { return $this->model->createDemande($data, $userId); }
    /**
     * CORRECTIF 2.4 (notifications in-app, 2026-06-27) : le demandeur
     * n'était jamais informé de la décision prise sur sa demande de
     * validation — il devait aller la consulter manuellement.
     */
    public function deciderDemande(int $id, string $decision, ?string $commentaire, int $userId): bool
    {
        $demande = $this->model->findDemande($id);
        $ok = $this->model->decideDemande($id, $decision, $commentaire, $userId);
        if ($ok && $demande !== null) {
            $demandeurId = (int) ($demande['dva_demandeur_utilisateur_id'] ?? 0);
            if ($demandeurId > 0 && $demandeurId !== $userId) {
                $libelle = match ($decision) {
                    'approuvee' => 'approuvée',
                    'refusee' => 'refusée',
                    default => 'annulée',
                };
                $this->notifier->notifierUtilisateur(
                    $demandeurId,
                    'validation.demande_decidee',
                    'Votre demande de validation a été ' . $libelle,
                    trim('Votre demande de validation a été ' . $libelle . '. ' . ($commentaire ?? '')),
                    ['demande_id' => $id, 'decision' => $decision],
                    isset($demande['dva_societe_id']) ? (int) $demande['dva_societe_id'] : null,
                    $userId
                );
            }
        }
        return $ok;
    }
    public function supprimerDemande(int $id, int $userId): bool { return $this->model->softDeleteDemande($id, $userId); }
    public function creerRegle(array $data, int $userId): int { return $this->model->createRegle($data, $userId); }
    public function modifierRegle(int $id, array $data, int $userId): bool { return $this->model->updateRegle($id, $data, $userId); }
    public function supprimerRegle(int $id, int $userId): bool { return $this->model->softDeleteRegle($id, $userId); }
    public function export(): array { return $this->model->export(); }
}
