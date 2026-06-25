<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Standards\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Core\Security\Class\RoleResolver;
use Nenad\Autosav\Modules\Notifications\Services\ActionNotifier;
use Nenad\Autosav\Modules\Standards\Models\StandardModel;

class StandardService implements ServiceInterface{
    /**
     * Niveau RoleResolver de chef_de_departement (70). Seul un acteur de
     * niveau strictement supérieur peut valider/rejeter un standard
     * (ACC-008 : "validé par un niveau supérieur").
     */
    private const NIVEAU_CHEF_DEPARTEMENT = 70;

    private ActionNotifier $notifier;

    public function __construct(private ?StandardModel $model = null, ?ActionNotifier $notifier = null)
    {
        $this->model ??= new StandardModel();
        $this->notifier = $notifier ?? new ActionNotifier();
    }

    public function dashboard(array $filters = []): array
    {
        return [
            'stats' => $this->model->stats(),
            'standards' => $this->model->standards($filters),
            'versions' => $this->model->versions(),
            'exigences' => $this->model->exigences(),
            'refs' => $this->model->referentiels(),
        ];
    }

    public function refs(): array { return $this->model->referentiels(); }
    public function standard(int $id): ?array { return $this->model->findStandard($id); }
    public function standards(array $filters = []): array { return $this->model->standards($filters); }
    public function versions(?int $standardId = null): array { return $this->model->versions($standardId); }
    public function version(int $id): ?array { return $this->model->findVersion($id); }
    public function exigences(?int $versionId = null): array { return $this->model->exigences($versionId); }
    public function evaluation(int $versionId): array { return $this->model->evaluationUtilisateurs($versionId); }

    public function creerStandard(array $data, ?int $userId): int { return $this->model->createStandard($data, $userId); }
    public function modifierStandard(int $id, array $data, ?int $userId): bool { return $this->model->updateStandard($id, $data, $userId); }
    public function supprimerStandard(int $id, ?int $userId): bool { return $this->model->softDeleteStandard($id, $userId); }

    public function creerVersion(array $data, ?int $userId): int { return $this->model->createVersion($data, $userId); }
    public function modifierVersion(int $id, array $data, ?int $userId): bool { return $this->model->updateVersion($id, $data, $userId); }
    public function supprimerVersion(int $id, ?int $userId): bool { return $this->model->softDeleteVersion($id, $userId); }
    public function versionsApprouvees(?int $standardId = null): array { return $this->model->versionsApprouvees($standardId); }

    /**
     * ACC-008 : un auteur peut soumettre sa propre version en brouillon
     * pour validation, mais ne peut jamais se valider lui-même (ce
     * contrôle est fait dans validerVersion/rejeterVersion, pas ici :
     * soumettre n'active rien, ça ne fait que passer en file d'attente).
     */
    public function soumettrePourValidation(int $id, int $userId): array
    {
        $ok = $this->model->soumettrePourValidation($id, $userId);
        return $ok
            ? ['success' => true, 'errors' => []]
            : ['success' => false, 'errors' => ['Cette version n\'est pas en brouillon ou est introuvable.']];
    }

    /**
     * ACC-008 : "Un chef_departement ne peut pas publier ou valider
     * directement ses propres standards. Tout standard créé par
     * chef_departement doit être validé par un niveau supérieur." On
     * refuse donc explicitement : (1) l'auto-validation par l'auteur,
     * quel que soit son rôle, et (2) la validation par un acteur de
     * niveau chef_de_departement ou inférieur.
     */
    public function validerVersion(int $id, int $userId): array
    {
        $version = $this->model->findVersion($id);
        if ($version === null) {
            return ['success' => false, 'errors' => ['Version introuvable.']];
        }
        if ((int) ($version['vst_cree_par_utilisateur_id'] ?? 0) === $userId) {
            return ['success' => false, 'errors' => ['Un standard ne peut pas être validé par son propre auteur.']];
        }
        if (!$this->validateurDeNiveauSuffisant()) {
            return ['success' => false, 'errors' => ['Seul un niveau supérieur à chef_de_departement peut valider un standard.']];
        }

        $ok = $this->model->validerVersion($id, $userId);
        if ($ok) {
            $this->notifier->notifierUtilisateur(
                (int) $version['vst_cree_par_utilisateur_id'],
                'standard.version_validee',
                'Standard validé',
                sprintf('Votre version %s du standard %s a été validée et est désormais applicable.', $version['vst_version'], $version['std_nom'] ?? ''),
                createdBy: $userId
            );
        }
        return $ok
            ? ['success' => true, 'errors' => []]
            : ['success' => false, 'errors' => ['Cette version n\'est pas en attente de validation.']];
    }

    public function rejeterVersion(int $id, int $userId, string $motif): array
    {
        $version = $this->model->findVersion($id);
        if ($version === null) {
            return ['success' => false, 'errors' => ['Version introuvable.']];
        }
        if ((int) ($version['vst_cree_par_utilisateur_id'] ?? 0) === $userId) {
            return ['success' => false, 'errors' => ['Un standard ne peut pas être rejeté par son propre auteur.']];
        }
        if (!$this->validateurDeNiveauSuffisant()) {
            return ['success' => false, 'errors' => ['Seul un niveau supérieur à chef_de_departement peut rejeter un standard.']];
        }
        if (trim($motif) === '') {
            return ['success' => false, 'errors' => ['Un motif de rejet est obligatoire.']];
        }

        $ok = $this->model->rejeterVersion($id, $userId, $motif);
        if ($ok) {
            $this->notifier->notifierUtilisateur(
                (int) $version['vst_cree_par_utilisateur_id'],
                'standard.version_rejetee',
                'Standard rejeté',
                sprintf('Votre version %s du standard %s a été rejetée. Motif : %s', $version['vst_version'], $version['std_nom'] ?? '', $motif),
                createdBy: $userId
            );
        }
        return $ok
            ? ['success' => true, 'errors' => []]
            : ['success' => false, 'errors' => ['Cette version n\'est pas en attente de validation.']];
    }

    private function validateurDeNiveauSuffisant(): bool
    {
        if (function_exists('has_role') && has_role(['super_administrateur', 'super_admin', 'SUPERADMIN'])) {
            return true;
        }
        $roleCodes = (array) ($_SESSION['user']['role_codes'] ?? $_SESSION['user_roles'] ?? $_SESSION['user']['roles'] ?? []);
        return RoleResolver::applicationLevel($roleCodes) > self::NIVEAU_CHEF_DEPARTEMENT;
    }

    public function creerExigence(array $data, ?int $userId): int { return $this->model->createExigence($data, $userId); }
    public function supprimerExigence(int $id, ?int $userId): bool { return $this->model->softDeleteExigence($id, $userId); }
}
