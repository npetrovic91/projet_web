<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Portail\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Portail\Models\ContexteActifModel;

class ContexteActifService implements ServiceInterface{
    private ContexteActifModel $model;

    public function __construct(?ContexteActifModel $model = null)
    {
        $this->model = $model ?? new ContexteActifModel();
    }

    public function donneesPortail(int $utilisateurId): array
    {
        $contexte = $this->contexteSession();
        $societes = $this->model->societesAccessibles($utilisateurId);
        $societeId = $contexte['societe_id'] ?: (int)($societes[0]['soc_id'] ?? 0) ?: null;

        $concessions = $this->model->societesConcessionsAccessibles($utilisateurId);
        $concessionId = $contexte['concession_id'] ?: $this->premierId($concessions, 'soc_id');
        $marques = $concessionId ? $this->model->marquesPourConcession($concessionId) : [];
        $marqueId = $contexte['marque_id'] ?: $this->premierId($marques, 'soc_id');
        $services = $societeId ? $this->model->servicesUtilisateur($utilisateurId, $societeId) : [];
        $serviceId = $contexte['service_id'] ?: $this->premierId($services, 'srv_id');
        $equipes = $societeId ? $this->model->equipesUtilisateur($utilisateurId, $societeId, $serviceId) : [];

        return [
            'utilisateur' => $this->model->utilisateur($utilisateurId),
            'contexte' => $contexte + [
                'societe_id' => $societeId,
                'concession_id' => $concessionId,
                'marque_id' => $marqueId,
                'service_id' => $serviceId,
                'equipe_id' => $contexte['equipe_id'] ?: $this->premierId($equipes, 'equ_id'),
            ],
            'societes' => $societes,
            'concessions' => $concessions,
            'marques' => $marques,
            'services' => $services,
            'equipes' => $equipes,
            'regles' => [
                'societe_obligatoire' => true,
                'concession_obligatoire' => true,
                'marque_verrouillee_si_unique' => count($marques) === 1,
                'concession_verrouillee_si_unique' => count($concessions) === 1,
                'service_verrouille_si_unique' => count($services) === 1,
                'equipe_verrouillee_si_unique' => count($equipes) === 1,
            ],
        ];
    }

    public function optionsDependantes(int $utilisateurId, array $input): array
    {
        $societeId = $this->entierNullable($input['societe_id'] ?? null);
        $concessionId = $this->entierNullable($input['concession_id'] ?? null);
        $serviceId = $this->entierNullable($input['service_id'] ?? null);
        return [
            'concessions' => $this->model->societesConcessionsAccessibles($utilisateurId),
            'marques' => $concessionId ? $this->model->marquesPourConcession($concessionId) : [],
            'services' => $societeId ? $this->model->servicesUtilisateur($utilisateurId, $societeId) : [],
            'equipes' => $societeId ? $this->model->equipesUtilisateur($utilisateurId, $societeId, $serviceId) : [],
        ];
    }

    public function appliquerContexte(int $utilisateurId, array $input): array
    {
        $societeId = $this->entierNullable($input['societe_id'] ?? null);
        $concessionId = $this->entierNullable($input['concession_id'] ?? null);
        $marqueId = $this->entierNullable($input['marque_id'] ?? null);
        $serviceId = $this->entierNullable($input['service_id'] ?? null);
        $equipeId = $this->entierNullable($input['equipe_id'] ?? null);

        if (!$societeId || !$this->model->validerAdhesionSociete($utilisateurId, $societeId)) {
            throw new \InvalidArgumentException('La société sélectionnée n’est pas accessible à cet utilisateur.');
        }
        if (!$concessionId) {
            $concessions = $this->model->societesConcessionsAccessibles($utilisateurId);
            $concessionId = $this->premierId($concessions, 'soc_id');
        }
        if ($concessionId && !$this->model->validerAdhesionSociete($utilisateurId, $concessionId)) {
            throw new \InvalidArgumentException('La concession sélectionnée n’est pas accessible à cet utilisateur.');
        }
        if ($marqueId && $concessionId && !$this->model->validerRepresentationMarque($concessionId, $marqueId)) {
            throw new \InvalidArgumentException('La marque sélectionnée n’est pas représentée par la concession active.');
        }
        if ($serviceId && !$this->model->validerService($utilisateurId, $societeId, $serviceId)) {
            throw new \InvalidArgumentException('Le service sélectionné n’est pas affecté à cet utilisateur dans cette société.');
        }
        if ($equipeId && !$this->model->validerEquipe($utilisateurId, $societeId, $equipeId, $serviceId)) {
            throw new \InvalidArgumentException('L’équipe sélectionnée n’est pas affectée à cet utilisateur dans ce contexte.');
        }

        $contexte = [
            'societe_id' => $societeId,
            'concession_id' => $concessionId,
            'marque_id' => $marqueId,
            'service_id' => $serviceId,
            'equipe_id' => $equipeId,
            'session_id' => $_SESSION['session_id'] ?? $_SESSION['user']['session_id'] ?? null,
        ];
        $this->ecrireSession($contexte);
        $this->model->mettreAJourUtilisateur($utilisateurId, $societeId, $marqueId);
        $this->model->mettreAJourSessionPersistante($contexte['session_id'] ? (int)$contexte['session_id'] : null, $contexte);
        $this->model->journaliserContexte($utilisateurId, $contexte, 'contexte_actif.modifie');
        return $contexte;
    }

    public function contexteSession(): array
    {
        return [
            'societe_id' => $this->entierNullable($_SESSION['active_company_id'] ?? $_SESSION['user']['active_company_id'] ?? $_SESSION['society']['id'] ?? null),
            'concession_id' => $this->entierNullable($_SESSION['active_concession_id'] ?? $_SESSION['user']['actual_concession_id'] ?? $_SESSION['concession']['id'] ?? null),
            'marque_id' => $this->entierNullable($_SESSION['active_brand_id'] ?? $_SESSION['user']['actual_brand'] ?? $_SESSION['marque']['id'] ?? null),
            'service_id' => $this->entierNullable($_SESSION['active_service_id'] ?? $_SESSION['user']['actual_service_id'] ?? null),
            'equipe_id' => $this->entierNullable($_SESSION['active_team_id'] ?? $_SESSION['user']['actual_team_id'] ?? null),
        ];
    }

    private function ecrireSession(array $contexte): void
    {
        $_SESSION['active_company_id'] = $contexte['societe_id'];
        $_SESSION['active_concession_id'] = $contexte['concession_id'];
        $_SESSION['active_brand_id'] = $contexte['marque_id'];
        $_SESSION['active_service_id'] = $contexte['service_id'];
        $_SESSION['active_team_id'] = $contexte['equipe_id'];

        $_SESSION['user']['active_company_id'] = $contexte['societe_id'];
        $_SESSION['user']['actual_concession_id'] = $contexte['concession_id'];
        $_SESSION['user']['actual_brand'] = $contexte['marque_id'];
        $_SESSION['user']['actual_service_id'] = $contexte['service_id'];
        $_SESSION['user']['actual_team_id'] = $contexte['equipe_id'];

        $_SESSION['society']['id'] = $contexte['societe_id'];
        $_SESSION['concession']['id'] = $contexte['concession_id'];
        $_SESSION['marque']['id'] = $contexte['marque_id'];
        $_SESSION['_last_activity'] = time();
    }

    private function entierNullable(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return null;
        }
        return (int)$value;
    }

    private function premierId(array $rows, string $key): ?int
    {
        return isset($rows[0][$key]) ? (int)$rows[0][$key] : null;
    }
}
